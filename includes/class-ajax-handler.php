<?php
/**
 * AJAX Handler Class
 *
 * Handles all AJAX requests for the plugin
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles AJAX Handler Class
 *
 * @since 1.0.0
 */
class CWP_Chat_Bubbles_AJAX_Handler {

    /**
     * Instance of this class
     *
     * @var CWP_Chat_Bubbles_AJAX_Handler
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
     * Get instance
     *
     * @return CWP_Chat_Bubbles_AJAX_Handler
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
        $this->init();
    }

    /**
     * Initialize AJAX handlers
     *
     * @since 1.0.0
     */
    private function init() {
        add_action('wp_ajax_cwp_chat_bubbles_save_item', array($this, 'ajax_save_item'));
        add_action('wp_ajax_cwp_chat_bubbles_delete_item', array($this, 'ajax_delete_item'));
        add_action('wp_ajax_cwp_chat_bubbles_reorder_items', array($this, 'ajax_reorder_items'));
        add_action('wp_ajax_cwp_chat_bubbles_get_attachment_url', array($this, 'ajax_get_attachment_url'));
    }

    /**
     * AJAX handler for saving item
     *
     * @since 1.0.0
     */
    public function ajax_save_item() {
        if (!$this->verify_ajax_request()) {
            wp_die(__('Security check failed', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if (!$this->check_rate_limit()) {
            wp_send_json_error(__('Too many requests. Please wait a moment before trying again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        $item_id = !empty($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
        $platform = sanitize_text_field($_POST['platform']);
        $label = sanitize_text_field($_POST['label']);
        $contact_value = sanitize_text_field($_POST['contact_value']);
        $qr_code_id = !empty($_POST['qr_code_id']) ? (int) $_POST['qr_code_id'] : 0;
        $enabled = !empty($_POST['enabled']) ? 1 : 0;

        if (empty($platform) || empty($label) || empty($contact_value)) {
            wp_send_json_error(__('Required fields are missing', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        $supported_platforms = $this->items_manager->get_supported_platforms();
        if (!array_key_exists($platform, $supported_platforms)) {
            wp_send_json_error(__('Invalid platform selected', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if (strlen($label) < 2 || strlen($label) > 50) {
            wp_send_json_error(__('Label must be between 2 and 50 characters', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

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
            $result = $this->items_manager->update_item($item_id, $item_data);
            $action = 'updated';
        } else {
            $result = $this->items_manager->create_item($item_data);
            $action = 'created';
        }

        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(__('Item %s successfully', CWP_CHAT_BUBBLES_TEXT_DOMAIN), $action),
                'items_html' => $this->get_items_list_html()
            ));
        } else {
            wp_send_json_error(__('Failed to save item', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX handler for deleting item
     *
     * @since 1.0.0
     */
    public function ajax_delete_item() {
        if (!$this->verify_ajax_request()) {
            wp_die(__('Security check failed', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if (!$this->check_rate_limit()) {
            wp_send_json_error(__('Too many requests. Please wait a moment before trying again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        $item_id = (int) $_POST['item_id'];

        if ($this->items_manager->delete_item($item_id)) {
            wp_send_json_success(array(
                'message' => __('Item deleted successfully', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                'items_html' => $this->get_items_list_html()
            ));
        } else {
            wp_send_json_error(__('Failed to delete item', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX handler for reordering items
     *
     * @since 1.0.0
     */
    public function ajax_reorder_items() {
        if (!$this->verify_ajax_request()) {
            wp_die(__('Security check failed', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if (!$this->check_rate_limit()) {
            wp_send_json_error(__('Too many requests. Please wait a moment before trying again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        $ordered_ids = array_map('intval', $_POST['ordered_ids']);

        if (count($ordered_ids) > 50) {
            wp_send_json_error(__('Too many items to reorder.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if ($this->items_manager->reorder_items($ordered_ids)) {
            wp_send_json_success(__('Items reordered successfully', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        } else {
            wp_send_json_error(__('Failed to reorder items', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX handler for getting attachment URL
     *
     * @since 1.0.0
     */
    public function ajax_get_attachment_url() {
        if (!$this->verify_ajax_request()) {
            wp_die(__('Security check failed', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        if (!$this->check_rate_limit()) {
            wp_die(__('Too many requests. Please wait a moment before trying again.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
        }

        $attachment_id = !empty($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        
        if ($attachment_id > 0) {
            $attachment = get_post($attachment_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                $file_path = get_attached_file($attachment_id);
                $mime_type = get_post_mime_type($attachment_id);
                
                $allowed_mime_types = array(
                    'image/jpeg',
                    'image/jpg', 
                    'image/png',
                    'image/gif',
                    'image/webp'
                );
                
                if (!in_array($mime_type, $allowed_mime_types)) {
                    wp_send_json_error(__('Invalid file type. Only images are allowed.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
                }
                
                if ($file_path && file_exists($file_path)) {
                    $file_size = filesize($file_path);
                    if ($file_size > 2 * 1024 * 1024) {
                        wp_send_json_error(__('File too large. Maximum size is 2MB.', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
                    }
                }
                
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    wp_send_json_success(array('url' => $url));
                }
            }
        }
        
        wp_send_json_error(__('Invalid attachment ID', CWP_CHAT_BUBBLES_TEXT_DOMAIN));
    }

    /**
     * Verify AJAX request security
     *
     * @return bool
     * @since 1.0.0
     */
    private function verify_ajax_request() {
        if (!wp_verify_nonce($_POST['nonce'], 'cwp_chat_bubbles_admin')) {
            return false;
        }

        if (!current_user_can('manage_options')) {
            return false;
        }

        return true;
    }

    /**
     * Check rate limiting for AJAX requests
     *
     * @return bool
     * @since 1.0.0
     */
    private function check_rate_limit() {
        $user_id = get_current_user_id();
        $transient_key = 'cwp_chat_bubbles_ajax_rate_limit_' . $user_id;
        $request_count = get_transient($transient_key);
        
        if ($request_count && $request_count >= 20) {
            return false;
        }
        
        set_transient($transient_key, ($request_count ? $request_count + 1 : 1), 60);
        
        return true;
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
            <?php endforeach; ?>
        </div>
        <?php
    }
}
