<?php
/**
 * Data Service Class
 *
 * Unified data service to eliminate duplicate queries between Frontend and Assets classes
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles Data Service Class
 *
 * @since 1.0.0
 */
class CWP_Chat_Bubbles_Data_Service {

    /**
     * Instance of this class
     *
     * @var CWP_Chat_Bubbles_Data_Service
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
     * Settings instance
     *
     * @var CWP_Chat_Bubbles_Settings
     * @since 1.0.0
     */
    private $settings;

    /**
     * Get instance
     *
     * @return CWP_Chat_Bubbles_Data_Service
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
        $this->settings = CWP_Chat_Bubbles_Settings::get_instance();
    }

    /**
     * Get processed frontend data with comprehensive caching
     * This replaces both get_all_items() calls and get_frontend_platform_data()
     *
     * @return array|false Processed frontend data or false if no enabled items
     * @since 1.0.0
     */
    public function get_frontend_data() {
        // Create cache key based on data version and settings
        $data_version = $this->get_data_version();
        $settings_hash = $this->get_settings_hash();
        $cache_key = 'cwp_frontend_complete_v' . $data_version . '_s' . $settings_hash;
        
        // Try to get cached data first
        $cached_data = wp_cache_get($cache_key, 'cwp_chat_bubbles');
        if (false !== $cached_data) {
            return $cached_data;
        }

        // Get enabled items (this will use the new caching in Items Manager)
        $enabled_items = $this->items_manager->get_all_items(true);
        
        if (empty($enabled_items)) {
            // Cache the empty result for 1 hour to avoid repeated queries
            wp_cache_set($cache_key, false, 'cwp_chat_bubbles', HOUR_IN_SECONDS);
            return false;
        }

        // Process items with all required data for both template and JavaScript
        $processed_data = array(
            'items' => array(),
            'settings' => array(
                'position' => $this->settings->get_option('position', 'bottom-right'),
                'main_button_color' => $this->settings->get_option('main_button_color', '#52BA00'),
                'animation_enabled' => $this->settings->get_option('animation_enabled', true),
                'show_labels' => $this->settings->should_show_labels(),
                'device_visibility' => $this->settings->get_device_visibility(),
                'behavior' => $this->settings->get_behavior_settings(),
                'schedule' => $this->settings->get_schedule_settings(),
                'analytics' => $this->settings->get_analytics_settings(),
                'appearance' => $this->settings->get_appearance_settings(),
            ),
            'support_icon' => $this->settings->get_main_icon_url(),
            'cancel_icon' => CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/cancel.svg'
        );

        foreach ($enabled_items as $item) {
            $processed_item = array(
                'id' => $item['id'],
                'platform' => $item['platform'],
                'label' => $item['label'],
                'contact_value' => $item['contact_value'],
                'enabled' => $item['enabled'],
                'qr_code_id' => $item['qr_code_id'],
                'sort_order' => $item['sort_order'],
                // Pre-processed data for performance
                'platform_url' => $this->items_manager->generate_platform_url($item['platform'], $item),
                'platform_icon' => $this->items_manager->get_platform_icon_url($item['platform']),
                'platform_color' => $this->items_manager->get_platform_color($item['platform']),
                'qr_code_url' => !empty($item['qr_code_id']) ? wp_get_attachment_url($item['qr_code_id']) : '',
                'has_qr' => !empty($item['qr_code_id'])
            );
            
            $processed_data['items'][] = $processed_item;
        }

        // Cache the processed data for 1 hour
        wp_cache_set($cache_key, $processed_data, 'cwp_chat_bubbles', HOUR_IN_SECONDS);

        return $processed_data;
    }

    /**
     * Get frontend data optimized for JavaScript
     *
     * @return array Frontend data for JavaScript
     * @since 1.0.0
     */
    public function get_frontend_js_data() {
        $frontend_data = $this->get_frontend_data();
        
        if (false === $frontend_data) {
            return array();
        }

        // Transform for JavaScript consumption
        $js_data = array();
        foreach ($frontend_data['items'] as $item) {
            $js_data[$item['id']] = array(
                'id' => $item['id'],
                'platform' => $item['platform'],
                'label' => $item['label'],
                'url' => $item['platform_url'],
                'icon' => $item['platform_icon'],
                'qr_code' => $item['qr_code_url'],
                'has_qr' => $item['has_qr']
            );
        }

        return $js_data;
    }

