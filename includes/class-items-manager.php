<?php
/**
 * Items Manager Class
 *
 * Handles custom table CRUD operations for chat bubble items
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles Items Manager Class
 *
 * @since 1.0.0
 */
class CWP_Chat_Bubbles_Items_Manager {

    /**
     * Instance of this class
     *
     * @var CWP_Chat_Bubbles_Items_Manager
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Table name for chat items
     *
     * @var string
     * @since 1.0.0
     */
    private $table_name;

    /**
     * Database version for table upgrades
     *
     * @var string
     * @since 1.0.0
     */
    private $db_version = '1.0.0';

    /**
     * Supported platforms configuration
     *
     * @var array
     * @since 1.0.0
     */
    private $supported_platforms = array(
        'phone' => array(
            'label' => 'Phone/Hotline',
            'contact_field' => 'number',
            'pattern' => '/^[\+]?[0-9\s\-\(\)]{7,20}$/',
            'placeholder' => '+1234567890'
        ),
        'zalo' => array(
            'label' => 'Zalo',
            'contact_field' => 'number',
            'pattern' => '/^[0-9]{9,11}$/',
            'placeholder' => '0123456789'
        ),
        'whatsapp' => array(
            'label' => 'WhatsApp',
            'contact_field' => 'number',
            'pattern' => '/^[\+]?[1-9][\d]{0,15}$/',
            'placeholder' => '1234567890'
        ),
        'viber' => array(
            'label' => 'Viber',
            'contact_field' => 'number',
            'pattern' => '/^[\+]?[0-9\s\-\(\)]{7,20}$/',
            'placeholder' => '+1234567890'
        ),
        'telegram' => array(
            'label' => 'Telegram',
            'contact_field' => 'username',
            'pattern' => '/^[a-zA-Z0-9_]{5,32}$/',
            'placeholder' => 'username'
        ),
        'messenger' => array(
            'label' => 'Facebook Messenger',
            'contact_field' => 'username',
            'pattern' => '/^[a-zA-Z0-9.]{1,50}$/',
            'placeholder' => 'username'
        ),
        'line' => array(
            'label' => 'Line',
            'contact_field' => 'id',
            'pattern' => '/^[a-zA-Z0-9._-]{1,50}$/',
            'placeholder' => 'your-line-id'
        ),
        'kakaotalk' => array(
            'label' => 'KakaoTalk',
            'contact_field' => 'id',
            'pattern' => '/^[a-zA-Z0-9_-]{1,50}$/',
            'placeholder' => 'your-kakao-id'
        )
    );

    /**
     * Platform icon mapping
     * Maps platform names to their corresponding icon filenames
     *
     * @var array
     * @since 1.0.0
     */
    const PLATFORM_ICON_MAP = array(
        'phone' => 'phone.svg',
        'zalo' => 'zalo.svg',
        'whatsapp' => 'whatsapp.svg',
        'viber' => 'viber.svg',
        'telegram' => 'telegram.svg',
        'messenger' => 'messenger.svg',
        'line' => 'line.svg',
        'kakaotalk' => 'kakaotalk.svg',
        // Additional icons available but not used as platforms yet
        'facebook' => 'facebook.svg',
        'instagram' => 'instagram.svg',
        'youtube' => 'youtube.svg',
        'tiktok' => 'tiktok.svg',
        'wechat' => 'wechat.svg'
    );

    /**
     * Platform brand colors
     * Maps platform names to their brand/recognizable colors
     *
     * @var array
     * @since 1.0.0
     */
    const PLATFORM_COLORS = array(
        'phone' => '#52BA00',
        'zalo' => '#008BE6',
        'whatsapp' => '#25D366',
        'viber' => '#665cac',
        'telegram' => '#0088cc',
        'messenger' => '#0084ff',
        'line' => '#38cd01',
        'kakaotalk' => '#ffeb3b'
    );

    /**
     * Get instance
     *
     * @return CWP_Chat_Bubbles_Items_Manager
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cwp_chat_bubbles_items';
        
        // Hook into WordPress to ensure table exists
        add_action('init', array($this, 'maybe_create_table'));
    }

    /**
     * Create the custom table for chat items
     *
     * @since 1.0.0
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            platform varchar(20) NOT NULL,
            enabled tinyint(1) DEFAULT 1,
            label varchar(255) NOT NULL,
            contact_value varchar(255) NOT NULL,
            qr_code_id int(11) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY platform (platform),
            KEY enabled (enabled),
            KEY sort_order (sort_order)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Update database version
        update_option('cwp_chat_bubbles_db_version', $this->db_version);
    }

    /**
     * Check if table needs to be created or updated
     *
     * @since 1.0.0
     */
    public function maybe_create_table() {
        $installed_version = get_option('cwp_chat_bubbles_db_version', '0');
        
        if (version_compare($installed_version, $this->db_version, '<')) {
            $this->create_table();
        }
    }

