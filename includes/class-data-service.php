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
     * Get a migration-oriented view of the current display-rule state.
     *
     * This keeps the quick-win settings authoritative while giving future contextual targeting work a
     * deterministic mapping and runtime snapshot to build from.
     *
     * @param DateTimeImmutable|null $current_time Optional override time for deterministic schedule checks.
     * @return array<string, mixed> Display-rule contract plus runtime state.
     * @since 1.0.3
     */
    public function get_display_rules_context($current_time = null) {
        return array(
            'contract' => $this->settings->get_display_rules_migration_contract(),
            'runtime' => array(
                'auto_load_enabled' => $this->settings->is_auto_load_enabled(),
                'has_visible_devices' => $this->has_visible_devices(),
                'schedule_allows_display' => $this->is_available_for_schedule($current_time),
                'excluded_pages' => array_values(array_map('absint', (array) $this->settings->get_option('exclude_pages', array()))),
            ),
        );
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

        if (!$this->matches_contextual_targeting()) {
            return false;
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
     * Evaluate the normalized contextual targeting schema against the current request.
     *
     * Precedence:
     * 1. Explicit exclude rules always win.
     * 2. If no include rules are configured, the contextual targeting layer allows display.
     * 3. When include rules exist, operator "any" requires one populated include bucket to match,
     *    while operator "all" requires every populated include bucket to match.
     *
     * @return bool Whether contextual targeting allows the current request.
     * @since 1.0.3
     */
    private function matches_contextual_targeting() {
        $targeting = $this->settings->get_targeting_settings();
        if (empty($targeting['rules']) || !is_array($targeting['rules'])) {
            return true;
        }

        $pages = isset($targeting['rules']['pages']) && is_array($targeting['rules']['pages'])
            ? $targeting['rules']['pages']
            : array();
        $post_types = isset($targeting['rules']['post_types']) && is_array($targeting['rules']['post_types'])
            ? $targeting['rules']['post_types']
            : array();
        $special_pages = isset($targeting['rules']['special_pages']) && is_array($targeting['rules']['special_pages'])
            ? $targeting['rules']['special_pages']
            : array();

        $context = $this->get_contextual_targeting_context();

        if (
            (!empty($context['page_id']) && in_array($context['page_id'], (array) ($pages['exclude'] ?? array()), true))
            || (!empty($context['post_type']) && in_array($context['post_type'], (array) ($post_types['exclude'] ?? array()), true))
            || $this->matches_special_page_state($special_pages, $context['special_pages'], 'exclude')
        ) {
            return false;
        }

        $include_matches = array();

        if (!empty($pages['include'])) {
            $include_matches[] = !empty($context['page_id']) && in_array($context['page_id'], $pages['include'], true);
        }

        if (!empty($post_types['include'])) {
            $include_matches[] = !empty($context['post_type']) && in_array($context['post_type'], $post_types['include'], true);
        }

        if ($this->has_special_page_state($special_pages, 'include')) {
            $include_matches[] = $this->matches_special_page_state($special_pages, $context['special_pages'], 'include');
        }

        if (empty($include_matches)) {
            return true;
        }

        $operator = isset($targeting['operator']) ? $targeting['operator'] : 'all';

        if ('any' === $operator) {
            return in_array(true, $include_matches, true);
        }

        return !in_array(false, $include_matches, true);
    }

    /**
     * Build the current request context used by contextual targeting.
     *
     * @return array<string, mixed> Current page, post-type, and special-page context.
     * @since 1.0.3
     */
    private function get_contextual_targeting_context() {
        $page_id = is_page() ? absint(get_the_ID()) : 0;
        $post_type = '';

        if (function_exists('get_post_type')) {
            $resolved_post_type = get_post_type($page_id ?: null);
            $post_type = is_string($resolved_post_type) ? $resolved_post_type : '';
        }

        return array(
            'page_id' => $page_id,
            'post_type' => $post_type,
            'special_pages' => $this->get_current_special_page_contexts(),
        );
    }

    /**
     * Get the active special-page contexts for the current request.
     *
     * Optional integrations are guarded with function_exists checks so environments without those plugins remain safe.
     *
     * @return array<int, string> Active special-page keys.
     * @since 1.0.3
     */
    private function get_current_special_page_contexts() {
        $contexts = array();

        if (function_exists('is_front_page') && is_front_page()) {
            $contexts[] = 'front_page';
        }

        if (function_exists('is_home') && is_home()) {
            $contexts[] = 'blog_index';
        }

        if (function_exists('is_search') && is_search()) {
            $contexts[] = 'search';
        }

        if (function_exists('is_404') && is_404()) {
            $contexts[] = '404';
        }

        if (function_exists('is_archive') && is_archive()) {
            $contexts[] = 'archive';
        }

        if (function_exists('is_shop') && is_shop()) {
            $contexts[] = 'archive';
        }

        return array_values(array_unique($contexts));
    }

    /**
     * Check whether a special-page targeting map contains any key in the requested state.
     *
     * @param array  $special_pages Targeting special-page map.
     * @param string $state Desired state.
     * @return bool Whether any special page uses that state.
     * @since 1.0.3
     */
    private function has_special_page_state($special_pages, $state) {
        foreach ($special_pages as $value) {
            if ($state === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether any active special-page context matches the requested state.
     *
     * @param array $special_pages Targeting special-page map.
     * @param array $active_contexts Active special-page keys for the current request.
     * @param string $state Desired state.
     * @return bool Whether any active special page matches that state.
     * @since 1.0.3
     */
    private function matches_special_page_state($special_pages, $active_contexts, $state) {
        foreach ($active_contexts as $context_key) {
            if (isset($special_pages[$context_key]) && $state === $special_pages[$context_key]) {
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