    /**
     * Check if should load on current page (unified logic)
     *
     * @return bool Whether to load on current page
     * @since 1.0.0
     */
    public function should_load_on_current_page() {
        // Plugin must be enabled
        if (!$this->settings->is_enabled()) {
            return false;
        }

        // Auto-loading must be enabled
        if (!$this->settings->is_auto_load_enabled()) {
            return false;
        }

        // Don't load in admin
        if (is_admin()) {
            return false;
        }

        // If every device class is disabled, there's nothing to render.
        if (!$this->has_visible_devices()) {
            return false;
        }

        if (!$this->is_available_for_schedule()) {
            return false;
        }

        // Check excluded pages
        $excluded_pages = $this->settings->get_option('exclude_pages', array());
        if (!empty($excluded_pages) && is_page()) {
            $current_page_id = get_the_ID();
            if (in_array($current_page_id, $excluded_pages)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get data version for cache invalidation
     *
     * @return string Data version
     * @since 1.0.0
     */
    private function get_data_version() {
        // Get data version from option directly to avoid reflection
        $version = wp_cache_get('cwp_chat_bubbles_data_version', 'cwp_chat_bubbles');
        
        if (false === $version) {
            // Get version from database or create new one
            $version = get_option('cwp_chat_bubbles_data_version', '1');
            wp_cache_set('cwp_chat_bubbles_data_version', $version, 'cwp_chat_bubbles', DAY_IN_SECONDS);
        }
        
        return $version;
    }

    /**
     * Get settings hash for cache invalidation when settings change
     *
     * @return string Settings hash
     * @since 1.0.0
     */
    private function get_settings_hash() {
        $relevant_settings = array(
            'position' => $this->settings->get_option('position', 'bottom-right'),
            'main_button_color' => $this->settings->get_option('main_button_color', '#52BA00'),
            'animation_enabled' => $this->settings->get_option('animation_enabled', true),
            'show_labels' => $this->settings->should_show_labels(),
            'custom_main_icon' => $this->settings->get_option('custom_main_icon', 0),
            'device_visibility' => $this->settings->get_device_visibility(),
            'behavior' => $this->settings->get_behavior_settings(),
            'schedule' => $this->settings->get_schedule_settings(),
            'analytics' => $this->settings->get_analytics_settings(),
            'appearance' => $this->settings->get_appearance_settings(),
        );
        
        return substr(md5(serialize($relevant_settings)), 0, 8);
    }

    /**
     * Check whether at least one device category can display the bubble.
     *
     * @return bool Whether any device visibility flag is enabled.
     * @since 1.0.3
     */
    private function has_visible_devices() {
        foreach ($this->settings->get_device_visibility() as $is_visible) {
            if ($is_visible) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the widget is currently available according to the business-hours schedule.
     *
     * @param DateTimeImmutable|null $current_time Optional override time for deterministic checks.
     * @return bool Whether the widget should be considered open right now.
     * @since 1.0.3
     */
    public function is_available_for_schedule($current_time = null) {
        $schedule = $this->settings->get_schedule_settings();

        if (empty($schedule['enabled'])) {
            return true;
        }

        if (!isset($schedule['closed_behavior']) || 'hide' !== $schedule['closed_behavior']) {
            return true;
        }

        $timezone = $this->resolve_schedule_timezone($schedule);
        $current = $current_time instanceof DateTimeImmutable
            ? $current_time->setTimezone($timezone)
            : new DateTimeImmutable('now', $timezone);

        $day_key = strtolower($current->format('D'));
        if (!isset($schedule['weekly_hours'][ $day_key ]) || !is_array($schedule['weekly_hours'][ $day_key ])) {
            return false;
        }

        $day_schedule = $schedule['weekly_hours'][ $day_key ];
        $current_minutes = ((int) $current->format('H') * 60) + (int) $current->format('i');

        if ($this->matches_schedule_window($day_schedule, $current_minutes, false)) {
            return true;
        }

        $previous_day_key = $this->get_previous_day_key($day_key);
        if (isset($schedule['weekly_hours'][ $previous_day_key ])) {
            return $this->matches_schedule_window($schedule['weekly_hours'][ $previous_day_key ], $current_minutes, true);
        }

        return false;
    }

    /**
     * Resolve the timezone used for business-hours evaluation.
     *
     * @param array $schedule Normalized schedule settings.
     * @return DateTimeZone Timezone object.
     * @since 1.0.3
     */
    private function resolve_schedule_timezone($schedule) {
        $timezone_string = !empty($schedule['timezone'])
            ? $schedule['timezone']
            : get_option('timezone_string', 'UTC');

        if (empty($timezone_string)) {
            $timezone_string = 'UTC';
        }

        try {
            return new DateTimeZone($timezone_string);
        } catch (Exception $exception) {
            return new DateTimeZone('UTC');
        }
    }

    /**
     * Convert an HH:MM string into minutes after midnight.
     *
     * @param string $time Time string.
     * @return int Minutes after midnight.
     * @since 1.0.3
     */
    private function time_to_minutes($time) {
        $parts = explode(':', (string) $time);
        $hours = isset($parts[0]) ? (int) $parts[0] : 0;
        $minutes = isset($parts[1]) ? (int) $parts[1] : 0;

        return ($hours * 60) + $minutes;
    }

    /**
     * Check whether the current minutes value matches a schedule window.
     *
     * @param array $day_schedule Day schedule settings.
     * @param int   $current_minutes Current minutes after midnight.
     * @param bool  $after_midnight Whether this check is evaluating a previous day's overnight spill.
     * @return bool Whether the schedule window matches.
     * @since 1.0.3
     */
    private function matches_schedule_window($day_schedule, $current_minutes, $after_midnight) {
        if (empty($day_schedule['enabled'])) {
            return false;
        }

        $open_minutes = $this->time_to_minutes($day_schedule['open'] ?? '00:00');
        $close_minutes = $this->time_to_minutes($day_schedule['close'] ?? '00:00');

        if ($open_minutes === $close_minutes) {
            return false;
        }

        if ($open_minutes < $close_minutes) {
            return !$after_midnight && $current_minutes >= $open_minutes && $current_minutes < $close_minutes;
        }

        if ($after_midnight) {
            return $current_minutes < $close_minutes;
        }

        return $current_minutes >= $open_minutes;
    }

    /**
     * Get the previous weekday key used by schedule settings.
     *
     * @param string $day_key Current weekday key.
     * @return string Previous weekday key.
     * @since 1.0.3
     */
    private function get_previous_day_key($day_key) {
        $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
        $index = array_search($day_key, $days, true);

        if (false === $index) {
            return 'sun';
        }

        return $days[($index + 6) % 7];
    }
}
