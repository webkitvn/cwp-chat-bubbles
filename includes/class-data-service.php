<?php
/**
 * Data Service Class
 *
 * Unified data service to eliminate duplicate queries between Frontend and Assets classes
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles Data Service Class
 *
 * @since 1.0.0
 */
class CWP_Chat_Bubbles_Data_Service {

    /**
     * Instance of this class
     *
     * @var CWP_Chat_Bubbles_Data_Service
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Items Manager instance
     *
     * @var CWP_Chat_Bubbles_Items_Manager
     * @since 1.0.0
     */
    private $items_manager;

    /**
     * Settings instance
     *
     * @var CWP_Chat_Bubbles_Settings
     * @since 1.0.0
     */
    private $settings;

    /**
     * Get instance
     *
     * @return CWP_Chat_Bubbles_Data_Service
     * @since 1.0.0
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->items_manager = CWP_Chat_Bubbles_Items_Manager::get_instance();
        $this->settings = CWP_Chat_Bubbles_Settings::get_instance();
    }

    /**
     * Get processed frontend data with comprehensive caching
     * This replaces both get_all_items() calls and get_frontend_platform_data()
     *
     * @return array|false Processed frontend data or false if no enabled items
     * @since 1.0.0
     */
    public function get_frontend_data() {
        // Create cache key based on data version and settings
        $data_version = $this->get_data_version();
        $settings_hash = $this->get_settings_hash();
        $cache_key = 'cwp_frontend_complete_v' . $data_version . '_s' . $settings_hash;
        
        // Try to get cached data first
        $cached_data = wp_cache_get($cache_key, 'cwp_chat_bubbles');
        if (false !== $cached_data) {
            return $cached_data;
        }

        // Get enabled items (this will use the new caching in Items Manager)
        $enabled_items = $this->items_manager->get_all_items(true);
        
        if (empty($enabled_items)) {
            // Cache the empty result for 1 hour to avoid repeated queries
            wp_cache_set($cache_key, false, 'cwp_chat_bubbles', HOUR_IN_SECONDS);
            return false;
        }

        // Process items with all required data for both template and JavaScript
        $processed_data = array(
            'items' => array(),
            'settings' => array(
                'position' => $this->settings->get_option('position', 'bottom-right'),
                'main_button_color' => $this->settings->get_option('main_button_color', '#52BA00'),
                'animation_enabled' => $this->settings->get_option('animation_enabled', true),
                'show_labels' => $this->settings->should_show_labels()
            ),
            'support_icon' => $this->get_main_icon_url(),
            'cancel_icon' => CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/cancel.svg'
        );

        foreach ($enabled_items as $item) {
            $processed_item = array(
                'id' => $item['id'],
                'platform' => $item['platform'],
                'label' => $item['label'],
                'contact_value' => $item['contact_value'],
                'enabled' => $item['enabled'],
                'qr_code_id' => $item['qr_code_id'],
                'sort_order' => $item['sort_order'],
                // Pre-processed data for performance
                'platform_url' => $this->generate_platform_url($item['platform'], $item),
                'platform_icon' => $this->items_manager->get_platform_icon_url($item['platform']),
                'platform_color' => $this->items_manager->get_platform_color($item['platform']),
                'qr_code_url' => !empty($item['qr_code_id']) ? wp_get_attachment_url($item['qr_code_id']) : '',
                'has_qr' => !empty($item['qr_code_id'])
            );
            
            $processed_data['items'][] = $processed_item;
        }

        // Cache the processed data for 1 hour
        wp_cache_set($cache_key, $processed_data, 'cwp_chat_bubbles', HOUR_IN_SECONDS);

        return $processed_data;
    }

