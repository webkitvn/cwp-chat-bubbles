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
        ?>
        <div class="wrap cwp-admin-wrapper">
            <h1 class="wp-heading-inline"><?php esc_html_e('Chat Bubbles', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h1>
            <hr class="wp-header-end">
            
            <?php 
            // Display admin notices (success/error messages)
            settings_errors(); 
            ?>
            
            <!-- Single form for all tabs to prevent data loss when switching tabs -->
            <form method="post" action="">
                <?php wp_nonce_field('cwp_chat_bubbles_settings', 'cwp_chat_bubbles_nonce'); ?>
                
                <div id="cwp-admin-tabs">
                    <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Settings sections', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>">
                        <a href="#chat-items" class="nav-tab nav-tab-active"><?php esc_html_e('Contact Buttons', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                        <a href="#general-settings" class="nav-tab"><?php esc_html_e('General', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                        <a href="#display-settings" class="nav-tab"><?php esc_html_e('Display', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></a>
                    </nav>

                    <!-- General Settings Tab -->
                    <div id="general-settings" class="tab-content" style="display: none;">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable chat bubbles', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[enabled]" value="1" <?php checked($options['enabled']); ?>>
                                        <?php esc_html_e('Show chat bubbles on your website', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Auto show on pages', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[auto_load]" value="1" <?php checked($options['auto_load']); ?>>
                                        <?php esc_html_e('Show automatically on all pages (recommended)', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('Turn this off if you only want to place chat bubbles with shortcode.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Show labels', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[show_labels]" value="1" <?php checked($options['show_labels']); ?>>
                                        <?php esc_html_e('Show text next to each contact button', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('This applies to all contact buttons.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Default layout', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <select name="cwp_chat_bubbles_options[default_layout]">
                                        <option value="toggle" <?php selected($options['default_layout'] ?? 'toggle', 'toggle'); ?>>
                                            <?php esc_html_e('Toggle (click to open)', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </option>
                                        <option value="expanded" <?php selected($options['default_layout'] ?? 'toggle', 'expanded'); ?>>
                                            <?php esc_html_e('Expanded (always show items)', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php esc_html_e('Expanded layout hides the main toggle and keeps all contact buttons visible.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Show on mobile', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[load_on_mobile]" value="1" <?php checked($options['load_on_mobile']); ?>>
                                        <?php esc_html_e('Show chat bubbles on phones and tablets', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                <!-- Chat Items Tab -->
                <div id="chat-items" class="tab-content">
                    <div class="cwp-items-header">
                        <div>
                            <h3><?php esc_html_e('Contact buttons', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h3>
                            <p class="description"><?php esc_html_e('Add, edit, or reorder contact buttons. Reordering is saved automatically.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                        </div>
                        <button type="button" class="button button-primary" id="add-new-item">
                            <?php esc_html_e('Add contact button', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                        </button>
                    </div>

                    <div id="cwp-items-container">
                        <?php $this->render_items_list($items); ?>
                    </div>
                </div>

                    <!-- Display Settings Tab -->
                    <div id="display-settings" class="tab-content" style="display: none;">
                        <table class="form-table" role="presentation">
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
                                <th scope="row"><?php esc_html_e('Position offset', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('Position Offset Settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
                                        <div class="cwp-offset-fields">
                                            <label class="cwp-offset-field">
                                                <span><?php esc_html_e('X', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                                <input type="number" name="cwp_chat_bubbles_options[offset_x]" value="<?php echo esc_attr($options['offset_x'] ?? 0); ?>" min="-200" max="200" step="1" class="small-text">
                                                <span class="description">px</span>
                                            </label>
                                            <label class="cwp-offset-field">
                                                <span><?php esc_html_e('Y', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                                                <input type="number" name="cwp_chat_bubbles_options[offset_y]" value="<?php echo esc_attr($options['offset_y'] ?? 0); ?>" min="-200" max="200" step="1" class="small-text">
                                                <span class="description">px</span>
                                            </label>
                                        </div>
                                        <p class="description">
                                            <?php esc_html_e('Fine-tune placement in pixels. Positive X moves right; positive Y moves down.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Main button color', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <input type="color" name="cwp_chat_bubbles_options[main_button_color]" value="<?php echo esc_attr($options['main_button_color'] ?? '#52BA00'); ?>" class="color-field">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Main button icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <div class="cwp-media-upload">
                                        <input type="hidden" name="cwp_chat_bubbles_options[custom_main_icon]" id="custom-main-icon" value="<?php echo esc_attr($options['custom_main_icon'] ?? 0); ?>">
                                        <button type="button" class="button" id="upload-main-icon">
                                            <?php esc_html_e('Upload icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <button type="button" class="button <?php echo (isset($options['custom_main_icon']) && $options['custom_main_icon'] > 0) ? '' : 'cwp-hidden'; ?>" id="remove-main-icon">
                                            <?php esc_html_e('Remove icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <div id="main-icon-preview" class="cwp-main-icon-preview" data-bg-color="<?php echo esc_attr($options['main_button_color'] ?? '#52BA00'); ?>">
                                            <?php if (isset($options['custom_main_icon']) && $options['custom_main_icon'] > 0): ?>
                                                <?php $custom_icon_url = wp_get_attachment_url($options['custom_main_icon']); ?>
                                                <?php if ($custom_icon_url): ?>
                                                    <img src="<?php echo esc_url($custom_icon_url); ?>" alt="<?php esc_attr_e('Custom main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" class="cwp-main-icon-image">
                                                <?php else: ?>
                                                    <img src="<?php echo esc_url(CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg'); ?>" alt="<?php esc_attr_e('Default main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" class="cwp-main-icon-image">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <img src="<?php echo esc_url(CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg'); ?>" alt="<?php esc_attr_e('Default main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" class="cwp-main-icon-image">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="description">
                                        <?php esc_html_e('Use your own icon for the main button. Recommended size: 64 × 64 px.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Animation effects', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cwp_chat_bubbles_options[animation_enabled]" value="1" <?php checked($options['animation_enabled']); ?>>
                                        <?php esc_html_e('Use animation when opening and hovering', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Single submit button for all tabs -->
                <div class="cwp-settings-actions">
                    <?php submit_button(); ?>
                </div>
            </form>
            
            <!-- Add/Edit Item Modal (outside main form to prevent nesting) -->
            <div id="cwp-item-modal" class="cwp-modal">
                <div class="cwp-modal-content">
                    <div class="cwp-modal-header">
                        <h3 id="modal-title"><?php esc_html_e('Add Contact Button', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h3>
                        <button type="button" class="cwp-modal-close" aria-label="<?php esc_attr_e('Close dialog', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>">&times;</button>
                    </div>
                    <div class="cwp-modal-body">
                        <form id="cwp-item-form">
                            <input type="hidden" id="item-id" name="item_id" value="">
                            
                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row">
                                        <label for="platform"><?php esc_html_e('Platform', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <select id="platform" name="platform" required>
                                            <option value=""><?php esc_html_e('Choose a platform', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></option>
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
                                        <label for="label"><?php esc_html_e('Button label', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="label" name="label" class="regular-text" placeholder="<?php esc_attr_e('Example: Sales Team', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="contact-value" id="contact-label"><?php esc_html_e('Contact details', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="contact-value" name="contact_value" class="regular-text" placeholder="" required>
                                        <p class="description" id="contact-description"></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="qr-code"><?php esc_html_e('QR code image', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="hidden" id="qr-code-id" name="qr_code_id" value="0">
                                        <button type="button" class="button" id="upload-qr-code">
                                            <?php esc_html_e('Upload QR code', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <button type="button" class="button cwp-hidden" id="remove-qr-code">
                                            <?php esc_html_e('Remove QR code', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </button>
                                        <div id="qr-preview" class="cwp-qr-preview"></div>
                                        <p class="description"><?php esc_html_e('Optional. Add a QR code that opens this contact method.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="enabled"><?php esc_html_e('Visibility', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="enabled" name="enabled" value="1" checked>
                                            <?php esc_html_e('Show this contact button', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="cwp-modal-footer">
                        <button type="button" class="button" id="cancel-item"><?php esc_html_e('Cancel', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></button>
                        <button type="button" class="button button-primary" id="save-item"><?php esc_html_e('Save contact button', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></button>
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
                __('You are saving too quickly. Please wait a moment and try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
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
                    __('Your changes have been saved.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'success'
                );
            } catch (Exception $e) {
                // Log failed settings update
                $this->log_admin_action('settings_error', 'Failed to save general settings: ' . $e->getMessage());
                
                add_settings_error(
                    'cwp_chat_bubbles_messages',
                    'cwp_chat_bubbles_message',
                    __('We could not save your changes. Please try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'error'
                );
            }
        }
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
                <h3><?php esc_html_e('No contact buttons yet', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h3>
                <p><?php esc_html_e('Click "Add contact button" to create your first one.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
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
                    <div class="cwp-item-content">
                        <p class="cwp-item-title"><?php echo esc_html($item['label']); ?></p>
                        <p class="cwp-item-meta"><?php echo esc_html($platform_label); ?>: <?php echo esc_html($item['contact_value']); ?></p>
                        <?php if (!empty($item['qr_code_id'])): ?>
                            <span class="dashicons dashicons-format-image cwp-qr-indicator" title="<?php esc_attr_e('Has QR code', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div class="cwp-item-status">
                        <?php if ($item['enabled']): ?>
                            <span class="dashicons dashicons-yes-alt" title="<?php esc_attr_e('Visible', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-dismiss" title="<?php esc_attr_e('Hidden', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div class="cwp-item-actions">
                        <button type="button" class="button button-small edit-item" data-item-id="<?php echo esc_attr($item['id']); ?>">
                            <?php esc_html_e('Edit', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" class="button-link button-link-delete delete-item" data-item-id="<?php echo esc_attr($item['id']); ?>">
                            <?php esc_html_e('Remove', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
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
     * Verify AJAX request security requirements.
     *
     * @param string $action_name Action name for audit logging context.
     * @since 1.1.1
     */
    private function verify_ajax_request($action_name) {
        if (!check_ajax_referer('cwp_chat_bubbles_admin', 'nonce', false)) {
            $this->log_admin_action('security_violation', 'Invalid nonce for ' . $action_name);
            wp_send_json_error(__('Your session has expired. Refresh this page and try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN), 403);
        }

        if (!current_user_can('manage_options')) {
            $this->log_admin_action('security_violation', 'Insufficient permissions for ' . $action_name);
            wp_send_json_error(__('You do not have permission to do this action.', CWP_CHAT_BUBBLES_TEXT_DOMAIN), 403);
        }
    }

    /**
     * Safely read a text value from $_POST.
     *
     * @param string $key POST field name.
     * @param string $default Default value.
     * @return string
     * @since 1.1.1
     */
    private function get_post_text($key, $default = '') {
        if (!isset($_POST[$key])) {
            return $default;
        }

        $value = wp_unslash($_POST[$key]);
        if (!is_scalar($value)) {
            return $default;
        }

        return sanitize_text_field((string) $value);
    }

    /**
     * Safely read an integer value from $_POST.
     *
     * @param string $key POST field name.
     * @param int $default Default value.
     * @return int
     * @since 1.1.1
     */
    private function get_post_int($key, $default = 0) {
        if (!isset($_POST[$key])) {
            return (int) $default;
        }

        $value = wp_unslash($_POST[$key]);
        if (!is_scalar($value)) {
            return (int) $default;
        }

        return (int) $value;
    }

    /**
     * Safely read an integer array from $_POST.
     *
     * @param string $key POST field name.
     * @return array<int>
     * @since 1.1.1
     */
    private function get_post_int_array($key) {
        if (!isset($_POST[$key])) {
            return array();
        }

        $values = wp_unslash($_POST[$key]);
        if (!is_array($values)) {
            return array();
        }

        return array_map('intval', array_filter($values, 'is_scalar'));
    }

    /**
     * AJAX handler for saving item
     *
     * @since 1.0.0
     */
    public function ajax_save_item() {
        $this->verify_ajax_request('ajax_save_item');

        // Rate limiting for AJAX requests
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_ajax_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 20) {
            $this->log_admin_action('rate_limited', 'AJAX request rate limited for ajax_save_item');
            wp_send_json_error(__('You are saving too quickly. Please wait a moment and try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
        
        // Increment rate limit counter
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);

        $item_id = $this->get_post_int('item_id');
        $platform = $this->get_post_text('platform');
        $label = $this->get_post_text('label');
        $contact_value = $this->get_post_text('contact_value');
        $qr_code_id = $this->get_post_int('qr_code_id');
        $enabled = $this->get_post_int('enabled') ? 1 : 0;

        // Enhanced validation
        if (empty($platform) || empty($label) || empty($contact_value)) {
            wp_send_json_error(__('Please fill in all required fields.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate platform is supported
        $supported_platforms = $this->items_manager->get_supported_platforms();
        if (!array_key_exists($platform, $supported_platforms)) {
            wp_send_json_error(__('That platform is not valid. Please choose another one.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate label length
        if (strlen($label) < 2 || strlen($label) > 255) {
            wp_send_json_error(__('The button label must be between 2 and 255 characters.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate contact value format based on platform
        if (!$this->items_manager->validate_contact_value($platform, $contact_value)) {
            wp_send_json_error(__('The contact details format is not valid for this platform.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
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
                'message' => sprintf(__('Contact button %s successfully.', CWP_CHAT_BUBBLES_TEXT_DOMAIN), $action),
                'items_html' => $this->get_items_list_html()
            ));
        } else {
            $this->log_admin_action('item_save_error', "Failed to {$action} item: {$label} ({$platform})");
            wp_send_json_error(__('We could not save this contact button. Please try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX handler for deleting item
     *
     * @since 1.0.0
     */
    public function ajax_delete_item() {
        $this->verify_ajax_request('ajax_delete_item');

        // Rate limiting (shared with other AJAX endpoints)
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_ajax_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 20) {
            $this->log_admin_action('rate_limited', 'AJAX request rate limited for ajax_delete_item');
            wp_send_json_error(__('You are making changes too quickly. Please wait a moment and try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
        
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);

        $item_id = $this->get_post_int('item_id');
        if ($item_id <= 0) {
            wp_send_json_error(__('This contact button could not be found.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if ($this->items_manager->delete_item($item_id)) {
            $this->log_admin_action('item_deleted', "Deleted item ID: {$item_id}");
            wp_send_json_success(array(
                'message' => __('Contact button removed.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                'items_html' => $this->get_items_list_html()
            ));
        } else {
            $this->log_admin_action('item_delete_error', "Failed to delete item ID: {$item_id}");
            wp_send_json_error(__('We could not remove this contact button. Please try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX handler for reordering items
     *
     * @since 1.0.0
     */
    public function ajax_reorder_items() {
        $this->verify_ajax_request('ajax_reorder_items');

        // Rate limiting (shared with other AJAX endpoints)
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_ajax_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 20) {
            $this->log_admin_action('rate_limited', 'AJAX request rate limited for ajax_reorder_items');
            wp_send_json_error(__('You are reordering too quickly. Please wait a moment and try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
        
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);

        $ordered_ids = $this->get_post_int_array('ordered_ids');

        if (empty($ordered_ids)) {
            wp_send_json_error(__('No contact buttons were provided for reordering.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        // Validate that ordered_ids is reasonable (max 50 items)
        if (count($ordered_ids) > 50) {
            $this->log_admin_action('security_violation', 'Too many items in reorder request: ' . count($ordered_ids));
            wp_send_json_error(__('Too many items to reorder.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if ($this->items_manager->reorder_items($ordered_ids)) {
            $this->log_admin_action('items_reordered', 'Reordered ' . count($ordered_ids) . ' items');
            wp_send_json_success(__('Order updated.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        } else {
            $this->log_admin_action('reorder_error', 'Failed to reorder ' . count($ordered_ids) . ' items');
            wp_send_json_error(__('We could not update the order. Please try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
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
        $this->verify_ajax_request('ajax_get_attachment_url');

        // Rate limiting for AJAX requests (shared with other AJAX endpoints)
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_ajax_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 20) {
            $this->log_admin_action('rate_limited', 'AJAX request rate limited for ajax_get_attachment_url');
            wp_die(__('You are requesting files too quickly. Please wait a moment and try again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
        
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);

        $attachment_id = $this->get_post_int('attachment_id');
        
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
                
                if (!in_array($mime_type, $allowed_mime_types, true)) {
                    $this->log_admin_action('security_violation', "Invalid file type uploaded: {$mime_type}");
                    wp_send_json_error(__('Please upload an image file (JPG, PNG, GIF, or WebP).', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
                }
                
                // Check file size (max 2MB for QR codes)
                if ($file_path && file_exists($file_path)) {
                    $file_size = filesize($file_path);
                    if ($file_size > 2 * 1024 * 1024) { // 2MB
                        $this->log_admin_action('security_violation', "File too large: " . number_format($file_size / 1024 / 1024, 2) . "MB");
                        wp_send_json_error(__('This image is too large. Maximum size is 2MB.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
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
        wp_send_json_error(__('We could not load that image. Please select another file.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
    }
} 
