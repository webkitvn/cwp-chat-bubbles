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

        $options = $this->settings->get_options();
        $items = $this->items_manager->get_all_items();
        $supported_platforms = $this->items_manager->get_supported_platforms();
        $available_pages = $this->get_available_pages();
        $available_post_types = $this->get_available_post_types();
        ?>
        <div class="wrap cwp-admin-wrapper">
            <h1 class="wp-heading-inline"><?php esc_html_e('Chat Bubble Settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h1>
            <a href="#chat-items" class="page-title-action" id="cwp-header-add-item"><?php esc_html_e('Add Contact Method', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
            <hr class="wp-header-end">

            <?php settings_errors(); ?>
            
            <!-- Single form for all tabs to prevent data loss when switching tabs -->
            <form method="post" action="options.php">
                <?php settings_fields('cwp_chat_bubbles_settings'); ?>
                
                <div id="cwp-admin-tabs">
                    <nav class="nav-tab-wrapper wp-clearfix">
                        <a href="#general-settings" class="nav-tab nav-tab-active"><?php esc_html_e('General', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                        <a href="#chat-items" class="nav-tab"><?php esc_html_e('Contact Methods', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                        <a href="#display-settings" class="nav-tab"><?php esc_html_e('Display', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                        <a href="#advanced-settings" class="nav-tab"><?php esc_html_e('Advanced', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                    </nav>

                    <!-- General Settings Tab -->
                    <div id="general-settings" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Show Chat Bubble', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[enabled]" value="1" <?php checked($options['enabled']); ?>>
                                        <?php esc_html_e('Show the chat bubble on your site', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Show Automatically', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[auto_load]" value="1" <?php checked($options['auto_load']); ?>>
                                        <?php esc_html_e('Show the chat bubble automatically across your site', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('Turn this off if you only want to place the chat bubble with a shortcode.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Show Contact Names', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[show_labels]" value="1" <?php checked($options['show_labels']); ?>>
                                        <?php esc_html_e('Show a text label next to each contact method', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('This changes every contact method at once.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Main Button Icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <div class="cwp-media-upload">
                                        <input type="hidden" name="cwp_chat_bubbles_options[custom_main_icon]" id="custom-main-icon" value="<?php echo esc_attr($options['custom_main_icon'] ?? 0); ?>">
                                        <button type="button" class="button" id="upload-main-icon">
                                            <?php esc_html_e('Upload Icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <button type="button" class="button" id="remove-main-icon" style="display: <?php echo (isset($options['custom_main_icon']) && $options['custom_main_icon'] > 0) ? 'inline-block' : 'none'; ?>;">
                                            <?php esc_html_e('Remove Icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <div id="main-icon-preview" class="cwp-main-icon-preview" style="--cwp-preview-color: <?php echo esc_attr($options['main_button_color'] ?? '#52BA00'); ?>;">
                                            <?php if (isset($options['custom_main_icon']) && $options['custom_main_icon'] > 0): ?>
                                                <?php $custom_icon_url = wp_get_attachment_url($options['custom_main_icon']); ?>
                                                <?php if ($custom_icon_url): ?>
                                                    <img src="<?php echo esc_url($custom_icon_url); ?>" alt="<?php esc_attr_e('Custom main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>">
                                                <?php else: ?>
                                                    <img src="<?php echo esc_url(CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg'); ?>" alt="<?php esc_attr_e('Default main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <img src="<?php echo esc_url(CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg'); ?>" alt="<?php esc_attr_e('Default main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="description">
                                        <?php esc_html_e('Upload an icon for the main chat button. Leave this empty to keep the default icon. Recommended size: 64x64 pixels.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                <!-- Chat Items Tab -->
                <div id="chat-items" class="tab-content" style="display: none;">
                    <div class="cwp-items-header">
                        <div>
                            <h3><?php esc_html_e('Contact Methods', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h3>
                            <p class="description"><?php esc_html_e('Add, edit, or reorder the ways people can contact you. Changes save automatically.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                        </div>
                        <button type="button" class="button" id="add-new-item">
                            <?php esc_html_e('Add Contact Method', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                <th scope="row"><?php esc_html_e('Move the Bubble', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Position adjustments', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 10px;">
                                            <label style="display: flex; align-items: center; gap: 5px;">
                                                <span style="min-width: 20px;"><?php esc_html_e('X:', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                                <input type="number" name="cwp_chat_bubbles_options[offset_x]" value="<?php echo esc_attr($options['offset_x'] ?? 0); ?>" min="-200" max="200" step="1" style="width: 80px;">
                                                <span class="cwp-input-suffix">px</span>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 5px;">
                                                <span style="min-width: 20px;"><?php esc_html_e('Y:', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                                <input type="number" name="cwp_chat_bubbles_options[offset_y]" value="<?php echo esc_attr($options['offset_y'] ?? 0); ?>" min="-200" max="200" step="1" style="width: 80px;">
                                                <span class="cwp-input-suffix">px</span>
                                            </label>
                                        </div>
                                        <p class="description">
                                            <?php esc_html_e('Move the bubble a few pixels if it overlaps other parts of your site. Positive X moves it right, positive Y moves it down.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Bubble Color', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <input type="color" name="cwp_chat_bubbles_options[main_button_color]" value="<?php echo esc_attr($options['main_button_color'] ?? '#52BA00'); ?>" class="color-field">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Motion', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[animation_enabled]" value="1" <?php checked($options['animation_enabled']); ?>>
                                        <?php esc_html_e('Use animation when the bubble opens or moves', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Advanced Settings Tab -->
                    <div id="advanced-settings" class="tab-content" style="display: none;">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('When to Show the Bubble', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Opening behavior settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <p style="margin-top: 0;">
                                            <label for="cwp-chat-bubbles-default-state" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                <?php esc_html_e('When the bubble first appears', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                            </label>
                                            <select id="cwp-chat-bubbles-default-state" name="cwp_chat_bubbles_options[behavior][default_state]">
                                                <option value="closed" <?php selected($options['behavior']['default_state'], 'closed'); ?>><?php esc_html_e('Keep it closed until someone clicks', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                <option value="open" <?php selected($options['behavior']['default_state'], 'open'); ?>><?php esc_html_e('Open it right away', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                            </select>
                                        </p>
                                        <div style="display: flex; gap: 24px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 8px;">
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-display-delay" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Wait before showing', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                <span class="cwp-input-suffix"><?php esc_html_e('seconds', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-scroll-trigger" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Show after scrolling', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                <span class="cwp-input-suffix"><?php esc_html_e('% of the page', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                            </p>
                                        </div>
                                        <label style="display: block;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[behavior][dismiss_for_session]" value="1" <?php checked(!empty($options['behavior']['dismiss_for_session'])); ?>>
                                            <?php esc_html_e('Keep the bubble hidden until the visitor closes their browser tab', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                    </fieldset>
                                    <p class="description">
                                        <?php esc_html_e('Use these settings to decide when the bubble appears. Enter 0 to turn off the delay or scroll trigger.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Business Hours', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Working hours settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <label style="display: block; margin-bottom: 12px;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[schedule][enabled]" value="1" <?php checked(!empty($options['schedule']['enabled'])); ?>>
                                            <?php esc_html_e('Only show the chat bubble during these hours', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                        <div style="display: flex; gap: 24px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 12px;">
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-schedule-timezone" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Timezone', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                    <?php esc_html_e('Outside these hours', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <select id="cwp-chat-bubbles-closed-behavior" name="cwp_chat_bubbles_options[schedule][closed_behavior]">
                                                    <option value="hide" <?php selected($options['schedule']['closed_behavior'], 'hide'); ?>><?php esc_html_e('Hide the chat bubble', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                </select>
                                            </p>
                                        </div>
                                        <table class="widefat fixed striped" role="grid" style="max-width: 820px;">
                                            <thead>
                                                <tr>
                                                    <th scope="col"><?php esc_html_e('Day', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                    <th scope="col"><?php esc_html_e('Enabled', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                    <th scope="col"><?php esc_html_e('Open', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                    <th scope="col"><?php esc_html_e('Close', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
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
                                                                class="regular-text"
                                                            >
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="time"
                                                                name="cwp_chat_bubbles_options[schedule][weekly_hours][<?php echo esc_attr($day_key); ?>][close]"
                                                                value="<?php echo esc_attr($options['schedule']['weekly_hours'][ $day_key ]['close']); ?>"
                                                                class="regular-text"
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
                                            esc_html__('Leave this blank to use your site timezone (%s). Outside these hours, the chat bubble stays hidden.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                                            esc_html($this->get_site_timezone_string())
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Show On These Devices', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Device visibility settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[device_visibility][desktop]" value="1" <?php checked(!empty($options['device_visibility']['desktop'])); ?>>
                                            <?php esc_html_e('Desktop', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[device_visibility][tablet]" value="1" <?php checked(!empty($options['device_visibility']['tablet'])); ?>>
                                            <?php esc_html_e('Tablet', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                        <label style="display: block;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[device_visibility][mobile]" value="1" <?php checked(!empty($options['device_visibility']['mobile'])); ?>>
                                            <?php esc_html_e('Mobile', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                    </fieldset>
                                    <p class="description">
                                        <?php esc_html_e('Choose which screen sizes can see the chat bubble.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Hide On These Pages', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
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
                                        <p><?php esc_html_e('There are no pages to choose from yet.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                    <?php endif; ?>
                                    <p class="description">
                                        <?php esc_html_e('The chat bubble will stay hidden on the pages you select here. Hold Command on macOS or Ctrl on Windows to choose more than one page.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Choose Where the Bubble Appears', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Show or hide by page type settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <p style="margin-top: 0; margin-bottom: 16px;">
                                            <label for="cwp-chat-bubbles-targeting-operator" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                <?php esc_html_e('If you choose more than one rule', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                            </label>
                                            <select id="cwp-chat-bubbles-targeting-operator" name="cwp_chat_bubbles_options[targeting][operator]">
                                                <option value="all" <?php selected($options['targeting']['operator'], 'all'); ?>><?php esc_html_e('Only show it when all selected rules match', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                <option value="any" <?php selected($options['targeting']['operator'], 'any'); ?>><?php esc_html_e('Show it when any selected rule matches', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                            </select>
                                        </p>
                                        <table class="widefat striped" style="max-width: 900px; margin-bottom: 12px;">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e('Page group', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                    <th><?php esc_html_e('Show on', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                    <th><?php esc_html_e('Hide on', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="vertical-align: top;">
                                                        <strong><?php esc_html_e('Specific pages', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></strong>
                                                        <p class="description" style="margin: 6px 0 0;">
                                                            <?php esc_html_e('Choose individual pages by name.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                        </p>
                                                    </td>
                                                    <td style="vertical-align: top;">
                                                        <?php if (!empty($available_pages)) : ?>
                                                            <select
                                                                name="cwp_chat_bubbles_options[targeting][rules][pages][include][]"
                                                                multiple
                                                                size="<?php echo esc_attr(min(8, max(4, count($available_pages)))); ?>"
                                                                style="min-width: 220px;"
                                                            >
                                                                <?php foreach ($available_pages as $page) : ?>
                                                                    <option
                                                                        value="<?php echo esc_attr($page->ID); ?>"
                                                                        <?php selected(in_array((int) $page->ID, $options['targeting']['rules']['pages']['include'], true)); ?>
                                                                    >
                                                                        <?php echo esc_html($page->post_title ? $page->post_title : sprintf(__('Page #%d', CWP_CHAT_BUBBLES_TEXT_DOMAIN), (int) $page->ID)); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else : ?>
                                                            <p><?php esc_html_e('There are no pages to choose from yet.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="vertical-align: top;">
                                                        <?php if (!empty($available_pages)) : ?>
                                                            <select
                                                                name="cwp_chat_bubbles_options[targeting][rules][pages][exclude][]"
                                                                multiple
                                                                size="<?php echo esc_attr(min(8, max(4, count($available_pages)))); ?>"
                                                                style="min-width: 220px;"
                                                            >
                                                                <?php foreach ($available_pages as $page) : ?>
                                                                    <option
                                                                        value="<?php echo esc_attr($page->ID); ?>"
                                                                        <?php selected(in_array((int) $page->ID, $options['targeting']['rules']['pages']['exclude'], true)); ?>
                                                                    >
                                                                        <?php echo esc_html($page->post_title ? $page->post_title : sprintf(__('Page #%d', CWP_CHAT_BUBBLES_TEXT_DOMAIN), (int) $page->ID)); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else : ?>
                                                            <p><?php esc_html_e('There are no pages to choose from yet.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: top;">
                                                        <strong><?php esc_html_e('Content types', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></strong>
                                                        <p class="description" style="margin: 6px 0 0;">
                                                            <?php esc_html_e('Choose broad areas like pages, posts, or custom content types.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                        </p>
                                                    </td>
                                                    <td style="vertical-align: top;">
                                                        <?php if (!empty($available_post_types)) : ?>
                                                            <select
                                                                name="cwp_chat_bubbles_options[targeting][rules][post_types][include][]"
                                                                multiple
                                                                size="<?php echo esc_attr(min(8, max(4, count($available_post_types)))); ?>"
                                                                style="min-width: 220px;"
                                                            >
                                                                <?php foreach ($available_post_types as $post_type) : ?>
                                                                    <option
                                                                        value="<?php echo esc_attr($post_type->name); ?>"
                                                                        <?php selected(in_array($post_type->name, $options['targeting']['rules']['post_types']['include'], true)); ?>
                                                                    >
                                                                        <?php echo esc_html($post_type->labels->singular_name ?: $post_type->label ?: $post_type->name); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else : ?>
                                                            <p><?php esc_html_e('There are no public content types to choose from yet.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="vertical-align: top;">
                                                        <?php if (!empty($available_post_types)) : ?>
                                                            <select
                                                                name="cwp_chat_bubbles_options[targeting][rules][post_types][exclude][]"
                                                                multiple
                                                                size="<?php echo esc_attr(min(8, max(4, count($available_post_types)))); ?>"
                                                                style="min-width: 220px;"
                                                            >
                                                                <?php foreach ($available_post_types as $post_type) : ?>
                                                                    <option
                                                                        value="<?php echo esc_attr($post_type->name); ?>"
                                                                        <?php selected(in_array($post_type->name, $options['targeting']['rules']['post_types']['exclude'], true)); ?>
                                                                    >
                                                                        <?php echo esc_html($post_type->labels->singular_name ?: $post_type->label ?: $post_type->name); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else : ?>
                                                            <p><?php esc_html_e('There are no public content types to choose from yet.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table class="widefat striped" style="max-width: 900px;">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e('Special page', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                    <th><?php esc_html_e('Show or hide', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($this->get_targeting_special_page_labels() as $special_key => $special_label) : ?>
                                                    <tr>
                                                        <td><strong><?php echo esc_html($special_label); ?></strong></td>
                                                        <td>
                                                            <select name="cwp_chat_bubbles_options[targeting][rules][special_pages][<?php echo esc_attr($special_key); ?>]">
                                                                <option value="ignore" <?php selected($options['targeting']['rules']['special_pages'][ $special_key ], 'ignore'); ?>><?php esc_html_e('Use the other rules', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                                <option value="include" <?php selected($options['targeting']['rules']['special_pages'][ $special_key ], 'include'); ?>><?php esc_html_e('Always show', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                                <option value="exclude" <?php selected($options['targeting']['rules']['special_pages'][ $special_key ], 'exclude'); ?>><?php esc_html_e('Always hide', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </fieldset>
                                    <p class="description">
                                        <?php esc_html_e('Use these rules to choose where the bubble appears on your site. Hold Command on macOS or Ctrl on Windows to choose more than one item.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                <span class="cwp-input-suffix">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-panel-width" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Contact List Width', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                <span class="cwp-input-suffix">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-modal-width" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('QR Pop-up Width', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                <span class="cwp-input-suffix">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-z-index" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Bring the Bubble Forward', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                    <?php esc_html_e('Round the Contact List Corners', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                <span class="cwp-input-suffix">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-modal-radius" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Round the QR Pop-up Corners', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                <span class="cwp-input-suffix">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-item-padding-y" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Top and Bottom Spacing', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                <span class="cwp-input-suffix">px</span>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-item-padding-x" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Left and Right Spacing', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                                <span class="cwp-input-suffix">px</span>
                                            </p>
                                        </div>
                                        <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-label-text-color" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Contact Name Color', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
                                        <?php esc_html_e('Change how the bubble, contact list, and QR pop-up look. Leave these as they are to keep the current design.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Share Click Data', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Tracking settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <label style="display: block; margin-bottom: 12px;">
                                            <input type="checkbox" name="cwp_chat_bubbles_options[analytics][enabled]" value="1" <?php checked(!empty($options['analytics']['enabled'])); ?>>
                                            <?php esc_html_e('Send bubble clicks to your analytics tool', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                        <div style="display: flex; gap: 24px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 8px;">
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-analytics-provider" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Analytics tool', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <select id="cwp-chat-bubbles-analytics-provider" name="cwp_chat_bubbles_options[analytics][provider]">
                                                    <option value="none" <?php selected($options['analytics']['provider'], 'none'); ?>><?php esc_html_e('Do not send this data anywhere else', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                    <option value="ga4" <?php selected($options['analytics']['provider'], 'ga4'); ?>><?php esc_html_e('Google Analytics 4', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                    <option value="gtm" <?php selected($options['analytics']['provider'], 'gtm'); ?>><?php esc_html_e('Google Tag Manager', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
                                                </select>
                                            </p>
                                            <p style="margin: 0;">
                                                <label for="cwp-chat-bubbles-event-prefix" style="display: block; font-weight: 600; margin-bottom: 6px;">
                                                    <?php esc_html_e('Start event names with', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                                </label>
                                                <input
                                                    id="cwp-chat-bubbles-event-prefix"
                                                    type="text"
                                                    name="cwp_chat_bubbles_options[analytics][event_prefix]"
                                                    value="<?php echo esc_attr($options['analytics']['event_prefix']); ?>"
                                                    class="regular-text"
                                                    placeholder="cwp_chat_bubbles"
                                                >
                                            </p>
                                        </div>
                                    </fieldset>
                                    <p class="description">
                                        <?php esc_html_e('Use this only if your site already sends data to Google Analytics 4 or Google Tag Manager and you want bubble clicks included too.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="cwp-chat-bubbles-custom-css"><?php esc_html_e('Add Your Own Styling (CSS)', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
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
                                        <?php esc_html_e('Add small CSS tweaks for the chat bubble. Unsafe code is removed when you save, so keep these rules simple.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Single submit button for all tabs -->
                <div class="cwp-settings-submit">
                    <?php submit_button(); ?>
                </div>
            </form>
            
            <div id="cwp-item-modal-inline" class="cwp-item-thickbox" style="display: none;">
                <form id="cwp-item-form">
                    <input type="hidden" id="item-id" name="item_id" value="">

                    <h2 id="modal-title"><?php esc_html_e('Add Contact Method', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="platform"><?php esc_html_e('Chat App', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <select id="platform" name="platform" required>
                                    <option value=""><?php esc_html_e('Choose a chat app', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
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
                                <label for="label"><?php esc_html_e('Name', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <input type="text" id="label" name="label" class="regular-text" placeholder="<?php esc_attr_e('e.g. Sales team', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="contact-value" id="contact-label"><?php esc_html_e('Contact Details', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
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
                                <label for="interaction-mode"><?php esc_html_e('How This Contact Method Opens', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <select id="interaction-mode" name="interaction_mode">
                                    <?php foreach ($this->get_item_interaction_mode_labels() as $mode => $mode_label) : ?>
                                        <option value="<?php echo esc_attr($mode); ?>" <?php echo 'auto' === $mode ? 'selected' : ''; ?>>
                                            <?php echo esc_html($mode_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Choose whether people go straight into chat or see the QR code first when one is available.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="prefill-message"><?php esc_html_e('Starting Message', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <textarea
                                    id="prefill-message"
                                    name="prefill_message"
                                    class="large-text"
                                    rows="4"
                                    maxlength="500"
                                    placeholder="<?php esc_attr_e('Optional message to start the chat with.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"
                                ></textarea>
                                <p class="description">
                                    <?php esc_html_e('Use this when you want supported chat apps to open with a ready-made message.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="enabled"><?php esc_html_e('Availability', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enabled" name="enabled" value="1" checked>
                                    <?php esc_html_e('Show this contact method', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </form>
                <div class="cwp-modal-footer">
                    <button type="button" class="button" id="cancel-item"><?php esc_html_e('Cancel', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button button-primary" id="save-item"><?php esc_html_e('Save Contact Method', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></button>
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
     * Get a stable list of public post types for contextual targeting controls.
     *
     * @return array Available public post type objects.
     * @since 1.0.3
     */
    private function get_available_post_types() {
        $post_types = get_post_types(
            array(
                'public' => true,
            ),
            'objects'
        );

        if (!is_array($post_types)) {
            return array();
        }

        unset($post_types['attachment']);

        uasort(
            $post_types,
            function ($left, $right) {
                $left_label = $left->labels->singular_name ?: $left->label ?: $left->name;
                $right_label = $right->labels->singular_name ?: $right->label ?: $right->name;

                return strcasecmp($left_label, $right_label);
            }
        );

        return $post_types;
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
     * Get display labels for special-page contextual targeting controls.
     *
     * @return array<string, string> Special-page labels keyed by slug.
     * @since 1.0.3
     */
    private function get_targeting_special_page_labels() {
        return array(
            'front_page' => __('Front page', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'blog_index' => __('Blog index', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'search' => __('Search results', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            '404' => __('404 pages', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'archive' => __('Archive views', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
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
     * Get display labels for per-item interaction modes.
     *
     * @return array<string, string> Interaction mode labels keyed by mode.
     * @since 1.0.3
     */
    private function get_item_interaction_mode_labels() {
        return array(
            'auto' => __('Use the default behavior', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'direct_link' => __('Open chat right away', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
            'qr_modal' => __('Show the QR code first', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
        );
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
                <h3><?php esc_html_e('No contact methods yet', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h3>
                <p><?php esc_html_e('Add your first contact method so visitors know how to reach you.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
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
                $behavior = $this->items_manager->get_item_behavior_settings($item);
                $interaction_labels = $this->get_item_interaction_mode_labels();
                $interaction_label = isset($interaction_labels[$behavior['interaction_mode']])
                    ? $interaction_labels[$behavior['interaction_mode']]
                    : $interaction_labels['auto'];
                $prefill_preview = '' !== $behavior['prefill_message']
                    ? substr($behavior['prefill_message'], 0, 60)
                    : '';
                ?>
                <div class="cwp-item" 
                     data-item-id="<?php echo esc_attr($item['id']); ?>"
                     data-platform="<?php echo esc_attr($item['platform']); ?>"
                     data-label="<?php echo esc_attr($item['label']); ?>"
                     data-contact-value="<?php echo esc_attr($item['contact_value']); ?>"
                     data-enabled="<?php echo esc_attr($item['enabled']); ?>"
                     data-qr-code-id="<?php echo esc_attr($item['qr_code_id']); ?>"
                     data-interaction-mode="<?php echo esc_attr($behavior['interaction_mode']); ?>"
                     data-prefill-message="<?php echo esc_attr($behavior['prefill_message']); ?>">
                    <span class="cwp-item-drag dashicons dashicons-move"></span>
                    <img class="cwp-item-icon" src="<?php echo esc_url($this->get_platform_icon_url($item['platform'])); ?>" alt="<?php echo esc_attr($platform_label); ?>">
                    <div class="cwp-item-info">
                        <strong><?php echo esc_html($item['label']); ?></strong>
                        <br>
                        <small><?php echo esc_html($platform_label); ?>: <?php echo esc_html($item['contact_value']); ?></small>
                        <?php if (!empty($item['qr_code_id'])): ?>
                            <span class="dashicons dashicons-format-image" title="<?php esc_attr_e('QR code available', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"></span>
                        <?php endif; ?>
                        <?php if ('auto' !== $behavior['interaction_mode'] || '' !== $prefill_preview) : ?>
                            <br>
                            <small>
                                <?php
                                printf(
                                    /* translators: %s: interaction mode label */
                                    esc_html__('Opens: %s', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                                    esc_html($interaction_label)
                                );
                                ?>
                                <?php if ('' !== $prefill_preview) : ?>
                                    <?php echo esc_html(' | ' . sprintf(__('Message starts with: %s', CWP_CHAT_BUBBLES_TEXT_DOMAIN), $prefill_preview)); ?>
                                <?php endif; ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="cwp-item-status">
                        <?php if ($item['enabled']): ?>
                            <span class="dashicons dashicons-yes-alt cwp-item-status-icon is-enabled" title="<?php esc_attr_e('Enabled', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-dismiss cwp-item-status-icon is-disabled" title="<?php esc_attr_e('Disabled', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"></span>
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
        $interaction_mode = !empty($_POST['interaction_mode'])
            ? sanitize_text_field($_POST['interaction_mode'])
            : 'auto';
        $prefill_message = isset($_POST['prefill_message'])
            ? sanitize_textarea_field($_POST['prefill_message'])
            : '';

        // Enhanced validation
        if (empty($platform) || empty($label) || empty($contact_value)) {
            wp_send_json_error(__('Add a chat app, a name, and contact details before you save.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate platform is supported
        $supported_platforms = $this->items_manager->get_supported_platforms();
        if (!array_key_exists($platform, $supported_platforms)) {
            wp_send_json_error(__('Choose one of the available chat apps.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate label length
        if (strlen($label) < 2 || strlen($label) > 50) {
            wp_send_json_error(__('Enter a name between 2 and 50 characters.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate contact value format based on platform
        if (!$this->items_manager->validate_contact_value($platform, $contact_value)) {
            wp_send_json_error(__('The contact details do not match the chat app you selected.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        $item_data = array(
            'platform' => $platform,
            'label' => $label,
            'contact_value' => $contact_value,
            'qr_code_id' => $qr_code_id,
            'enabled' => $enabled,
            'behavior_settings' => array(
                'interaction_mode' => $interaction_mode,
                'prefill_message' => $prefill_message,
            ),
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
                'message' => 'updated' === $action
                    ? __('Contact method updated.', CWP_CHAT_BUBBLES_TEXT_DOMAIN)
                    : __('Contact method added.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                'items_html' => $this->get_items_list_html()
            ));
        } else {
            $this->log_admin_action('item_save_error', "Failed to {$action} item: {$label} ({$platform})");
            wp_send_json_error(__('We could not save this contact method. Try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
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
                'message' => __('Contact method deleted.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                'items_html' => $this->get_items_list_html()
            ));
        } else {
            $this->log_admin_action('item_delete_error', "Failed to delete item ID: {$item_id}");
            wp_send_json_error(__('We could not delete this contact method. Try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
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
            wp_send_json_error(__('There are too many contact methods to reorder at once.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if ($this->items_manager->reorder_items($ordered_ids)) {
            $this->log_admin_action('items_reordered', 'Reordered ' . count($ordered_ids) . ' items');
            wp_send_json_success(__('Contact methods reordered.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        } else {
            $this->log_admin_action('reorder_error', 'Failed to reorder ' . count($ordered_ids) . ' items');
            wp_send_json_error(__('We could not save the new order. Try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
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
