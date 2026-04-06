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
     * Items Manager instance
     *
     * @var CWP_Chat_Bubbles_Items_Manager
     * @since 1.0.2
     */
    private $items_manager;

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
        $this->items_manager = CWP_Chat_Bubbles_Items_Manager::get_instance();
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
     * Render chat bubbles (auto-injection) - Optimized with unified data service
     *
     * @since 1.0.0
     */
    public function render_chat_bubbles() {
        $data_service = CWP_Chat_Bubbles_Data_Service::get_instance();
        
        // Use unified logic to check if should load (eliminates duplicate checks)
        if (!$data_service->should_load_on_current_page()) {
            return;
        }

        if ($this->should_use_lazy_autoload()) {
            $shell_data = $data_service->get_frontend_shell_data();
            $this->render_shell_template($shell_data);
            return;
        }

        // Get processed frontend data (single source of truth)
        $frontend_data = $data_service->get_frontend_data();
        if (false === $frontend_data) {
            return;
        }

        // Render the template with pre-processed data
        $this->render_template_optimized($frontend_data);
    }

    /**
     * Shortcode handler - Optimized with unified data service
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

        // Get processed frontend data
        $data_service = CWP_Chat_Bubbles_Data_Service::get_instance();
        $frontend_data = $data_service->get_frontend_data();
        
        if (false === $frontend_data) {
            return '';
        }
        
        // Filter by shortcode platforms if specified
        if (!empty($atts['platforms'])) {
            $shortcode_platforms = array_map('trim', explode(',', $atts['platforms']));
            $frontend_data['items'] = array_filter($frontend_data['items'], function($item) use ($shortcode_platforms) {
                return in_array($item['platform'], $shortcode_platforms);
            });
        }

        if (empty($frontend_data['items'])) {
            return '';
        }

        // Start output buffering
        ob_start();
        
        // Render template with shortcode attributes
        $this->render_template_optimized($frontend_data, $atts);
        
        // Return captured output
        return ob_get_clean();
    }

    /**
     * Render chat bubbles template with pre-processed data (optimized)
     *
     * @param array $frontend_data Pre-processed frontend data from data service
     * @param array $override_settings Override settings for shortcode
     * @since 1.0.0
     */
    private function render_template_optimized($frontend_data, $override_settings = array()) {
        // Use pre-processed data directly (no additional processing needed)
        $template_vars = $frontend_data;
        
        // Apply any override settings
        if (!empty($override_settings['position'])) {
            $template_vars['settings']['position'] = $override_settings['position'];
        }
        if (!empty($override_settings['show_labels'])) {
            $template_vars['settings']['show_labels'] = (bool) $override_settings['show_labels'];
        }

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
     * Render lazy shell template for auto-inject mode.
     *
     * @param array $shell_data Lightweight frontend shell data.
     * @since 1.1.1
     */
    private function render_shell_template($shell_data) {
        $template_path = $this->locate_template('chat-bubbles-shell.php');

        if ($template_path) {
            $settings = isset($shell_data['settings']) ? $shell_data['settings'] : array();
            $support_icon = isset($shell_data['support_icon']) ? $shell_data['support_icon'] : '';
            $cancel_icon = isset($shell_data['cancel_icon']) ? $shell_data['cancel_icon'] : '';
            $lazy_enabled = !empty($shell_data['lazy_enabled']);
            $prefetch_enabled = !empty($shell_data['prefetch_enabled']);
            $lazy_endpoint = isset($shell_data['lazy_endpoint']) ? $shell_data['lazy_endpoint'] : '';
            include $template_path;
            return;
        }

        $this->render_shell_fallback_template($shell_data);
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
     * Render minimal shell fallback template when custom template is missing.
     *
     * @param array $vars Template variables.
     * @since 1.1.1
     */
    private function render_shell_fallback_template($vars) {
        $settings = isset($vars['settings']) ? $vars['settings'] : array();
        $support_icon = isset($vars['support_icon']) ? $vars['support_icon'] : CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg';
        $cancel_icon = isset($vars['cancel_icon']) ? $vars['cancel_icon'] : CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/cancel.svg';
        $lazy_endpoint = isset($vars['lazy_endpoint']) ? $vars['lazy_endpoint'] : esc_url_raw(rest_url('cwp-chat-bubbles/v1/items'));
        $prefetch_enabled = !empty($vars['prefetch_enabled']);
        ?>
        <div id="chat-bubbles"
             class="cwp-chat-bubbles"
             data-position="<?php echo esc_attr($settings['position'] ?? 'bottom-right'); ?>"
             data-lazy="1"
             data-endpoint="<?php echo esc_url($lazy_endpoint); ?>"
             data-prefetch="<?php echo $prefetch_enabled ? '1' : '0'; ?>">
            <div class="chat-icon chat-btn-toggle" style="background-color: <?php echo esc_attr($settings['main_button_color'] ?? '#52BA00'); ?>">
                <img src="<?php echo esc_url($support_icon); ?>" alt="<?php esc_attr_e('Support', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" class="chat-icon-open">
                <img src="<?php echo esc_url($cancel_icon); ?>" alt="<?php esc_attr_e('Close', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" class="chat-icon-close">
            </div>
            <div class="item-group <?php echo empty($settings['show_labels']) ? 'no-labels' : ''; ?>"></div>
            <div class="cwp-chat-modals"></div>
        </div>
        <?php
    }

    /**
     * Determine whether lazy autoload is enabled for auto-inject rendering.
     *
     * @return bool
     * @since 1.1.1
     */
    private function should_use_lazy_autoload() {
        return (bool) apply_filters('cwp_chat_bubbles_lazy_autoload_enabled', true);
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
                    $platform_url = $this->items_manager->generate_platform_url($item['platform'], $item);
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
                                <a href="<?php echo esc_url($this->items_manager->generate_platform_url($item['platform'], $item)); ?>" 
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
     * Get platform icon URL
     *
     * @param string $platform Platform name
     * @return string Icon URL
     * @since 1.0.0
     */
    private function get_platform_icon_url($platform) {
        return $this->items_manager->get_platform_icon_url($platform);
    }
} 
