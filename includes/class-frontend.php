<?php
/**
 * Frontend Class
 *
 * Handles frontend auto-injection and rendering
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles Frontend Class
 *
 * @since 1.0.0
 */
class CWP_Chat_Bubbles_Frontend {

    /**
     * Instance of this class
     *
     * @var CWP_Chat_Bubbles_Frontend
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
     * @return CWP_Chat_Bubbles_Frontend
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
     * Initialize frontend
     *
     * @since 1.0.0
     */
    private function init() {
        // Auto-inject chat bubbles via wp_footer
        add_action('wp_footer', array($this, 'render_chat_bubbles'));
        
        // Add shortcode support (optional)
        add_shortcode('cwp_chat_bubbles', array($this, 'shortcode_handler'));
    }

    /**
     * Render chat bubbles (auto-injection)
     *
     * @since 1.0.0
     */
    public function render_chat_bubbles() {
        // Check if plugin is enabled
        if (!$this->settings->is_enabled()) {
            return;
        }

        // Check if auto-loading is enabled
        if (!$this->settings->is_auto_load_enabled()) {
            return;
        }

        // Don't display in admin
        if (is_admin()) {
            return;
        }

        // Check if we have any enabled items
        $items_manager = CWP_Chat_Bubbles_Items_Manager::get_instance();
        $enabled_items = $items_manager->get_all_items(true); // Get enabled items only
        if (empty($enabled_items)) {
            return;
        }

        // Check mobile setting
        if (wp_is_mobile() && !$this->settings->get_option('load_on_mobile', true)) {
            return;
        }

        // Check excluded pages
        if ($this->is_current_page_excluded()) {
            return;
        }

        // Render the template
        $this->render_template($enabled_items);
    }