    /**
     * Get frontend data optimized for JavaScript
     *
     * @return array Frontend data for JavaScript
     * @since 1.0.0
     */
    public function get_frontend_js_data() {
        $frontend_data = $this->get_frontend_data();
        
        if (false === $frontend_data) {
            return array();
        }

        // Transform for JavaScript consumption
        $js_data = array();
        foreach ($frontend_data['items'] as $item) {
            $js_data[$item['id']] = array(
                'id' => $item['id'],
                'platform' => $item['platform'],
                'label' => $item['label'],
                'url' => $item['platform_url'],
                'icon' => $item['platform_icon'],
                'qr_code' => $item['qr_code_url'],
                'has_qr' => $item['has_qr']
            );
        }

        return $js_data;
    }

    /**
     * Check if should load on current page (unified logic)
     *
     * @return bool Whether to load on current page
     * @since 1.0.0
     */
    public function should_load_on_current_page() {
        // Plugin must be enabled
        if (!$this->settings->is_enabled()) {
            return false;
        }

        // Auto-loading must be enabled
        if (!$this->settings->is_auto_load_enabled()) {
            return false;
        }

        // Don't load in admin
        if (is_admin()) {
            return false;
        }

        // Mobile display is now handled via CSS media queries
        // This eliminates unreliable server-side wp_is_mobile() detection

        // Check excluded pages
        $excluded_pages = $this->settings->get_option('exclude_pages', array());
        if (!empty($excluded_pages) && is_page()) {
            $current_page_id = get_the_ID();
            if (in_array($current_page_id, $excluded_pages)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get data version for cache invalidation
     *
     * @return string Data version
     * @since 1.0.0
     */
    private function get_data_version() {
        // Get data version from option directly to avoid reflection
        $version = wp_cache_get('cwp_chat_bubbles_data_version', 'cwp_chat_bubbles');
        
        if (false === $version) {
            // Get version from database or create new one
            $version = get_option('cwp_chat_bubbles_data_version', '1');
            wp_cache_set('cwp_chat_bubbles_data_version', $version, 'cwp_chat_bubbles', DAY_IN_SECONDS);
        }
        
        return $version;
    }

    /**
     * Get settings hash for cache invalidation when settings change
     *
     * @return string Settings hash
     * @since 1.0.0
     */
    private function get_settings_hash() {
        $relevant_settings = array(
            'position' => $this->settings->get_option('position', 'bottom-right'),
            'main_button_color' => $this->settings->get_option('main_button_color', '#52BA00'),
            'animation_enabled' => $this->settings->get_option('animation_enabled', true),
            'show_labels' => $this->settings->should_show_labels(),
            'custom_main_icon' => $this->settings->get_option('custom_main_icon', 0)
        );
        
        return substr(md5(serialize($relevant_settings)), 0, 8);
    }

    /**
     * Generate platform URL
     *
     * @param string $platform Platform name
     * @param array $item Item data
     * @return string Platform URL
     * @since 1.0.0
     */
    private function generate_platform_url($platform, $item) {
        $contact_value = !empty($item['contact_value']) ? $item['contact_value'] : '';
        
        if (empty($contact_value)) {
            return '#';
        }
        
        switch ($platform) {
            case 'phone':
                return 'tel:' . $contact_value;
                
            case 'zalo':
                return 'https://zalo.me/' . $contact_value . '?openChat=true';
                
            case 'whatsapp':
                return 'https://wa.me/' . $contact_value;
                
            case 'viber':
                return 'viber://contact?number=' . $contact_value;
                
            case 'telegram':
                return 'https://t.me/' . $contact_value;
                
            case 'messenger':
                return 'https://m.me/' . $contact_value;
                
            case 'line':
                return 'https://line.me/ti/p/' . $contact_value;
                
            case 'kakaotalk':
                return '#kakaotalk-' . $contact_value;
                
            default:
                return '#';
        }
    }

    /**
     * Get main icon URL
     *
     * @return string Main icon URL
     * @since 1.0.0
     */
    private function get_main_icon_url() {
        $custom_icon_id = $this->settings->get_option('custom_main_icon', 0);
        
        if ($custom_icon_id > 0) {
            $custom_icon_url = wp_get_attachment_url($custom_icon_id);
            if ($custom_icon_url) {
                return $custom_icon_url;
            }
        }
        
        return CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg';
    }
} 