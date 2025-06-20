<?php
/**
 * Assets Class
 *
 * Handles asset loading and enqueueing
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles Assets Class
 *
 * @since 1.0.0
 */
class CWP_Chat_Bubbles_Assets {

    /**
     * Instance of this class
     *
     * @var CWP_Chat_Bubbles_Assets
     * @since 1.0.0
     */
    private static $instance = null;

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
     * @return CWP_Chat_Bubbles_Assets
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
        $this->settings = CWP_Chat_Bubbles_Settings::get_instance();
        $this->init();
    }

    /**
     * Initialize assets
     *
     * @since 1.0.0
     */
    private function init() {
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add inline styles for customization
        add_action('wp_head', array($this, 'output_inline_styles'));
    }

    /**
     * Enqueue frontend assets
     *
     * @since 1.0.0
     */
    public function enqueue_frontend_assets() {
        // Only load if plugin is enabled and should auto-load
        if (!$this->settings->is_enabled() || !$this->settings->is_auto_load_enabled()) {
            return;
        }

        // Check if we should load on current page
        if (!$this->should_load_on_current_page()) {
            return;
        }

        // CSS
        $css_file = CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/css/chat-bubbles.min.css';
        if (file_exists(CWP_CHAT_BUBBLES_PLUGIN_DIR . 'assets/css/chat-bubbles.min.css')) {
            wp_enqueue_style(
                'cwp-chat-bubbles',
                $css_file,
                array(),
                $this->get_file_version('assets/css/chat-bubbles.min.css'),
                'all'
            );
        }

        // JavaScript
        $js_file = CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/js/chat-bubbles.min.js';
        if (file_exists(CWP_CHAT_BUBBLES_PLUGIN_DIR . 'assets/js/chat-bubbles.min.js')) {
            wp_enqueue_script(
                'cwp-chat-bubbles',
                $js_file,
                array(),
                $this->get_file_version('assets/js/chat-bubbles.min.js'),
                true // Load in footer
            );

            // Pass data to JavaScript
            wp_localize_script('cwp-chat-bubbles', 'cwpChatBubbles', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cwp_chat_bubbles_nonce'),
                'platforms' => $this->get_frontend_platform_data(),
                'settings' => array(
                    'position' => $this->settings->get_option('position', 'bottom-right'),
                    'animationEnabled' => $this->settings->get_option('animation_enabled', true),
                    'showLabels' => $this->settings->get_option('show_labels', true)
                )
            ));
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @since 1.0.0
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin settings page
        if ('toplevel_page_cwp-chat-bubbles' !== $hook) {
            return;
        }

        // WordPress media library for QR code uploads
        wp_enqueue_media();

        // WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Admin CSS
        $admin_css = CWP_CHAT_BUBBLES_PLUGIN_URL . 'admin/css/admin.min.css';
        if (file_exists(CWP_CHAT_BUBBLES_PLUGIN_DIR . 'admin/css/admin.min.css')) {
            wp_enqueue_style(
                'cwp-chat-bubbles-admin',
                $admin_css,
                array('wp-color-picker'),
                $this->get_file_version('admin/css/admin.min.css')
            );
        }

        // Enqueue jQuery UI Sortable for drag-and-drop
        wp_enqueue_script('jquery-ui-sortable');

        // Admin JavaScript (if we create one later)
        $admin_js = CWP_CHAT_BUBBLES_PLUGIN_URL . 'admin/js/admin.js';
        if (file_exists(CWP_CHAT_BUBBLES_PLUGIN_DIR . 'admin/js/admin.js')) {
            wp_enqueue_script(
                'cwp-chat-bubbles-admin',
                $admin_js,
                array('jquery', 'wp-color-picker', 'jquery-ui-sortable', 'media-upload'),
                $this->get_file_version('admin/js/admin.js'),
                true
            );

            // Pass data to admin JavaScript
            wp_localize_script('cwp-chat-bubbles-admin', 'wpAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cwp_chat_bubbles_admin')
            ));
        }
    }

    /**
     * Output inline styles for customization
     *
     * @since 1.0.0
     */
    public function output_inline_styles() {
        // Only output if plugin is enabled
        if (!$this->settings->is_enabled() || !$this->settings->is_auto_load_enabled()) {
            return;
        }

        $custom_css = '';
        
        // Main button color
        $main_color = $this->settings->get_option('main_button_color', '#52BA00');
        if ($main_color && $main_color !== '#52BA00') {
            $custom_css .= "
                #chat-bubbles .chat-icon {
                    background-color: {$main_color} !important;
                }
                #chat-bubbles .chat-icon::before {
                    background-color: {$main_color} !important;
                }
            ";
        }

        // Position
        $position = $this->settings->get_option('position', 'bottom-right');
        if ($position !== 'bottom-right') {
            $position_css = $this->get_position_css($position);
            $custom_css .= $position_css;
        }

        // Animation settings
        if (!$this->settings->get_option('animation_enabled', true)) {
            $custom_css .= "
                #chat-bubbles .chat-icon::before {
                    animation: none !important;
                }
                #chat-bubbles .item-group,
                #chat-bubbles .bubble-modal {
                    transition: none !important;
                }
            ";
        }

        // Custom CSS from settings
        $user_custom_css = $this->settings->get_option('custom_css', '');
        if ($user_custom_css) {
            $custom_css .= "\n" . $user_custom_css;
        }

        // Output styles if we have any
        if ($custom_css) {
            echo "<style id='cwp-chat-bubbles-custom-css'>\n" . wp_strip_all_tags($custom_css) . "\n</style>\n";
        }
    }

    /**
     * Get file version for cache busting
     *
     * @param string $file Relative file path
     * @return string File version
     * @since 1.0.0
     */
    private function get_file_version($file) {
        $file_path = CWP_CHAT_BUBBLES_PLUGIN_DIR . $file;
        
        if (file_exists($file_path)) {
            return filemtime($file_path);
        }
        
        return CWP_CHAT_BUBBLES_VERSION;
    }

    /**
     * Check if assets should load on current page
     *
     * @return bool Whether to load assets
     * @since 1.0.0
     */
    private function should_load_on_current_page() {
        // Don't load in admin
        if (is_admin()) {
            return false;
        }

        // Check mobile setting
        if (wp_is_mobile() && !$this->settings->get_option('load_on_mobile', true)) {
            return false;
        }

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
     * Get frontend platform data for JavaScript
     *
     * @return array Platform data for frontend
     * @since 1.0.0
     */
    private function get_frontend_platform_data() {
        $items_manager = CWP_Chat_Bubbles_Items_Manager::get_instance();
        $enabled_items = $items_manager->get_all_items(true); // Get enabled items only
        $frontend_data = array();

        foreach ($enabled_items as $item) {
            $frontend_data[$item['id']] = array(
                'id' => $item['id'],
                'platform' => $item['platform'],
                'label' => $item['label'],
                'url' => $this->generate_platform_url($item['platform'], $item),
                'icon' => $this->get_platform_icon_url($item['platform']),
                'qr_code' => !empty($item['qr_code_id']) ? wp_get_attachment_url($item['qr_code_id']) : '',
                'has_qr' => !empty($item['qr_code_id'])
            );
        }

        return $frontend_data;
    }

    /**
     * Generate platform URL
     *
     * @param string $platform Platform name
     * @param array $item Item data from custom table
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
                // KakaoTalk doesn't have a direct web URL, will need custom implementation
                return '#kakaotalk-' . $contact_value;
                
            default:
                return '#';
        }
    }

    /**
     * Get platform icon URL
     *
     * @param string $platform Platform name
     * @return string Icon URL
     * @since 1.0.0
     */
    private function get_platform_icon_url($platform) {
        return CWP_Chat_Bubbles_Items_Manager::get_instance()->get_platform_icon_url($platform);
    }

    /**
     * Get CSS for different positions
     *
     * @param string $position Position setting
     * @return string CSS for position
     * @since 1.0.0
     */
    private function get_position_css($position) {
        switch ($position) {
            case 'bottom-left':
                return "
                    #chat-bubbles {
                        left: 15px !important;
                        right: auto !important;
                    }
                    #chat-bubbles .item-group,
                    #chat-bubbles .bubble-modal {
                        left: 0 !important;
                        right: auto !important;
                    }
                    #chat-bubbles .item-group::before {
                        left: 25px !important;
                        right: auto !important;
                    }
                ";
                
            case 'top-right':
                return "
                    #chat-bubbles {
                        top: 50px !important;
                        bottom: auto !important;
                    }
                    #chat-bubbles .item-group,
                    #chat-bubbles .bubble-modal {
                        top: calc(100% + 10px) !important;
                        bottom: auto !important;
                    }
                    #chat-bubbles .item-group::before {
                        top: -7px !important;
                        bottom: auto !important;
                        border-bottom: 8px solid #fff !important;
                        border-top: 8px solid transparent !important;
                    }
                ";
                
            case 'top-left':
                return "
                    #chat-bubbles {
                        top: 50px !important;
                        bottom: auto !important;
                        left: 15px !important;
                        right: auto !important;
                    }
                    #chat-bubbles .item-group,
                    #chat-bubbles .bubble-modal {
                        top: calc(100% + 10px) !important;
                        bottom: auto !important;
                        left: 0 !important;
                        right: auto !important;
                    }
                    #chat-bubbles .item-group::before {
                        top: -7px !important;
                        bottom: auto !important;
                        left: 25px !important;
                        right: auto !important;
                        border-bottom: 8px solid #fff !important;
                        border-top: 8px solid transparent !important;
                    }
                ";
                
            default:
                return '';
        }
    }
} 