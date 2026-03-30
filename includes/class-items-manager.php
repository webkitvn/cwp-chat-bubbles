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
    private $db_version = '1.1.0';

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
            'pattern' => '/^\+?[0-9\s\-\(\)]{7,20}$/',
            'placeholder' => '+1234567890'
        ),
        'zalo' => array(
            'label' => 'Zalo',
            'contact_field' => 'number',
            'pattern' => '/^[0-9]{9,11}$/',
            'placeholder' => '0123456789'
        ),
        'zalo_oa' => array(
            'label' => 'Zalo OA',
            'contact_field' => 'id',
            'pattern' => '/^(?:https?:\/\/oa\.zalo\.me\/)?([0-9]{10,25})$/',
            'placeholder' => '4073699630774515920 or https://oa.zalo.me/4073699630774515920'
        ),
        'whatsapp' => array(
            'label' => 'WhatsApp',
            'contact_field' => 'number',
            'pattern' => '/^\+?[1-9][0-9]{6,15}$/',
            'placeholder' => '1234567890'
        ),
        'viber' => array(
            'label' => 'Viber',
            'contact_field' => 'number',
            'pattern' => '/^\+?[0-9\s\-\(\)]{7,20}$/',
            'placeholder' => '+1234567890'
        ),
        'telegram' => array(
            'label' => 'Telegram',
            'contact_field' => 'username',
            'pattern' => '/^[a-zA-Z][a-zA-Z0-9_]{4,31}$/',
            'placeholder' => 'username'
        ),
        'messenger' => array(
            'label' => 'Facebook Messenger',
            'contact_field' => 'username',
            'pattern' => '/^[a-zA-Z0-9][a-zA-Z0-9\.]{0,49}$/',
            'placeholder' => 'username'
        ),
        'line' => array(
            'label' => 'Line',
            'contact_field' => 'id',
            'pattern' => '/^[a-zA-Z0-9][a-zA-Z0-9\._-]{0,49}$/',
            'placeholder' => 'your-line-id'
        ),
        'kakaotalk' => array(
            'label' => 'KakaoTalk',
            'contact_field' => 'id',
            'pattern' => '/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,49}$/',
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
        'zalo_oa' => 'zalo.svg',
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
        'zalo_oa' => '#008BE6',
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
            behavior_settings longtext DEFAULT NULL,
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
        // Create cache key based on data version and enabled filter
        $data_version = $this->get_data_version();
        $cache_key = $enabled_only ? 
            'cwp_items_enabled_v' . $data_version : 
            'cwp_items_all_v' . $data_version;
        
        // Try to get cached data first
        $cached_results = wp_cache_get($cache_key, 'cwp_chat_bubbles');
        if (false !== $cached_results) {
            return $cached_results;
        }

        global $wpdb;

        if ($enabled_only) {
            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE enabled = %d ORDER BY sort_order ASC, id ASC", 1),
                ARRAY_A
            );
        } else {
            $results = $wpdb->get_results(
                "SELECT * FROM {$this->table_name} ORDER BY sort_order ASC, id ASC",
                ARRAY_A
            );
        }

        $results = $results ? array_map(array($this, 'normalize_item_record'), $results) : array();
        
        // Cache the results for 1 hour
        wp_cache_set($cache_key, $results, 'cwp_chat_bubbles', HOUR_IN_SECONDS);

        return $results;
    }

    /**
     * Get single item by ID with caching
     *
     * @param int $id Item ID
     * @return array|null Item data or null if not found
     * @since 1.0.0
     */
    public function get_item($id) {
        $data_version = $this->get_data_version();
        $cache_key = 'cwp_item_' . $id . '_v' . $data_version;
        
        // Try to get cached data first
        $cached_result = wp_cache_get($cache_key, 'cwp_chat_bubbles');
        if (false !== $cached_result) {
            return $cached_result;
        }

        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );

        if (is_array($result)) {
            $result = $this->normalize_item_record($result);
        }

        // Cache the result for 1 hour (even if null)
        wp_cache_set($cache_key, $result, 'cwp_chat_bubbles', HOUR_IN_SECONDS);

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
            $this->get_item_column_formats($sanitized_data)
        );

        if ($result) {
            // Clear cache when data changes
            $this->clear_frontend_cache();
            return $wpdb->insert_id;
        }

        return false;
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
            $this->get_item_column_formats($sanitized_data),
            array('%d')
        );

        if ($result !== false) {
            // Clear cache when data changes
            $this->clear_frontend_cache();
        }

        return $result !== false;
    }

    /**
     * Get current data version for cache invalidation
     *
     * @return string Data version
     * @since 1.0.0
     */
    private function get_data_version() {
        $version = wp_cache_get('cwp_chat_bubbles_data_version', 'cwp_chat_bubbles');
        
        if (false === $version) {
            // Get version from database or create new one
            $version = get_option('cwp_chat_bubbles_data_version', '1');
            wp_cache_set('cwp_chat_bubbles_data_version', $version, 'cwp_chat_bubbles', DAY_IN_SECONDS);
        }
        
        return $version;
    }

    /**
     * Increment data version to invalidate all item caches
     *
     * @since 1.0.0
     */
    private function increment_data_version() {
        $current_version = (int) get_option('cwp_chat_bubbles_data_version', 1);
        $new_version = $current_version + 1;
        
        update_option('cwp_chat_bubbles_data_version', $new_version);
        wp_cache_set('cwp_chat_bubbles_data_version', $new_version, 'cwp_chat_bubbles', DAY_IN_SECONDS);
        
        // Clear all related caches
        $this->clear_all_item_caches();
    }

    /**
     * Clear all item-related caches
     *
     * @since 1.0.0
     */
    private function clear_all_item_caches() {
        // Clear frontend data cache
        wp_cache_delete('cwp_chat_bubbles_frontend_data', 'cwp_chat_bubbles');
        
        // Clear versioned item caches (we don't know the exact version, so we increment instead)
        // The version increment will make old cache keys invalid
        
        // Clear data version cache to force refresh
        wp_cache_delete('cwp_chat_bubbles_data_version', 'cwp_chat_bubbles');
    }

    /**
     * Clear frontend data cache (legacy method - now uses versioning)
     *
     * @since 1.0.0
     */
    private function clear_frontend_cache() {
        $this->increment_data_version();
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

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );

        if ($result !== false) {
            // Clear cache when data changes
            $this->clear_frontend_cache();
        }

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

        if ($success) {
            // Clear cache when data changes
            $this->clear_frontend_cache();
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
     * Generate platform URL
     *
     * @param string $platform Platform name
     * @param array $item Item data with contact_value
     * @return string Platform URL
     * @since 1.0.2
     */
    public function generate_platform_url($platform, $item) {
        $contact_value = !empty($item['contact_value']) ? $item['contact_value'] : '';
        
        if (empty($contact_value)) {
            return '#';
        }
        
        switch ($platform) {
            case 'phone':
                return 'tel:' . $contact_value;
                
            case 'zalo':
                return 'https://zalo.me/' . $contact_value . '?openChat=true';

            case 'zalo_oa':
                // Extract OA ID if full URL was provided
                $oa_id = $contact_value;
                if (preg_match('/^https?:\/\/oa\.zalo\.me\/([0-9]+)/', $contact_value, $matches)) {
                    $oa_id = $matches[1];
                }
                return 'https://oa.zalo.me/' . $oa_id;

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
                $channel_id = ltrim($contact_value, '_');
                return 'https://pf.kakao.com/_' . $channel_id . '/chat';
                
            default:
                return '#';
        }
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

        $contact_value = trim($contact_value);
        
        // Additional security checks
        if (strlen($contact_value) > 100) {
            return false;
        }
        
        // Check for potentially malicious content
        $malicious_patterns = array(
            '/<script/i',
            '/javascript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/onclick=/i',
            '/onmouseover=/i',
            '/expression\(/i',
            '/vbscript:/i'
        );
        
        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $contact_value)) {
                return false;
            }
        }

        return preg_match($config['pattern'], $contact_value);
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

        if (array_key_exists('behavior_settings', $data)) {
            $sanitized['behavior_settings'] = $this->encode_item_behavior_settings($data['behavior_settings']);
        }

        if (isset($data['sort_order'])) {
            $sanitized['sort_order'] = (int) $data['sort_order'];
        }

        return $sanitized;
    }

    /**
     * Get the normalized default per-item behavior payload for future CRUD and runtime consumers.
     *
     * Existing installs should resolve to this payload implicitly until the dedicated storage column ships.
     *
     * @return array<string, mixed> Default item behavior settings.
     * @since 1.0.3
     */
    public function get_default_item_behavior_settings() {
        return array(
            'schema_version' => 1,
            'interaction_mode' => 'auto',
            'prefill_message' => '',
        );
    }

    /**
     * Normalize a raw per-item behavior payload from array or stored string form.
     *
     * The future storage column will use a serialized longtext payload so old plugin versions can safely ignore it,
     * while new code can still hydrate defaults when the field is empty, missing, or malformed.
     *
     * @param mixed $behavior_settings Raw behavior payload.
     * @return array<string, mixed> Normalized item behavior settings.
     * @since 1.0.3
     */
    public function normalize_item_behavior_settings($behavior_settings) {
        $defaults = $this->get_default_item_behavior_settings();
        $decoded = $this->decode_item_behavior_settings($behavior_settings);

        if (!is_array($decoded)) {
            return $defaults;
        }

        $normalized = $defaults;
        $allowed_modes = array('auto', 'direct_link', 'qr_modal');

        if (isset($decoded['interaction_mode']) && in_array($decoded['interaction_mode'], $allowed_modes, true)) {
            $normalized['interaction_mode'] = $decoded['interaction_mode'];
        }

        if (isset($decoded['prefill_message'])) {
            $normalized['prefill_message'] = substr(
                sanitize_textarea_field((string) $decoded['prefill_message']),
                0,
                500
            );
        }

        return $normalized;
    }

    /**
     * Get normalized per-item behavior settings from an item record.
     *
     * @param array<string, mixed> $item Item row or partial item payload.
     * @return array<string, mixed> Normalized item behavior settings.
     * @since 1.0.3
     */
    public function get_item_behavior_settings($item) {
        if (!is_array($item) || !array_key_exists('behavior_settings', $item)) {
            return $this->get_default_item_behavior_settings();
        }

        return $this->normalize_item_behavior_settings($item['behavior_settings']);
    }

    /**
     * Get the selected migration contract for per-item behavior storage.
     *
     * This keeps the existing custom table authoritative for item identity while documenting exactly how the
     * future behavior payload should be added, read, and rolled back.
     *
     * @return array<string, mixed> Structured storage and migration contract.
     * @since 1.0.3
     */
    public function get_item_behavior_storage_contract() {
        return array(
            'schema_version' => 1,
            'current_storage' => array(
                'type' => 'custom_table',
                'table_suffix' => 'cwp_chat_bubbles_items',
                'columns' => array(
                    'id',
                    'platform',
                    'enabled',
                    'label',
                    'contact_value',
                    'qr_code_id',
                    'sort_order',
                ),
            ),
            'selected_strategy' => array(
                'type' => 'custom_table_column',
                'table_suffix' => 'cwp_chat_bubbles_items',
                'column' => 'behavior_settings',
                'column_type' => 'longtext',
                'encoding' => 'php_serialized_array',
                'default_storage' => null,
            ),
            'default_behavior' => $this->get_default_item_behavior_settings(),
            'field_contract' => array(
                'interaction_mode' => array(
                    'default' => 'auto',
                    'allowed_values' => array('auto', 'direct_link', 'qr_modal'),
                    'notes' => array(
                        'auto preserves the current behavior: QR modal when a QR code exists, otherwise direct link',
                        'direct_link bypasses the QR modal even when a QR code exists',
                        'qr_modal forces the QR modal first when a QR code exists and falls back to direct link when it does not',
                    ),
                ),
                'prefill_message' => array(
                    'default' => '',
                    'max_length' => 500,
                    'notes' => array(
                        'Store the raw operator-authored message at the item level',
                        'Frontend platform integrations decide whether and how the message is appended to outbound links',
                    ),
                ),
            ),
            'migration' => array(
                'forward' => array(
                    'Add the nullable behavior_settings longtext column with dbDelta and bump the item-table db version',
                    'Do not backfill every row; treat NULL or empty payloads as the normalized default behavior',
                    'Update CRUD and admin UI to write only the normalized payload for items that opt into non-default behavior',
                ),
                'fallback_behavior' => array(
                    'Missing, empty, or malformed behavior_settings payloads resolve to the default behavior contract',
                    'Existing installs keep the current QR-versus-link flow until an item is edited and saved with new behavior data',
                ),
                'rollback' => array(
                    'Older plugin versions ignore the extra longtext column and continue reading the legacy item fields',
                    'Do not delete or rewrite existing core columns during the first rollout',
                ),
            ),
        );
    }

    /**
     * Decode raw per-item behavior settings from an array or stored string payload.
     *
     * @param mixed $behavior_settings Raw behavior payload.
     * @return array<string, mixed> Decoded payload or empty array.
     * @since 1.0.3
     */
    private function decode_item_behavior_settings($behavior_settings) {
        if (is_array($behavior_settings)) {
            return $behavior_settings;
        }

        if (!is_string($behavior_settings)) {
            return array();
        }

        $behavior_settings = trim($behavior_settings);

        if ('' === $behavior_settings) {
            return array();
        }

        $decoded_json = json_decode($behavior_settings, true);
        if (is_array($decoded_json)) {
            return $decoded_json;
        }

        $decoded_serialized = @unserialize($behavior_settings);
        if (is_array($decoded_serialized)) {
            return $decoded_serialized;
        }

        return array();
    }

    /**
     * Normalize a raw database item row so downstream callers always see the stable behavior payload shape.
     *
     * @param array<string, mixed> $item Raw item row.
     * @return array<string, mixed> Normalized item row.
     * @since 1.0.3
     */
    private function normalize_item_record($item) {
        if (!is_array($item)) {
            return array();
        }

        $item['behavior_settings'] = $this->get_item_behavior_settings($item);

        return $item;
    }

    /**
     * Encode per-item behavior settings for storage.
     *
     * Defaults are stored as NULL so existing installs keep the legacy behavior without unnecessary payload churn.
     *
     * @param mixed $behavior_settings Raw behavior payload.
     * @return string|null Serialized payload or null for defaults.
     * @since 1.0.3
     */
    private function encode_item_behavior_settings($behavior_settings) {
        $normalized = $this->normalize_item_behavior_settings($behavior_settings);

        if ($normalized === $this->get_default_item_behavior_settings()) {
            return null;
        }

        return serialize($normalized);
    }

    /**
     * Get dynamic format strings for the item insert/update payload.
     *
     * @param array<string, mixed> $data Sanitized item payload.
     * @return array<int, string> Format strings in the same order as the data keys.
     * @since 1.0.3
     */
    private function get_item_column_formats($data) {
        $formats = array();

        foreach (array_keys($data) as $key) {
            switch ($key) {
                case 'enabled':
                case 'qr_code_id':
                case 'sort_order':
                    $formats[] = '%d';
                    break;

                default:
                    $formats[] = '%s';
                    break;
            }
        }

        return $formats;
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
     * @param mixed $qr_code_path Old QR code path, URL, or attachment ID
     * @return int WordPress media ID or 0 if failed
     * @since 1.0.0
     */
    private function migrate_qr_code($qr_code_path) {
        if (empty($qr_code_path)) {
            return 0;
        }

        // If it's already a numeric attachment ID, validate and return it
        if (is_numeric($qr_code_path)) {
            $attachment_id = absint($qr_code_path);
            if ($attachment_id > 0 && wp_get_attachment_url($attachment_id)) {
                return $attachment_id;
            }
            return 0;
        }

        // If it's a URL, try to find matching attachment in media library
        if (filter_var($qr_code_path, FILTER_VALIDATE_URL)) {
            $attachment_id = $this->get_attachment_id_by_url($qr_code_path);
            if ($attachment_id > 0) {
                return $attachment_id;
            }
        }

        // If it's a file path, try to find by filename in media library
        if (is_string($qr_code_path) && !filter_var($qr_code_path, FILTER_VALIDATE_URL)) {
            $filename = basename($qr_code_path);
            $attachment_id = $this->get_attachment_id_by_filename($filename);
            if ($attachment_id > 0) {
                return $attachment_id;
            }
        }

        // Graceful degradation - return 0 if no match found
        return 0;
    }

    /**
     * Get attachment ID by URL
     *
     * @param string $url Attachment URL
     * @return int Attachment ID or 0 if not found
     * @since 1.0.0
     */
    private function get_attachment_id_by_url($url) {
        global $wpdb;

        // Try using WordPress function first (available since WP 4.0)
        if (function_exists('attachment_url_to_postid')) {
            $attachment_id = attachment_url_to_postid($url);
            if ($attachment_id > 0) {
                return $attachment_id;
            }
        }

        // Fallback: query by guid
        $attachment_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment' LIMIT 1",
                $url
            )
        );

        return $attachment_id ? absint($attachment_id) : 0;
    }

    /**
     * Get attachment ID by filename
     *
     * @param string $filename File name to search for
     * @return int Attachment ID or 0 if not found
     * @since 1.0.0
     */
    private function get_attachment_id_by_filename($filename) {
        global $wpdb;

        // Search for attachment by filename in post meta
        $attachment_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_wp_attached_file' 
                AND meta_value LIKE %s 
                LIMIT 1",
                '%' . $wpdb->esc_like($filename)
            )
        );

        if ($attachment_id && wp_get_attachment_url($attachment_id)) {
            return absint($attachment_id);
        }

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
