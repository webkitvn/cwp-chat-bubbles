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
        $this->init();
    }

    /**
     * Initialize options page
     *
     * @since 1.0.0
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_cwp_chat_bubbles_save_item', array($this, 'ajax_save_item'));
        add_action('wp_ajax_cwp_chat_bubbles_delete_item', array($this, 'ajax_delete_item'));
        add_action('wp_ajax_cwp_chat_bubbles_reorder_items', array($this, 'ajax_reorder_items'));
        add_action('wp_ajax_cwp_chat_bubbles_get_attachment_url', array($this, 'ajax_get_attachment_url'));
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
        $available_pages = $this->get_available_pages();
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
                        <a href="#advanced-settings" class="nav-tab"><?php esc_html_e('Advanced Settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                    </nav>

                    <!-- General Settings Tab -->
                    <div id="general-settings" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Plugin', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[enabled]" value="1" <?php checked($options['enabled']); ?>>
                                        <?php esc_html_e('Enable chat bubbles on the website', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Auto Load', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[auto_load]" value="1" <?php checked($options['auto_load']); ?>>
                                        <?php esc_html_e('Automatically display on all pages (recommended)', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('When enabled, chat bubbles will appear automatically. Disable this if you want to use shortcodes only.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Show Labels', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[show_labels]" value="1" <?php checked($options['show_labels']); ?>>
                                        <?php esc_html_e('Show text labels for all chat items', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('This setting applies to all chat items globally.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Custom Main Icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <div class="cwp-media-upload">
                                        <input type="hidden" name="cwp_chat_bubbles_options[custom_main_icon]" id="custom-main-icon" value="<?php echo esc_attr($options['custom_main_icon'] ?? 0); ?>">
                                        <button type="button" class="button" id="upload-main-icon">
                                            <?php esc_html_e('Upload Custom Icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <button type="button" class="button" id="remove-main-icon" style="display: <?php echo (isset($options['custom_main_icon']) && $options['custom_main_icon'] > 0) ? 'inline-block' : 'none'; ?>;">
                                            <?php esc_html_e('Remove Custom Icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <div id="main-icon-preview" style="margin-top: 10px; display: flex; justify-content: center; align-items: center; border-radius: 50%; width: 64px; height: 64px; background-color: <?php echo esc_attr($options['main_button_color'] ?? '#52BA00'); ?>;">
                                            <?php if (isset($options['custom_main_icon']) && $options['custom_main_icon'] > 0): ?>
                                                <?php $custom_icon_url = wp_get_attachment_url($options['custom_main_icon']); ?>
                                                <?php if ($custom_icon_url): ?>
                                                    <img src="<?php echo esc_url($custom_icon_url); ?>" alt="<?php esc_attr_e('Custom main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" style="width:80%; height:auto;">
                                                <?php else: ?>
                                                    <img src="<?php echo esc_url(CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg'); ?>" alt="<?php esc_attr_e('Default main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" style="width:80%; height:auto;">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <img src="<?php echo esc_url(CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg'); ?>" alt="<?php esc_attr_e('Default main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" style="width:80%; height:auto;">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="description">
                                        <?php esc_html_e('Upload a custom icon for the main chat button. Leave empty to use the default support icon. Recommended size: 64x64 pixels.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
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
                        <?php $this->render_items_list($items); ?>
                    </div>
                </div>

                    <!-- Display Settings Tab -->
                    <div id="display-settings" class="tab-content" style="display: none;">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Position', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <select name="cwp_chat_bubbles_options[position]">
                                        <option value="bottom-right" <?php selected($options['position'], 'bottom-right'); ?>><?php esc_html_e('Bottom Right', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                        <option value="bottom-left" <?php selected($options['position'], 'bottom-left'); ?>><?php esc_html_e('Bottom Left', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                        <option value="top-right" <?php selected($options['position'], 'top-right'); ?>><?php esc_html_e('Top Right', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                        <option value="top-left" <?php selected($options['position'], 'top-left'); ?>><?php esc_html_e('Top Left', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Position Offset', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Position Offset Settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 10px;">
                                            <label style="display: flex; align-items: center; gap: 5px;">
                                                <span style="min-width: 20px;"><?php esc_html_e('X:', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                                <input type="number" name="cwp_chat_bubbles_options[offset_x]" value="<?php echo esc_attr($options['offset_x'] ?? 0); ?>" min="-200" max="200" step="1" style="width: 80px;">
                                                <span style="color: #666;">px</span>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 5px;">
                                                <span style="min-width: 20px;"><?php esc_html_e('Y:', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                                <input type="number" name="cwp_chat_bubbles_options[offset_y]" value="<?php echo esc_attr($options['offset_y'] ?? 0); ?>" min="-200" max="200" step="1" style="width: 80px;">
                                                <span style="color: #666;">px</span>
                                            </label>
                                        </div>
                                        <p class="description">
                                            <?php esc_html_e('Fine-tune the position with pixel-level adjustments. Positive X moves right, negative moves left. Positive Y moves down, negative moves up.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Main Button Color', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <input type="color" name="cwp_chat_bubbles_options[main_button_color]" value="<?php echo esc_attr($options['main_button_color'] ?? '#52BA00'); ?>" class="color-field">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Animations', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[animation_enabled]" value="1" <?php checked($options['animation_enabled']); ?>>
                                        <?php esc_html_e('Enable animations and effects', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Advanced Settings Tab -->
                    <div id="advanced-settings" class="tab-content" style="display: none;">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Behavior', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Global behavior settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <p style="margin-top: 0;">
                                            <label for="cwp-chat-bubbles-default-state" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                <?php esc_html_e('Initial state', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                            </label>
                                            <select id="cwp-chat-bubbles-default-state" name="cwp_chat_bubbles_options[behavior][default_state]">
                                                <option value="closed" <?php selected($options['behavior']['default_state'], 'closed'); ?>><?php esc_html_e('Closed until the visitor clicks', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                <option value="open" <?php selected($options['behavior']['default_state'], 'open'); ?>><?php esc_html_e('Open by default on first render', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                            </select>
                                        </p>
                                        <div style="display: flex; gap: 24px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 8px;">
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-display-delay" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Display delay', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-display-delay"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[behavior][display_delay]"
                                                    value="<?php echo esc_attr($options['behavior']['display_delay']); ?>"
                                                    min="0"
                                                    max="30"
                                                    step="1"
                                                    style="width: 90px;"
                                                >
                                                <span style="color: #666;"><?php esc_html_e('seconds', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-scroll-trigger" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Scroll trigger', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-scroll-trigger"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[behavior][scroll_trigger_percent]"
                                                    value="<?php echo esc_attr($options['behavior']['scroll_trigger_percent']); ?>"
                                                    min="0"
                                                    max="100"
                                                    step="5"
                                                    style="width: 90px;"
                                                >
                                                <span style="color: #666;"><?php esc_html_e('% page depth', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                            </p>
                                        </div>
                                        <label style="display: block;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[behavior][dismiss_for_session]" value="1" <?php checked(!empty($options['behavior']['dismiss_for_session'])); ?>>
                                            <?php esc_html_e('Keep the bubble dismissed for the rest of the browser session after a visitor closes it', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                    </fieldset>
                                    <p class="description">
                                        <?php esc_html_e('Set a small number of engagement rules now so the frontend runtime can adopt them later without inventing new parsing rules. Use 0 seconds or 0% to disable the delay and scroll trigger.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Business Hours', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Business hours schedule settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <label style="display: block; margin-bottom: 12px;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[schedule][enabled]" value="1" <?php checked(!empty($options['schedule']['enabled'])); ?>>
                                            <?php esc_html_e('Only show the widget during scheduled working hours', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                        <div style="display: flex; gap: 24px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 12px;">
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-schedule-timezone" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Schedule timezone', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-schedule-timezone"
                                                    type="text"
                                                    name="cwp_chat_bubbles_options[schedule][timezone]"
                                                    value="<?php echo esc_attr($options['schedule']['timezone']); ?>"
                                                    class="regular-text"
                                                    placeholder="<?php echo esc_attr($this->get_site_timezone_string()); ?>"
                                                >
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-closed-behavior" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Outside business hours', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <select id="cwp-chat-bubbles-closed-behavior" name="cwp_chat_bubbles_options[schedule][closed_behavior]">
                                                    <option value="hide" <?php selected($options['schedule']['closed_behavior'], 'hide'); ?>><?php esc_html_e('Hide the widget', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                </select>
                                            </p>
                                        </div>
                                        <table class="widefat striped" style="max-width: 720px;">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e('Day', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                    <th><?php esc_html_e('Enabled', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                    <th><?php esc_html_e('Open', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                    <th><?php esc_html_e('Close', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($this->get_schedule_day_labels() as $day_key => $day_label) : ?>
                                                    <tr>
                                                        <td><strong><?php echo esc_html($day_label); ?></strong></td>
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                name="cwp_chat_bubbles_options[schedule][weekly_hours][<?php echo esc_attr($day_key); ?>][enabled]"
                                                                value="1"
                                                                <?php checked(!empty($options['schedule']['weekly_hours'][ $day_key ]['enabled'])); ?>
                                                            >
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="time"
                                                                name="cwp_chat_bubbles_options[schedule][weekly_hours][<?php echo esc_attr($day_key); ?>][open]"
                                                                value="<?php echo esc_attr($options['schedule']['weekly_hours'][ $day_key ]['open']); ?>"
                                                            >
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="time"
                                                                name="cwp_chat_bubbles_options[schedule][weekly_hours][<?php echo esc_attr($day_key); ?>][close]"
                                                                value="<?php echo esc_attr($options['schedule']['weekly_hours'][ $day_key ]['close']); ?>"
                                                            >
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </fieldset>
                                    <p class="description">
                                        <?php
                                        printf(
                                            /* translators: %s: timezone string */
                                            esc_html__('Leave the timezone blank to use the site timezone (%s). When the current time falls outside the enabled windows, this first version hides the widget entirely.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                                            esc_html($this->get_site_timezone_string())
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Device Visibility', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Device visibility settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[device_visibility][desktop]" value="1" <?php checked(!empty($options['device_visibility']['desktop'])); ?>>
                                            <?php esc_html_e('Show on desktop devices', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[device_visibility][tablet]" value="1" <?php checked(!empty($options['device_visibility']['tablet'])); ?>>
                                            <?php esc_html_e('Show on tablet devices', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                        <label style="display: block;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[device_visibility][mobile]" value="1" <?php checked(!empty($options['device_visibility']['mobile'])); ?>>
                                            <?php esc_html_e('Show on mobile devices', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                    </fieldset>
                                    <p class="description">
                                        <?php esc_html_e('Use these rules to decide which device categories can see the floating bubble. Existing installs keep their current mobile behavior until you save new values here.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Exclude Pages', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <?php if (!empty($available_pages)) : ?>
                                        <select
                                            name="cwp_chat_bubbles_options[exclude_pages][]"
                                            multiple
                                            size="<?php echo esc_attr(min(10, max(4, count($available_pages)))); ?>"
                                            style="min-width: 320px;"
                                        >
                                            <?php foreach ($available_pages as $page) : ?>
                                                <option
                                                    value="<?php echo esc_attr($page->ID); ?>"
                                                    <?php selected(in_array((int) $page->ID, $options['exclude_pages'], true)); ?>
                                                >
                                                    <?php echo esc_html($page->post_title ? $page->post_title : sprintf(__('Page #%d', CWP_CHAT_BUBBLES_TEXT_DOMAIN), (int) $page->ID)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else : ?>
                                        <p><?php esc_html_e('No pages are available to exclude yet.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                    <?php endif; ?>
                                    <p class="description">
                                        <?php esc_html_e('Selected pages will not auto-load the bubble. Hold Command on macOS or Ctrl on Windows to select multiple pages.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Appearance', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Appearance settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <div style="display: flex; gap: 24px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 12px;">
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-bubble-size" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Bubble size', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-bubble-size"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[appearance][bubble_size]"
                                                    value="<?php echo esc_attr($options['appearance']['bubble_size']); ?>"
                                                    min="48"
                                                    max="96"
                                                    step="1"
                                                    style="width: 90px;"
                                                >
                                                <span style="color: #666;">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-panel-width" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Panel width', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-panel-width"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[appearance][panel_width]"
                                                    value="<?php echo esc_attr($options['appearance']['panel_width']); ?>"
                                                    min="160"
                                                    max="320"
                                                    step="1"
                                                    style="width: 90px;"
                                                >
                                                <span style="color: #666;">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-modal-width" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Modal width', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-modal-width"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[appearance][modal_width]"
                                                    value="<?php echo esc_attr($options['appearance']['modal_width']); ?>"
                                                    min="240"
                                                    max="420"
                                                    step="1"
                                                    style="width: 90px;"
                                                >
                                                <span style="color: #666;">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-z-index" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Base z-index', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-z-index"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[appearance][z_index]"
                                                    value="<?php echo esc_attr($options['appearance']['z_index']); ?>"
                                                    min="100"
                                                    max="99999"
                                                    step="1"
                                                    style="width: 110px;"
                                                >
                                            </p>
                                        </div>
                                        <div style="display: flex; gap: 24px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 12px;">
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-panel-radius" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Panel radius', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-panel-radius"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[appearance][panel_radius]"
                                                    value="<?php echo esc_attr($options['appearance']['panel_radius']); ?>"
                                                    min="0"
                                                    max="24"
                                                    step="1"
                                                    style="width: 90px;"
                                                >
                                                <span style="color: #666;">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-modal-radius" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Modal radius', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-modal-radius"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[appearance][modal_radius]"
                                                    value="<?php echo esc_attr($options['appearance']['modal_radius']); ?>"
                                                    min="0"
                                                    max="24"
                                                    step="1"
                                                    style="width: 90px;"
                                                >
                                                <span style="color: #666;">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-item-padding-y" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Item padding Y', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-item-padding-y"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[appearance][item_padding_y]"
                                                    value="<?php echo esc_attr($options['appearance']['item_padding_y']); ?>"
                                                    min="0"
                                                    max="20"
                                                    step="1"
                                                    style="width: 90px;"
                                                >
                                                <span style="color: #666;">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-item-padding-x" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Item padding X', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-item-padding-x"
                                                    type="number"
                                                    name="cwp_chat_bubbles_options[appearance][item_padding_x]"
                                                    value="<?php echo esc_attr($options['appearance']['item_padding_x']); ?>"
                                                    min="0"
                                                    max="24"
                                                    step="1"
                                                    style="width: 90px;"
                                                >
                                                <span style="color: #666;">px</span>
                                            </p>
                                        </div>
                                        <p style="margin: 0;">
                                            <label for="cwp-chat-bubbles-label-text-color" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                <?php esc_html_e('Label text color', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                            </label>
                                            <input
                                                id="cwp-chat-bubbles-label-text-color"
                                                type="color"
                                                name="cwp_chat_bubbles_options[appearance][label_text_color]"
                                                value="<?php echo esc_attr($options['appearance']['label_text_color']); ?>"
                                            >
                                        </p>
                                    </fieldset>
                                    <p class="description">
                                        <?php esc_html_e('These values feed centralized CSS variables for the button, list panel, and QR modal. Defaults preserve the current look if you leave them unchanged.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="cwp-chat-bubbles-custom-css"><?php esc_html_e('Custom CSS', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <textarea
                                        id="cwp-chat-bubbles-custom-css"
                                        name="cwp_chat_bubbles_options[custom_css]"
                                        class="large-text code"
                                        rows="12"
                                        spellcheck="false"
                                        placeholder=".cwp-chat-bubbles { z-index: 9999; }"
                                    ><?php echo esc_textarea($options['custom_css']); ?></textarea>
                                    <p class="description">
                                        <?php esc_html_e('Add small, focused CSS overrides for the bubble UI. Unsafe patterns are stripped during save, so keep rules simple and self-contained.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Single submit button for all tabs -->
                <div style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ddd; border-top: none;">
                    <?php submit_button(); ?>
                </div>
            </form>
            
            <!-- Add/Edit Item Modal (outside main form to prevent nesting) -->
            <div id="cwp-item-modal" class="cwp-modal">
                <div class="cwp-modal-content">
                    <div class="cwp-modal-header">
                        <h3 id="modal-title"><?php esc_html_e('Add New Item', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h3>
                        <button type="button" class="cwp-modal-close">&times;</button>
                    </div>
                    <div class="cwp-modal-body">
                        <form id="cwp-item-form">
                            <input type="hidden" id="item-id" name="item_id" value="">
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="platform"><?php esc_html_e('Platform Type', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <select id="platform" name="platform" required>
                                            <option value=""><?php esc_html_e('Select Platform', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                            <?php foreach ($supported_platforms as $platform => $config): ?>
                                                <option value="<?php echo esc_attr($platform); ?>">
                                                    <?php echo esc_html($config['label']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="label"><?php esc_html_e('Display Label', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="label" name="label" class="regular-text" placeholder="<?php esc_attr_e('e.g., Business Support', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="contact-value" id="contact-label"><?php esc_html_e('Contact Info', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="contact-value" name="contact_value" class="regular-text" placeholder="" required>
                                        <p class="description" id="contact-description"></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="qr-code"><?php esc_html_e('QR Code', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="hidden" id="qr-code-id" name="qr_code_id" value="0">
                                        <button type="button" class="button" id="upload-qr-code">
                                            <?php esc_html_e('Upload QR Code', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <button type="button" class="button" id="remove-qr-code" style="display: none;">
                                            <?php esc_html_e('Remove QR Code', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <div id="qr-preview" style="margin-top: 10px;"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="enabled"><?php esc_html_e('Status', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="enabled" name="enabled" value="1" checked>
                                            <?php esc_html_e('Enable this item', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="cwp-modal-footer">
                        <button type="button" class="button" id="cancel-item"><?php esc_html_e('Cancel', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></button>
                        <button type="button" class="button button-primary" id="save-item"><?php esc_html_e('Save Item', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></button>
                    </div>
                </div>
            </div>
        </div>

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

    /**
     * Get a stable list of pages for the page exclusion control.
     *
     * @return array Available WordPress pages.
     * @since 1.0.3
     */
    private function get_available_pages() {
        return get_pages(
            array(
                'sort_column' => 'post_title',
                'sort_order'  => 'ASC',
            )
        );
    }

    /**
     * Get weekday labels for the schedule table.
     *
     * @return array<string, string> Day labels keyed by day slug.
     * @since 1.0.3
     */
    private function get_schedule_day_labels() {
        return array(
            'mon' => __('Monday', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'tue' => __('Tuesday', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'wed' => __('Wednesday', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'thu' => __('Thursday', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'fri' => __('Friday', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'sat' => __('Saturday', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'sun' => __('Sunday', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
        );
    }

    /**
     * Get the site timezone string used as the schedule fallback.
     *
     * @return string Timezone string.
     * @since 1.0.3
     */
    private function get_site_timezone_string() {
        $timezone = get_option('timezone_string', '');

        return '' !== $timezone ? $timezone : 'UTC';
    }

    /**
     * Render items list for admin interface
     *
     * @param array $items Items from custom table
     * @since 1.0.0
     */
    private function render_items_list($items) {
        if (empty($items)) {
            ?>
            <div class="cwp-empty-state">
                <h3><?php esc_html_e('No chat items found', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h3>
                <p><?php esc_html_e('Click "Add New Item" to create your first chat bubble item.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
            </div>
            <?php
            return;
        }

        $supported_platforms = $this->items_manager->get_supported_platforms();
        ?>
        <div class="cwp-items-list" id="sortable-items">
            <?php foreach ($items as $item): ?>
                <?php 
                $platform_config = isset($supported_platforms[$item['platform']]) ? $supported_platforms[$item['platform']] : null;
                $platform_label = $platform_config ? $platform_config['label'] : ucfirst($item['platform']);
                ?>
                <div class="cwp-item" 
                     data-item-id="<?php echo esc_attr($item['id']); ?>"
                     data-platform="<?php echo esc_attr($item['platform']); ?>"
                     data-label="<?php echo esc_attr($item['label']); ?>"
                     data-contact-value="<?php echo esc_attr($item['contact_value']); ?>"
                     data-enabled="<?php echo esc_attr($item['enabled']); ?>"
                     data-qr-code-id="<?php echo esc_attr($item['qr_code_id']); ?>">
                    <span class="cwp-item-drag dashicons dashicons-move"></span>
                    <img class="cwp-item-icon" src="<?php echo esc_url($this->get_platform_icon_url($item['platform'])); ?>" alt="<?php echo esc_attr($platform_label); ?>">
                    <div class="cwp-item-info">
                        <strong><?php echo esc_html($item['label']); ?></strong>
                        <br>
                        <small><?php echo esc_html($platform_label); ?>: <?php echo esc_html($item['contact_value']); ?></small>
                        <?php if (!empty($item['qr_code_id'])): ?>
                            <span class="dashicons dashicons-format-image" title="<?php esc_attr_e('Has QR Code', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div class="cwp-item-status">
                        <?php if ($item['enabled']): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: green;" title="<?php esc_attr_e('Enabled', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-dismiss" style="color: red;" title="<?php esc_attr_e('Disabled', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div class="cwp-item-actions">
                        <button type="button" class="button button-small edit-item" data-item-id="<?php echo esc_attr($item['id']); ?>">
                            <?php esc_html_e('Edit', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" class="button button-small delete-item" data-item-id="<?php echo esc_attr($item['id']); ?>">
                            <?php esc_html_e('Delete', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Log admin actions for audit trail
     *
     * @param string $action Action performed
     * @param string $message Additional message
     * @since 1.0.0
     */
    private function log_admin_action($action, $message = '') {
        $user_id = get_current_user_id();
        $user_info = get_userdata($user_id);
        $username = $user_info ? $user_info->user_login : 'unknown';
        $ip_address = $this->get_client_ip();
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'username' => $username,
            'ip_address' => $ip_address,
            'action' => $action,
            'message' => $message,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : ''
        );
        
        // Get existing log entries
        $log_entries = get_option('cwp_chat_bubbles_audit_log', array());
        
        // Filter out entries older than 30 days
        $log_entries = array_filter($log_entries, function($entry) {
            return isset($entry['timestamp']) && strtotime($entry['timestamp']) > strtotime('-30 days');
        });
        $log_entries = array_values($log_entries); // Re-index array
        
        // Add new entry
        array_unshift($log_entries, $log_entry);
        
        // Keep only last 100 entries to prevent database bloat
        $log_entries = array_slice($log_entries, 0, 100);
        
        // Save back to database (autoload disabled for performance)
        update_option('cwp_chat_bubbles_audit_log', $log_entries, false);
    }

    /**
     * Get client IP address safely
     *
     * @return string IP address
     * @since 1.0.0
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = sanitize_text_field($_SERVER[$key]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
    }

    /**
     * AJAX handler for saving item
     *
     * @since 1.0.0
     */
    public function ajax_save_item() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cwp_chat_bubbles_admin')) {
            $this->log_admin_action('security_violation', 'Invalid nonce for ajax_save_item');
            wp_die(__('Security check failed', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            $this->log_admin_action('security_violation', 'Insufficient permissions for ajax_save_item');
            wp_die(__('Insufficient permissions', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Rate limiting for AJAX requests
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_ajax_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 20) {
            $this->log_admin_action('rate_limited', 'AJAX request rate limited for ajax_save_item');
            wp_send_json_error(__('Too many requests. Please wait a moment before trying again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
        
        // Increment rate limit counter
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);

        $item_id = !empty($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
        $platform = sanitize_text_field($_POST['platform']);
        $label = sanitize_text_field($_POST['label']);
        $contact_value = sanitize_text_field($_POST['contact_value']);
        $qr_code_id = !empty($_POST['qr_code_id']) ? (int) $_POST['qr_code_id'] : 0;
        $enabled = !empty($_POST['enabled']) ? 1 : 0;

        // Enhanced validation
        if (empty($platform) || empty($label) || empty($contact_value)) {
            wp_send_json_error(__('Required fields are missing', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate platform is supported
        $supported_platforms = $this->items_manager->get_supported_platforms();
        if (!array_key_exists($platform, $supported_platforms)) {
            wp_send_json_error(__('Invalid platform selected', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate label length
        if (strlen($label) < 2 || strlen($label) > 50) {
            wp_send_json_error(__('Label must be between 2 and 50 characters', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate contact value format based on platform
        if (!$this->items_manager->validate_contact_value($platform, $contact_value)) {
            wp_send_json_error(__('Invalid contact value format for selected platform', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        $item_data = array(
            'platform' => $platform,
            'label' => $label,
            'contact_value' => $contact_value,
            'qr_code_id' => $qr_code_id,
            'enabled' => $enabled
        );

        if ($item_id > 0) {
            // Update existing item
            $result = $this->items_manager->update_item($item_id, $item_data);
            $action = 'updated';
        } else {
            // Create new item
            $result = $this->items_manager->create_item($item_data);
            $action = 'created';
        }

        if ($result) {
            $this->log_admin_action('item_' . $action, "Item {$action}: {$label} ({$platform})");
            wp_send_json_success(array(
                'message' => sprintf(__('Item %s successfully', CWP_CHAT_BUBBLES_TEXT_DOMAIN), $action),
                'items_html' => $this->get_items_list_html()
            ));
        } else {
            $this->log_admin_action('item_save_error', "Failed to {$action} item: {$label} ({$platform})");
            wp_send_json_error(__('Failed to save item', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX handler for deleting item
     *
     * @since 1.0.0
     */
    public function ajax_delete_item() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cwp_chat_bubbles_admin')) {
            $this->log_admin_action('security_violation', 'Invalid nonce for ajax_delete_item');
            wp_die(__('Security check failed', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            $this->log_admin_action('security_violation', 'Insufficient permissions for ajax_delete_item');
            wp_die(__('Insufficient permissions', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Rate limiting (shared with other AJAX endpoints)
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_ajax_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 20) {
            $this->log_admin_action('rate_limited', 'AJAX request rate limited for ajax_delete_item');
            wp_send_json_error(__('Too many requests. Please wait a moment before trying again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
        
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);

        $item_id = (int) $_POST['item_id'];

        if ($this->items_manager->delete_item($item_id)) {
            $this->log_admin_action('item_deleted', "Deleted item ID: {$item_id}");
            wp_send_json_success(array(
                'message' => __('Item deleted successfully', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                'items_html' => $this->get_items_list_html()
            ));
        } else {
            $this->log_admin_action('item_delete_error', "Failed to delete item ID: {$item_id}");
            wp_send_json_error(__('Failed to delete item', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX handler for reordering items
     *
     * @since 1.0.0
     */
    public function ajax_reorder_items() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cwp_chat_bubbles_admin')) {
            $this->log_admin_action('security_violation', 'Invalid nonce for ajax_reorder_items');
            wp_die(__('Security check failed', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            $this->log_admin_action('security_violation', 'Insufficient permissions for ajax_reorder_items');
            wp_die(__('Insufficient permissions', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Rate limiting (shared with other AJAX endpoints)
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_ajax_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 20) {
            $this->log_admin_action('rate_limited', 'AJAX request rate limited for ajax_reorder_items');
            wp_send_json_error(__('Too many requests. Please wait a moment before trying again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
        
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);

        $ordered_ids = array_map('intval', $_POST['ordered_ids']);

        // Validate that ordered_ids is reasonable (max 50 items)
        if (count($ordered_ids) > 50) {
            $this->log_admin_action('security_violation', 'Too many items in reorder request: ' . count($ordered_ids));
            wp_send_json_error(__('Too many items to reorder.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if ($this->items_manager->reorder_items($ordered_ids)) {
            $this->log_admin_action('items_reordered', 'Reordered ' . count($ordered_ids) . ' items');
            wp_send_json_success(__('Items reordered successfully', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        } else {
            $this->log_admin_action('reorder_error', 'Failed to reorder ' . count($ordered_ids) . ' items');
            wp_send_json_error(__('Failed to reorder items', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
    }

    /**
     * Get items list HTML for AJAX responses
     *
     * @return string Items list HTML
     * @since 1.0.0
     */
    private function get_items_list_html() {
        ob_start();
        $items = $this->items_manager->get_all_items();
        $this->render_items_list($items);
        return ob_get_clean();
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

    /**
     * AJAX handler for getting attachment URL
     *
     * @since 1.0.0
     */
    public function ajax_get_attachment_url() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cwp_chat_bubbles_admin')) {
            $this->log_admin_action('security_violation', 'Invalid nonce for ajax_get_attachment_url');
            wp_die(__('Security check failed', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            $this->log_admin_action('security_violation', 'Insufficient permissions for ajax_get_attachment_url');
            wp_die(__('Insufficient permissions', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Rate limiting for AJAX requests (shared with other AJAX endpoints)
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_ajax_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 20) {
            $this->log_admin_action('rate_limited', 'AJAX request rate limited for ajax_get_attachment_url');
            wp_die(__('Too many requests. Please wait a moment before trying again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
        
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);

        $attachment_id = !empty($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        
        if ($attachment_id > 0) {
            // Verify attachment exists and is actually an image
            $attachment = get_post($attachment_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                // Enhanced file validation
                $file_path = get_attached_file($attachment_id);
                $mime_type = get_post_mime_type($attachment_id);
                
                // Check if it's an image
                $allowed_mime_types = array(
                    'image/jpeg',
                    'image/jpg', 
                    'image/png',
                    'image/gif',
                    'image/webp'
                );
                
                if (!in_array($mime_type, $allowed_mime_types)) {
                    $this->log_admin_action('security_violation', "Invalid file type uploaded: {$mime_type}");
                    wp_send_json_error(__('Invalid file type. Only images are allowed.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
                }
                
                // Check file size (max 2MB for QR codes)
                if ($file_path && file_exists($file_path)) {
                    $file_size = filesize($file_path);
                    if ($file_size > 2 * 1024 * 1024) { // 2MB
                        $this->log_admin_action('security_violation', "File too large: " . number_format($file_size / 1024 / 1024, 2) . "MB");
                        wp_send_json_error(__('File too large. Maximum size is 2MB.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
                    }
                }
                
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    $this->log_admin_action('attachment_retrieved', "Retrieved attachment URL for ID: {$attachment_id}");
                    wp_send_json_success(array('url' => $url));
                }
            }
        }
        
        $this->log_admin_action('invalid_attachment', "Invalid attachment ID requested: {$attachment_id}");
        wp_send_json_error(__('Invalid attachment ID', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
    }
} 
