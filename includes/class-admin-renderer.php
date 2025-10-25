<?php
/**
 * Admin Renderer Class
 *
 * Handles rendering of admin UI components
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles Admin Renderer Class
 *
 * @since 1.0.0
 */
class CWP_Chat_Bubbles_Admin_Renderer {

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
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->settings = CWP_Chat_Bubbles_Settings::get_instance();
        $this->items_manager = CWP_Chat_Bubbles_Items_Manager::get_instance();
    }

    /**
     * Render items list for admin interface
     *
     * @param array $items Items from custom table
     * @since 1.0.0
     */
    public function render_items_list($items) {
        if (empty($items)) {
            $this->render_empty_state();
            return;
        }

        $supported_platforms = $this->items_manager->get_supported_platforms();
        ?>
        <div class="cwp-items-list" id="sortable-items">
            <?php foreach ($items as $item): ?>
                <?php $this->render_item_row($item, $supported_platforms); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render single item row
     *
     * @param array $item Item data
     * @param array $supported_platforms Supported platforms configuration
     * @since 1.0.0
     */
    private function render_item_row($item, $supported_platforms) {
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
            <img class="cwp-item-icon" src="<?php echo esc_url($this->items_manager->get_platform_icon_url($item['platform'])); ?>" alt="<?php echo esc_attr($platform_label); ?>">
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
        <?php
    }

    /**
     * Render empty state message
     *
     * @since 1.0.0
     */
    private function render_empty_state() {
        ?>
        <div class="cwp-empty-state">
            <h3><?php esc_html_e('No chat items found', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Click "Add New Item" to create your first chat bubble item.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></p>
        </div>
        <?php
    }

    /**
     * Render general settings tab
     *
     * @param array $options Current options
     * @since 1.0.0
     */
    public function render_general_settings_tab($options) {
        ?>
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
                    <?php $this->render_main_icon_upload($options); ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render main icon upload field
     *
     * @param array $options Current options
     * @since 1.0.0
     */
    private function render_main_icon_upload($options) {
        ?>
        <div class="cwp-media-upload">
            <input type="hidden" name="cwp_chat_bubbles_options[custom_main_icon]" id="custom-main-icon" value="<?php echo esc_attr($options['custom_main_icon']); ?>">
            <button type="button" class="button" id="upload-main-icon">
                <?php esc_html_e('Upload Custom Icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="button" id="remove-main-icon" style="display: <?php echo $options['custom_main_icon'] > 0 ? 'inline-block' : 'none'; ?>;">
                <?php esc_html_e('Remove Custom Icon', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
            </button>
            <div id="main-icon-preview" style="margin-top: 10px; display: flex; justify-content: center; align-items: center; border-radius: 50%; width: 64px; height: 64px; background-color: <?php echo esc_attr($options['main_button_color']); ?>;">
                <?php if ($options['custom_main_icon'] > 0): ?>
                    <?php $custom_icon_url = wp_get_attachment_url($options['custom_main_icon']); ?>
                    <?php if ($custom_icon_url): ?>
                        <img src="<?php echo esc_url($custom_icon_url); ?>" alt="<?php esc_attr_e('Custom main icon preview', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>" style="width:80%; height:auto;">
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <p class="description">
            <?php esc_html_e('Upload a custom icon for the main chat button. Leave empty to use the default support icon. Recommended size: 64x64 pixels.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
        </p>
        <?php
    }

    /**
     * Render display settings tab
     *
     * @param array $options Current options
     * @since 1.0.0
     */
    public function render_display_settings_tab($options) {
        ?>
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
                    <?php $this->render_position_offset_field($options); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Main Button Color', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="color" name="cwp_chat_bubbles_options[main_button_color]" value="<?php echo esc_attr($options['main_button_color']); ?>" class="color-field">
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
            <tr>
                <th scope="row"><?php esc_html_e('Load on Mobile', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="cwp_chat_bubbles_options[load_on_mobile]" value="1" <?php checked($options['load_on_mobile']); ?>>
                        <?php esc_html_e('Show chat bubbles on mobile devices', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render position offset field
     *
     * @param array $options Current options
     * @since 1.0.0
     */
    private function render_position_offset_field($options) {
        ?>
        <fieldset>
            <legend class="screen-reader-text"><?php esc_html_e('Position Offset Settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></legend>
            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 10px;">
                <label style="display: flex; align-items: center; gap: 5px;">
                    <span style="min-width: 20px;"><?php esc_html_e('X:', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                    <input type="number" name="cwp_chat_bubbles_options[offset_x]" value="<?php echo esc_attr($options['offset_x']); ?>" min="-200" max="200" step="1" style="width: 80px;">
                    <span style="color: #666;">px</span>
                </label>
                <label style="display: flex; align-items: center; gap: 5px;">
                    <span style="min-width: 20px;"><?php esc_html_e('Y:', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?></span>
                    <input type="number" name="cwp_chat_bubbles_options[offset_y]" value="<?php echo esc_attr($options['offset_y']); ?>" min="-200" max="200" step="1" style="width: 80px;">
                    <span style="color: #666;">px</span>
                </label>
            </div>
            <p class="description">
                <?php esc_html_e('Fine-tune the position with pixel-level adjustments. Positive X moves right, negative moves left. Positive Y moves down, negative moves up.', CWP_CHAT_BUBBLES_TEXT_DOMAIN); ?>
            </p>
        </fieldset>
        <?php
    }

    /**
     * Render modal for add/edit item
     *
     * @param array $supported_platforms Supported platforms configuration
     * @since 1.0.0
     */
    public function render_item_modal($supported_platforms) {
        ?>
        <div id="cwp-item-modal" class="cwp-modal" style="display: none;">
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
        <?php
    }
}