    /**
     * Shortcode handler
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     * @since 1.0.0
     */
    public function shortcode_handler($atts = array()) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'platforms' => '', // Comma-separated list of platforms to show
            'position' => '', // Override position
            'show_labels' => '', // Override show labels setting
        ), $atts, 'cwp_chat_bubbles');

        // Check if plugin is enabled
        if (!$this->settings->is_enabled()) {
            return '';
        }

        // Get enabled items
        $items_manager = CWP_Chat_Bubbles_Items_Manager::get_instance();
        $enabled_items = $items_manager->get_all_items(true); // Get enabled items only
        
        // Filter by shortcode platforms if specified
        if (!empty($atts['platforms'])) {
            $shortcode_platforms = array_map('trim', explode(',', $atts['platforms']));
            $enabled_items = array_filter($enabled_items, function($item) use ($shortcode_platforms) {
                return in_array($item['platform'], $shortcode_platforms);
            });
        }

        if (empty($enabled_items)) {
            return '';
        }

        // Start output buffering
        ob_start();
        
        // Render template with shortcode attributes
        $this->render_template($enabled_items, $atts);
        
        // Return captured output
        return ob_get_clean();
    }

    /**
     * Render chat bubbles template
     *
     * @param array $items Enabled items from custom table
     * @param array $override_settings Override settings for shortcode
     * @since 1.0.0
     */
    private function render_template($items, $override_settings = array()) {
        // Template variables
        $template_vars = array(
            'items' => $items,
            'settings' => array(
                'position' => !empty($override_settings['position']) ? $override_settings['position'] : $this->settings->get_option('position', 'bottom-right'),
                'main_button_color' => $this->settings->get_option('main_button_color', '#52BA00'),
                'animation_enabled' => $this->settings->get_option('animation_enabled', true),
                'show_labels' => !empty($override_settings['show_labels']) ? (bool) $override_settings['show_labels'] : $this->settings->should_show_labels()
            ),
            'support_icon' => CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg',
            'cancel_icon' => CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/cancel.svg'
        );

        // Load template
        $template_path = $this->locate_template('chat-bubbles.php');
        
        if ($template_path) {
            // Extract variables for template
            extract($template_vars);
            include $template_path;
        } else {
            // Fallback inline template
            $this->render_fallback_template($template_vars);
        }
    }

    /**
     * Locate template file
     *
     * @param string $template_name Template file name
     * @return string|false Template path or false if not found
     * @since 1.0.0
     */
    private function locate_template($template_name) {
        // Check theme for override
        $theme_template = locate_template(array(
            'cwp-chat-bubbles/' . $template_name,
            'chat-bubbles/' . $template_name,
            $template_name
        ));

        if ($theme_template) {
            return $theme_template;
        }

        // Check plugin templates directory
        $plugin_template = CWP_CHAT_BUBBLES_PLUGIN_DIR . 'templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }

    /**
     * Render fallback template (inline HTML)
     *
     * @param array $vars Template variables
     * @since 1.0.0
     */
    private function render_fallback_template($vars) {
        extract($vars);
        ?>
        <div id="chat-bubbles" class="cwp-chat-bubbles">
            <div class="chat-icon chat-btn-toggle">
                <img src="<?php echo esc_url($support_icon); ?>" alt="<?php esc_attr_e('Support', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>">
                <img src="<?php echo esc_url($cancel_icon); ?>" alt="<?php esc_attr_e('Close', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" class="close">
            </div>
            
            <div class="item-group">
                <?php foreach ($items as $item): ?>
                    <?php
                    $platform_url = $this->generate_platform_url($item['platform'], $item);
                    $platform_icon = $this->get_platform_icon_url($item['platform']);
                    $has_qr = !empty($item['qr_code_id']);
                    ?>
                    <a href="<?php echo esc_url($platform_url); ?>" 
                       class="chat-item" 
                       <?php if ($has_qr): ?>data-bubble-modal="modal-<?php echo esc_attr($item['id']); ?>"<?php endif; ?>
                       target="_blank" 
                       rel="noopener noreferrer">
                        <img src="<?php echo esc_url($platform_icon); ?>" alt="<?php echo esc_attr($item['label']); ?>">
                        <?php if ($settings['show_labels']): ?>
                            <span><?php echo esc_html($item['label']); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php foreach ($items as $item): ?>
                <?php if (!empty($item['qr_code_id'])): ?>
                    <?php $qr_image_url = wp_get_attachment_url($item['qr_code_id']); ?>
                    <?php if ($qr_image_url): ?>
                        <div id="modal-<?php echo esc_attr($item['id']); ?>" class="bubble-modal">
                            <div class="modal-body">
                                <button class="bubble-modal-close">
                                    <img src="<?php echo esc_url($cancel_icon); ?>" alt="<?php esc_attr_e('Close', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>">
                                </button>
                                <div class="qrcode">
                                    <h3><?php echo esc_html($item['label']); ?></h3>
                                    <img src="<?php echo esc_url($qr_image_url); ?>" alt="<?php echo esc_attr($item['label']); ?> QR Code">
                                </div>
                                <a href="<?php echo esc_url($this->generate_platform_url($item['platform'], $item)); ?>" 
                                   class="btn" 
                                   target="_blank" 
                                   rel="noopener noreferrer">
                                    <div class="icon">
                                        <img src="<?php echo esc_url($this->get_platform_icon_url($item['platform'])); ?>" alt="<?php echo esc_attr($item['label']); ?>">
                                    </div>
                                    <span><?php printf(__('Open %s', CWP_CHAT_BUBBLES_TEXT_DOMAIN), $item['label']); ?></span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Check if current page is excluded
     *
     * @return bool Whether current page is excluded
     * @since 1.0.0
     */
    private function is_current_page_excluded() {
        $excluded_pages = $this->settings->get_option('exclude_pages', array());
        
        if (empty($excluded_pages)) {
            return false;
        }

        if (is_page()) {
            $current_page_id = get_the_ID();
            return in_array($current_page_id, $excluded_pages);
        }

        return false;
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
                return 'https://zalo.me/' . $contact_value;
                
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
} 