<?php
/**
 * Options Page Class
 *
 * Handles WordPress admin interface
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles Options Page Class
 *
 * @since 1.0.0
 */
class CWP_Chat_Bubbles_Options_Page {

    /**
     * Instance of this class
     *
     * @var CWP_Chat_Bubbles_Options_Page
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
     * @since 1.0.0
     */
    private $items_manager;

    /**
     * Admin Renderer instance
     *
     * @var CWP_Chat_Bubbles_Admin_Renderer
     * @since 1.0.0
     */
    private $renderer;

    /**
     * Get instance
     *
     * @return CWP_Chat_Bubbles_Options_Page
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
        $this->renderer = new CWP_Chat_Bubbles_Admin_Renderer();
        $this->init();
    }

    /**
     * Initialize options page
     *
     * @since 1.0.0
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     * Add admin menu
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        $hook = add_menu_page(
            __('Chat Bubbles', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            __('Chat Bubbles', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'manage_options',
            'cwp-chat-bubbles',
            array($this, 'render_options_page'),
            'dashicons-format-chat',
            30
        );
        
        // Add security headers when loading our admin page
        add_action('load-' . $hook, array($this, 'add_security_headers'));
    }

    /**
     * Add security headers to admin page
     *
     * @since 1.0.0
     */
    public function add_security_headers() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // WordPress-compatible CSP for admin interface
        $csp_directives = array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // WordPress Media Library requires unsafe-eval for templates
            "style-src 'self' 'unsafe-inline'",  // WordPress admin requires inline styles
            "img-src 'self' data: https: blob:",  // blob: needed for media library previews
            "font-src 'self' data:",  // data: needed for WordPress admin fonts
            "connect-src 'self'",
            "worker-src 'self' blob:",  // blob: needed for media library workers
            "frame-ancestors 'none'"
        );
        
        header('Content-Security-Policy: ' . implode('; ', $csp_directives));
    }

    /**
     * Render options page
     *
     * @since 1.0.0
     */
    public function render_options_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submission for general settings
        if (isset($_POST['submit'])) {
            $this->handle_form_submission();
        }

        $options = $this->settings->get_options();
        $items = $this->items_manager->get_all_items();
        $supported_platforms = $this->items_manager->get_supported_platforms();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('CWP Chat Bubbles Settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h1>
            
            <?php 
            // Display admin notices (success/error messages)
            settings_errors(); 
            ?>
            
            <!-- Single form for all tabs to prevent data loss when switching tabs -->
            <form method="post" action="">
                <?php wp_nonce_field('cwp_chat_bubbles_settings', 'cwp_chat_bubbles_nonce'); ?>
                
                <div id="cwp-admin-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general-settings" class="nav-tab nav-tab-active"><?php esc_html_e('General Settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                        <a href="#chat-items" class="nav-tab"><?php esc_html_e('Chat Items', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                        <a href="#display-settings" class="nav-tab"><?php esc_html_e('Display Settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                    </nav>

                    <!-- General Settings Tab -->
                    <div id="general-settings" class="tab-content">
                        <?php $this->renderer->render_general_settings_tab($options); ?>
                    </div>

                <!-- Chat Items Tab -->
                <div id="chat-items" class="tab-content" style="display: none;">
                    <div class="cwp-items-header">
                        <div>
                            <h3><?php esc_html_e('Manage Chat Items', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h3>
                            <p class="description"><?php esc_html_e('Add, edit, or reorder your chat items. Changes are saved automatically.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                        </div>
                        <button type="button" class="button button-primary" id="add-new-item">
                            <?php esc_html_e('Add New Item', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                        </button>
                    </div>

                    <div id="cwp-items-container">
                        <?php $this->renderer->render_items_list($items); ?>
                    </div>
                </div>

                    <!-- Display Settings Tab -->
                    <div id="display-settings" class="tab-content" style="display: none;">
                        <?php $this->renderer->render_display_settings_tab($options); ?>
                    </div>
                </div>
                
                <!-- Single submit button for all tabs -->
                <div style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ddd; border-top: none;">
                    <?php submit_button(); ?>
                </div>
            </form>
            
            <!-- Add/Edit Item Modal (outside main form to prevent nesting) -->
            <?php $this->renderer->render_item_modal($supported_platforms); ?>
        </div>

        <style>
        .cwp-items-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .cwp-items-header h3 {
            margin: 0 0 5px 0;
        }
        .cwp-items-header .description {
            margin: 0 0 10px 0;
            color: #666;
            font-style: italic;
        }
        .cwp-items-list {
            border: 1px solid #ddd;
            background: #fff;
            min-height: 200px;
            padding: 15px;
        }
        .cwp-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            background: #f9f9f9;
            cursor: move;
        }
        .cwp-item:hover {
            background: #f0f0f0;
        }
        .cwp-item-drag {
            margin-right: 10px;
            cursor: grab;
        }
        .cwp-item-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }
        .cwp-item-info {
            flex: 1;
        }
        .cwp-item-actions {
            display: flex;
            gap: 5px;
        }
        .cwp-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 100000;
        }
        .cwp-modal-content {
            position: relative;
            background: #fff;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 4px;
        }
        .cwp-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cwp-modal-body {
            padding: 20px;
        }
        .cwp-modal-footer {
            padding: 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        .cwp-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }
        .nav-tab-wrapper {
            margin-bottom: 20px;
        }
        .tab-content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .cwp-empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        /* Enhanced form validation styles */
        .cwp-modal input.error, 
        .cwp-modal select.error {
            border-color: #d63638 !important;
            box-shadow: 0 0 0 1px #d63638 !important;
            background-color: #fbeaea;
        }
        
        .field-error {
            color: #d63638;
            font-size: 12px;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }
        
        .form-error {
            background: #fbeaea;
            border: 1px solid #d63638;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            color: #d63638;
            font-weight: 500;
        }
        
        /* Success state for valid fields */
        .cwp-modal input:valid:not(:placeholder-shown), 
        .cwp-modal select:valid {
            border-color: #46b450;
        }
        
        /* Focus states */
        .cwp-modal input:focus, 
        .cwp-modal select:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        
        /* Loading state for save button */
        #save-item:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Improve error field transitions */
        .cwp-modal input, 
        .cwp-modal select {
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }
        </style>

        <script>
        // Platform configuration for dynamic form updates
        window.platformConfigs = <?php echo json_encode($supported_platforms); ?>;
        </script>
        <?php
    }

    /**
     * Handle form submission
     *
     * @since 1.0.0
     */
    private function handle_form_submission() {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('cwp_chat_bubbles_settings', 'cwp_chat_bubbles_nonce');

        // Rate limiting: Check if too many requests in short time
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 10) {
            add_settings_error(
                'cwp_chat_bubbles_messages',
                'cwp_chat_bubbles_message',
                __('Too many requests. Please wait a moment before trying again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                'error'
            );
            return;
        }
        
        // Increment rate limit counter
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);

        if (isset($_POST['cwp_chat_bubbles_options'])) {
            $options = $_POST['cwp_chat_bubbles_options'];
            
            try {
                // Process settings update
                $this->settings->update_options($options);
                
                // Log successful settings update
                $this->log_admin_action('settings_updated', 'General settings updated successfully');
                
                // Always show success message when save completes without errors
                add_settings_error(
                    'cwp_chat_bubbles_messages',
                    'cwp_chat_bubbles_message',
                    __('Settings saved successfully!', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'success'
                );
            } catch (Exception $e) {
                // Log failed settings update
                $this->log_admin_action('settings_error', 'Failed to save general settings: ' . $e->getMessage());
                
                add_settings_error(
                    'cwp_chat_bubbles_messages',
                    'cwp_chat_bubbles_message',
                    __('Failed to save settings.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'error'
                );
            }
        }
    }

} 