    /**
     * Get all items with optional filtering
     *
     * @param bool $enabled_only Whether to return only enabled items
     * @return array Array of chat items
     * @since 1.0.0
     */
    public function get_all_items($enabled_only = false) {
        global $wpdb;

        $where = $enabled_only ? "WHERE enabled = 1" : "";

        $results = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} {$where} ORDER BY sort_order ASC, id ASC",
            ARRAY_A
        );

        return $results ? $results : array();
    }

    /**
     * Get single item by ID
     *
     * @param int $id Item ID
     * @return array|null Item data or null if not found
     * @since 1.0.0
     */
    public function get_item($id) {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );

        return $result;
    }

    /**
     * Create new item
     *
     * @param array $data Item data
     * @return int|false Item ID on success, false on failure
     * @since 1.0.0
     */
    public function create_item($data) {
        global $wpdb;

        // Validate required fields
        if (empty($data['platform']) || empty($data['label']) || empty($data['contact_value'])) {
            return false;
        }

        // Validate platform
        if (!$this->is_platform_supported($data['platform'])) {
            return false;
        }

        // Validate contact value
        if (!$this->validate_contact_value($data['platform'], $data['contact_value'])) {
            return false;
        }

        // Sanitize data
        $sanitized_data = $this->sanitize_item_data($data);

        // Get next sort order if not provided
        if (!isset($sanitized_data['sort_order'])) {
            $max_order = $wpdb->get_var("SELECT MAX(sort_order) FROM {$this->table_name}");
            $sanitized_data['sort_order'] = ($max_order ? (int) $max_order : 0) + 1;
        }

        // Set default enabled state
        if (!isset($sanitized_data['enabled'])) {
            $sanitized_data['enabled'] = 1;
        }

        $result = $wpdb->insert(
            $this->table_name,
            $sanitized_data,
            array('%s', '%d', '%s', '%s', '%d', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update existing item
     *
     * @param int $id Item ID
     * @param array $data Updated item data
     * @return bool Success
     * @since 1.0.0
     */
    public function update_item($id, $data) {
        global $wpdb;

        // Check if item exists
        $existing_item = $this->get_item($id);
        if (!$existing_item) {
            return false;
        }

        // Don't allow platform change (for data integrity)
        if (isset($data['platform']) && $data['platform'] !== $existing_item['platform']) {
            unset($data['platform']);
        }

        // Validate contact value if provided
        if (isset($data['contact_value'])) {
            if (!$this->validate_contact_value($existing_item['platform'], $data['contact_value'])) {
                return false;
            }
        }

        // Sanitize data
        $sanitized_data = $this->sanitize_item_data($data);

        if (empty($sanitized_data)) {
            return false;
        }

        $result = $wpdb->update(
            $this->table_name,
            $sanitized_data,
            array('id' => $id),
            null,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Delete item
     *
     * @param int $id Item ID
     * @return bool Success
     * @since 1.0.0
     */
    public function delete_item($id) {
        global $wpdb;

        // Get item before deletion to clean up QR code
        $item = $this->get_item($id);
        if ($item && !empty($item['qr_code_id'])) {
            wp_delete_attachment($item['qr_code_id'], true);
        }

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Reorder items by updating sort_order
     *
     * @param array $ordered_ids Array of item IDs in desired order
     * @return bool Success
     * @since 1.0.0
     */
    public function reorder_items($ordered_ids) {
        global $wpdb;

        if (empty($ordered_ids) || !is_array($ordered_ids)) {
            return false;
        }

        $success = true;

        foreach ($ordered_ids as $index => $id) {
            $result = $wpdb->update(
                $this->table_name,
                array('sort_order' => $index + 1),
                array('id' => (int) $id),
                array('%d'),
                array('%d')
            );

            if ($result === false) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Get supported platforms configuration
     *
     * @return array Supported platforms
     * @since 1.0.0
     */
    public function get_supported_platforms() {
        return $this->supported_platforms;
    }

    /**
     * Check if platform is supported
     *
     * @param string $platform Platform name
     * @return bool True if supported
     * @since 1.0.0
     */
    public function is_platform_supported($platform) {
        return isset($this->supported_platforms[$platform]);
    }

    /**
     * Get platform configuration
     *
     * @param string $platform Platform name
     * @return array|null Platform config or null if not found
     * @since 1.0.0
     */
    public function get_platform_config($platform) {
        return isset($this->supported_platforms[$platform]) ? $this->supported_platforms[$platform] : null;
    }

    /**
     * Get platform icon URL
     *
     * @param string $platform Platform name
     * @return string Icon URL
     * @since 1.0.0
     */
    public function get_platform_icon_url($platform) {
        $icon_filename = isset(self::PLATFORM_ICON_MAP[$platform]) ? self::PLATFORM_ICON_MAP[$platform] : $platform . '.svg';
        return CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/socials/' . $icon_filename;
    }

    /**
     * Get all available platform icons
     *
     * @return array Array of platform => icon_url pairs
     * @since 1.0.0
     */
    public function get_all_platform_icons() {
        $icons = array();
        foreach (self::PLATFORM_ICON_MAP as $platform => $filename) {
            $icons[$platform] = CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/socials/' . $filename;
        }
        return $icons;
    }

    /**
     * Get platform brand color
     *
     * @param string $platform Platform name
     * @return string Color hex code
     * @since 1.0.0
     */
    public function get_platform_color($platform) {
        return isset(self::PLATFORM_COLORS[$platform]) ? self::PLATFORM_COLORS[$platform] : '#52BA00';
    }

    /**
     * Validate contact value based on platform
     *
     * @param string $platform Platform name
     * @param string $contact_value Contact value to validate
     * @return bool True if valid
     * @since 1.0.0
     */
    public function validate_contact_value($platform, $contact_value) {
        $config = $this->get_platform_config($platform);
        
        if (!$config || empty($contact_value)) {
            return false;
        }

        return preg_match($config['pattern'], trim($contact_value));
    }

    /**
     * Sanitize item data
     *
     * @param array $data Raw item data
     * @return array Sanitized data
     * @since 1.0.0
     */
    private function sanitize_item_data($data) {
        $sanitized = array();

        if (isset($data['platform'])) {
            $sanitized['platform'] = sanitize_text_field($data['platform']);
        }

        if (isset($data['enabled'])) {
            $sanitized['enabled'] = (int) (bool) $data['enabled'];
        }

        if (isset($data['label'])) {
            $sanitized['label'] = sanitize_text_field($data['label']);
        }

        if (isset($data['contact_value'])) {
            $sanitized['contact_value'] = sanitize_text_field($data['contact_value']);
        }

        if (isset($data['qr_code_id'])) {
            $sanitized['qr_code_id'] = (int) $data['qr_code_id'];
        }

        if (isset($data['sort_order'])) {
            $sanitized['sort_order'] = (int) $data['sort_order'];
        }

        return $sanitized;
    }

    /**
     * Migrate data from old options format to new table
     *
     * @return bool Success
     * @since 1.0.0
     */
    public function migrate_from_options() {
        // Check if migration has already been done
        if (get_option('cwp_chat_bubbles_migrated', false)) {
            return true;
        }

        $old_options = get_option('cwp_chat_bubbles_options', array());
        
        if (empty($old_options['platforms'])) {
            // No old data to migrate
            update_option('cwp_chat_bubbles_migrated', true);
            return true;
        }

        $migrated_count = 0;
        $order = 1;

        foreach ($old_options['platforms'] as $platform => $config) {
            if (empty($config['enabled']) || empty($config['label'])) {
                continue;
            }

            // Determine contact value based on platform
            $contact_value = '';
            if (!empty($config['number'])) {
                $contact_value = $config['number'];
            } elseif (!empty($config['username'])) {
                $contact_value = $config['username'];
            } elseif (!empty($config['id'])) {
                $contact_value = $config['id'];
            }

            if (empty($contact_value)) {
                continue;
            }

            // Handle QR code migration
            $qr_code_id = 0;
            if (!empty($config['qr_code'])) {
                $qr_code_id = $this->migrate_qr_code($config['qr_code']);
            }

            $item_data = array(
                'platform' => $platform,
                'enabled' => 1,
                'label' => $config['label'],
                'contact_value' => $contact_value,
                'qr_code_id' => $qr_code_id,
                'sort_order' => $order++
            );

            if ($this->create_item($item_data)) {
                $migrated_count++;
            }
        }

        // Backup old options and mark migration as complete
        update_option('cwp_chat_bubbles_options_backup', $old_options);
        update_option('cwp_chat_bubbles_migrated', true);

        return $migrated_count > 0;
    }

    /**
     * Migrate QR code from old format
     *
     * @param string $qr_code_path Old QR code path or URL
     * @return int WordPress media ID or 0 if failed
     * @since 1.0.0
     */
    private function migrate_qr_code($qr_code_path) {
        // This is a placeholder for QR code migration
        // In a real scenario, you'd handle file uploads to media library
        return 0;
    }

    /**
     * Get table name
     *
     * @return string Table name
     * @since 1.0.0
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Drop the custom table (for uninstall)
     *
     * @since 1.0.0
     */
    public function drop_table() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
        delete_option('cwp_chat_bubbles_db_version');
        delete_option('cwp_chat_bubbles_migrated');
    }
} 