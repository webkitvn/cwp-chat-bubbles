<?php
/**
 * Settings Class
 *
 * Handles WordPress Settings API integration
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles Settings Class
 *
 * @since 1.0.0
 */
class CWP_Chat_Bubbles_Settings {

    /**
     * Instance of this class
     *
     * @var CWP_Chat_Bubbles_Settings
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Settings option name
     *
     * @var string
     * @since 1.0.0
     */
    private $option_name = 'cwp_chat_bubbles_options';

    /**
     * Get instance
     *
     * @return CWP_Chat_Bubbles_Settings
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
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register plugin settings
     *
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting(
            'cwp_chat_bubbles_settings',
            $this->option_name,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_options'),
                'default' => $this->get_default_options()
            )
        );
    }

    /**
     * Get default options
     *
     * @return array Default options
     * @since 1.0.0
     */
    public function get_default_options() {
        return array(
            // General settings
            'enabled' => true,
            'auto_load' => true,
            'position' => 'bottom-right',
            'custom_main_icon' => 0,            // Custom main icon attachment ID, 0 = use default
            
            // Display settings
            'main_button_color' => '#52BA00',
            'animation_enabled' => true,
            'show_labels' => true,              // Global setting for all items
            'offset_x' => 0,                    // Horizontal offset in pixels (-200 to 200)
            'offset_y' => 0,                    // Vertical offset in pixels (-200 to 200)
            
            // Advanced settings
            'custom_css' => '',
            'load_on_mobile' => true,
            'device_visibility' => $this->get_default_device_visibility(),
            'behavior' => $this->get_default_behavior_settings(),
            'schedule' => $this->get_default_schedule_settings(),
            'appearance' => $this->get_default_appearance_settings(),
            'exclude_pages' => array()
        );
    }

    /**
     * Sanitize and validate options
     *
     * @param array $options Raw options from form
     * @return array Sanitized options
     * @since 1.0.0
     */
    public function sanitize_options($options) {
        $sanitized = array();

        // Sanitize general settings
        // Note: For checkboxes, if not present in POST data, it means unchecked
        $sanitized['enabled'] = isset($options['enabled']) ? (bool) $options['enabled'] : false;
        $sanitized['auto_load'] = isset($options['auto_load']) ? (bool) $options['auto_load'] : false;
        $sanitized['position'] = isset($options['position']) ? sanitize_text_field($options['position']) : 'bottom-right';
        
        // Sanitize custom main icon
        $sanitized['custom_main_icon'] = isset($options['custom_main_icon']) ? absint($options['custom_main_icon']) : 0;
        // Validate that attachment exists if not 0
        if ($sanitized['custom_main_icon'] > 0 && !wp_get_attachment_url($sanitized['custom_main_icon'])) {
            $sanitized['custom_main_icon'] = 0; // Reset to default if attachment doesn't exist
        }

        // Validate position
        $valid_positions = array('bottom-right', 'bottom-left', 'top-right', 'top-left');
        if (!in_array($sanitized['position'], $valid_positions)) {
            $sanitized['position'] = 'bottom-right';
        }

        // Note: Platform-specific settings are now handled by Items Manager custom table

        // Sanitize display settings
        $sanitized['main_button_color'] = isset($options['main_button_color'])
            ? sanitize_hex_color($options['main_button_color'])
            : '#52BA00';
            
        $sanitized['animation_enabled'] = isset($options['animation_enabled'])
            ? (bool) $options['animation_enabled']
            : false;
            
        $sanitized['show_labels'] = isset($options['show_labels'])
            ? (bool) $options['show_labels']
            : false;

        // Sanitize offset settings
        $sanitized['offset_x'] = isset($options['offset_x']) 
            ? max(-200, min(200, (int) $options['offset_x'])) 
            : 0;
            
        $sanitized['offset_y'] = isset($options['offset_y']) 
            ? max(-200, min(200, (int) $options['offset_y'])) 
            : 0;

        // Sanitize advanced settings - Enhanced CSS validation
        $sanitized['custom_css'] = '';
        if (isset($options['custom_css']) && !empty($options['custom_css'])) {
            $css = wp_strip_all_tags($options['custom_css']);
            
            // Additional CSS security - remove potentially dangerous functions
            $dangerous_patterns = array(
                '/javascript\s*:/i',
                '/expression\s*\(/i',
                '/url\s*\(\s*["\']?\s*javascript:/i',
                '/url\s*\(\s*["\']?\s*data:/i',
                '/import\s*["\']?/i',
                '/@import/i',
                '/behavior\s*:/i',
                '/binding\s*:/i',
                '/mozbinding\s*:/i'
            );
            
            foreach ($dangerous_patterns as $pattern) {
                $css = preg_replace($pattern, '', $css);
            }
            
            // Limit CSS length to prevent abuse
            if (strlen($css) > 5000) {
                $css = substr($css, 0, 5000);
            }
            
            $sanitized['custom_css'] = $css;
        }
            
        $sanitized['device_visibility'] = $this->sanitize_device_visibility($options);
        $sanitized['load_on_mobile'] = $sanitized['device_visibility']['mobile'];
        $sanitized['behavior'] = $this->sanitize_behavior_settings($options);
        $sanitized['schedule'] = $this->sanitize_schedule_settings($options);
        $sanitized['appearance'] = $this->sanitize_appearance_settings($options);

        // Sanitize exclude pages
        $sanitized['exclude_pages'] = array();
        if (isset($options['exclude_pages']) && is_array($options['exclude_pages'])) {
            foreach ($options['exclude_pages'] as $page_id) {
                $sanitized['exclude_pages'][] = absint($page_id);
            }
        }

        return $sanitized;
    }

    /**
     * Get all plugin options
     *
     * @return array Plugin options
     * @since 1.0.0
     */
    public function get_options() {
        $options = get_option($this->option_name, $this->get_default_options());

        if (!is_array($options)) {
            return $this->get_default_options();
        }

        return $this->normalize_options($options);
    }

    /**
     * Update plugin options
     *
     * @param array $options New options
     * @return bool Always returns true unless there's an error
     * @since 1.0.0
     */
    public function update_options($options) {
        $sanitized_options = $this->sanitize_options($options);
        
        // update_option returns false if the value hasn't changed, not if there's an error
        // We always want to clear caches and return success for UX purposes
        update_option($this->option_name, $sanitized_options);
        
        // Always clear frontend caches when settings are processed
        $this->clear_frontend_caches();
        
        // Return true to indicate successful processing
        return true;
    }

    /**
     * Clear frontend caches when settings change
     *
     * @since 1.0.0
     */
    private function clear_frontend_caches() {
        // Clear specific cache keys first
        $cache_keys_to_clear = array(
            'cwp_chat_bubbles_data_version',
            'cwp_chat_bubbles_frontend_data'
        );
        
        foreach ($cache_keys_to_clear as $key) {
            wp_cache_delete($key, 'cwp_chat_bubbles');
        }
        
        // Try to clear cache group if function exists (not all cache systems support this)
        if (function_exists('wp_cache_delete_group')) {
            wp_cache_delete_group('cwp_chat_bubbles');
        }
    }

    /**
     * Get specific option
     *
     * @param string $key Option key (supports dot notation for nested values)
     * @param mixed $default Default value
     * @return mixed Option value
     * @since 1.0.0
     */
    public function get_option($key, $default = null) {
        $options = $this->get_options();
        
        // Support dot notation for nested values
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $options;
            
            foreach ($keys as $k) {
                if (!isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }
            
            return $value;
        }
        
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * Check if plugin is enabled
     *
     * @return bool Whether plugin is enabled
     * @since 1.0.0
     */
    public function is_enabled() {
        return (bool) $this->get_option('enabled', true);
    }

    /**
     * Check if auto-loading is enabled
     *
     * @return bool Whether auto-loading is enabled
     * @since 1.0.0
     */
    public function is_auto_load_enabled() {
        return (bool) $this->get_option('auto_load', true);
    }

    /**
     * Check if labels should be shown
     *
     * @return bool Whether labels should be shown for all items
     * @since 1.0.0
     */
    public function should_show_labels() {
        return (bool) $this->get_option('show_labels', true);
    }

    /**
     * Get normalized device visibility settings.
     *
     * @return array<string, bool> Device visibility map
     * @since 1.0.3
     */
    public function get_device_visibility() {
        return $this->get_option('device_visibility', $this->get_default_device_visibility());
    }

    /**
     * Check whether chat bubbles should be shown for a device type.
     *
     * @param string $device Device key.
     * @return bool Whether the device is enabled.
     * @since 1.0.3
     */
    public function is_device_enabled($device) {
        $visibility = $this->get_device_visibility();

        return array_key_exists($device, $visibility)
            ? (bool) $visibility[$device]
            : true;
    }

    /**
     * Check whether chat bubbles should load on mobile devices.
     *
     * @return bool Whether mobile visibility is enabled.
     * @since 1.0.3
     */
    public function should_load_on_mobile() {
        return $this->is_device_enabled('mobile');
    }

    /**
     * Get normalized global behavior settings.
     *
     * @return array<string, mixed> Behavior settings map.
     * @since 1.0.3
     */
    public function get_behavior_settings() {
        return $this->get_option('behavior', $this->get_default_behavior_settings());
    }

    /**
     * Get a single behavior setting.
     *
     * @param string $key Behavior key.
     * @param mixed  $default Fallback value.
     * @return mixed Behavior setting value.
     * @since 1.0.3
     */
    public function get_behavior_setting($key, $default = null) {
        $behavior = $this->get_behavior_settings();

        return array_key_exists($key, $behavior)
            ? $behavior[$key]
            : $default;
    }

    /**
     * Get normalized schedule settings.
     *
     * @return array<string, mixed> Schedule settings map.
     * @since 1.0.3
     */
    public function get_schedule_settings() {
        return $this->get_option('schedule', $this->get_default_schedule_settings());
    }

    /**
     * Get a single schedule setting.
     *
     * @param string $key Schedule key.
     * @param mixed  $default Fallback value.
     * @return mixed Schedule setting value.
     * @since 1.0.3
     */
    public function get_schedule_setting($key, $default = null) {
        $schedule = $this->get_schedule_settings();

        return array_key_exists($key, $schedule)
            ? $schedule[$key]
            : $default;
    }

    /**
     * Get normalized appearance settings.
     *
     * @return array<string, mixed> Appearance settings map.
     * @since 1.0.3
     */
    public function get_appearance_settings() {
        return $this->get_option('appearance', $this->get_default_appearance_settings());
    }

    /**
     * Get a single appearance setting.
     *
     * @param string $key Appearance key.
     * @param mixed  $default Fallback value.
     * @return mixed Appearance setting value.
     * @since 1.0.3
     */
    public function get_appearance_setting($key, $default = null) {
        $appearance = $this->get_appearance_settings();

        return array_key_exists($key, $appearance)
            ? $appearance[$key]
            : $default;
    }

    /**
     * Get main icon URL
     *
     * @return string Main icon URL (custom or default)
     * @since 1.0.2
     */
    public function get_main_icon_url() {
        $custom_icon_id = $this->get_option('custom_main_icon', 0);
        
        if ($custom_icon_id > 0) {
            $custom_icon_url = wp_get_attachment_url($custom_icon_id);
            if ($custom_icon_url) {
                return $custom_icon_url;
            }
        }
        
        return CWP_CHAT_BUBBLES_PLUGIN_URL . 'assets/images/support.svg';
    }

    /**
     * Get the default device visibility settings.
     *
     * @return array<string, bool> Default device visibility map.
     * @since 1.0.3
     */
    private function get_default_device_visibility() {
        return array(
            'desktop' => true,
            'tablet' => true,
            'mobile' => true,
        );
    }

    /**
     * Get the default global behavior settings.
     *
     * @return array<string, mixed> Default behavior settings.
     * @since 1.0.3
     */
    private function get_default_behavior_settings() {
        return array(
            'default_state' => 'closed',
            'display_delay' => 0,
            'scroll_trigger_percent' => 0,
            'dismiss_for_session' => false,
        );
    }

    /**
     * Get the default business-hours schedule settings.
     *
     * @return array<string, mixed> Default schedule settings.
     * @since 1.0.3
     */
    private function get_default_schedule_settings() {
        $weekly_hours = array();

        foreach ($this->get_schedule_days() as $day_key => $day_label) {
            $weekly_hours[ $day_key ] = array(
                'enabled' => in_array($day_key, array('mon', 'tue', 'wed', 'thu', 'fri'), true),
                'open' => '09:00',
                'close' => '17:00',
            );
        }

        return array(
            'enabled' => false,
            'timezone' => '',
            'closed_behavior' => 'hide',
            'weekly_hours' => $weekly_hours,
        );
    }

    /**
     * Get the default advanced appearance settings.
     *
     * @return array<string, mixed> Default appearance settings.
     * @since 1.0.3
     */
    private function get_default_appearance_settings() {
        return array(
            'bubble_size' => 60,
            'panel_width' => 200,
            'panel_radius' => 10,
            'modal_width' => 300,
            'modal_radius' => 10,
            'item_padding_y' => 5,
            'item_padding_x' => 10,
            'label_text_color' => '#333333',
            'z_index' => 1000,
        );
    }

    /**
     * Normalize stored options so older installs expose the latest schema.
     *
     * @param array $options Raw stored options.
     * @return array Normalized options.
     * @since 1.0.3
     */
    private function normalize_options($options) {
        $normalized = array_replace_recursive($this->get_default_options(), $options);
        $normalized['device_visibility'] = $this->normalize_device_visibility($options);
        $normalized['load_on_mobile'] = $normalized['device_visibility']['mobile'];
        $normalized['behavior'] = $this->normalize_behavior_settings($options);
        $normalized['schedule'] = $this->normalize_schedule_settings($options);
        $normalized['appearance'] = $this->normalize_appearance_settings($options);

        return $normalized;
    }

    /**
     * Normalize device visibility from stored options while preserving legacy mobile behavior.
     *
     * @param array $options Raw stored options.
     * @return array<string, bool> Normalized device visibility map.
     * @since 1.0.3
     */
    private function normalize_device_visibility($options) {
        $default_visibility = $this->get_default_device_visibility();

        if (!is_array($options)) {
            return $default_visibility;
        }

        $stored_visibility = isset($options['device_visibility']) && is_array($options['device_visibility'])
            ? $options['device_visibility']
            : array();

        $normalized = array();
        foreach ($default_visibility as $device => $default) {
            if (array_key_exists($device, $stored_visibility)) {
                $normalized[$device] = (bool) $stored_visibility[$device];
                continue;
            }

            if ('mobile' === $device && array_key_exists('load_on_mobile', $options)) {
                $normalized[$device] = (bool) $options['load_on_mobile'];
                continue;
            }

            $normalized[$device] = $default;
        }

        return $normalized;
    }

    /**
     * Sanitize device visibility settings from submitted options.
     *
     * @param array $options Raw submitted options.
     * @return array<string, bool> Sanitized device visibility map.
     * @since 1.0.3
     */
    private function sanitize_device_visibility($options) {
        $default_visibility = $this->get_default_device_visibility();

        if (!is_array($options)) {
            return $default_visibility;
        }

        if (!isset($options['device_visibility']) || !is_array($options['device_visibility'])) {
            return array(
                'desktop' => true,
                'tablet' => true,
                'mobile' => isset($options['load_on_mobile']) ? (bool) $options['load_on_mobile'] : false,
            );
        }

        $sanitized = array();
        foreach ($default_visibility as $device => $default) {
            $sanitized[$device] = isset($options['device_visibility'][ $device ])
                ? (bool) $options['device_visibility'][ $device ]
                : false;
        }

        return $sanitized;
    }

    /**
     * Normalize behavior settings from stored options.
     *
     * @param array $options Raw stored options.
     * @return array<string, mixed> Normalized behavior settings.
     * @since 1.0.3
     */
    private function normalize_behavior_settings($options) {
        $defaults = $this->get_default_behavior_settings();

        if (!is_array($options) || !isset($options['behavior']) || !is_array($options['behavior'])) {
            return $defaults;
        }

        return array(
            'default_state' => in_array($options['behavior']['default_state'] ?? '', array('closed', 'open'), true)
                ? $options['behavior']['default_state']
                : $defaults['default_state'],
            'display_delay' => max(0, min(30, (int) ($options['behavior']['display_delay'] ?? $defaults['display_delay']))),
            'scroll_trigger_percent' => max(0, min(100, (int) ($options['behavior']['scroll_trigger_percent'] ?? $defaults['scroll_trigger_percent']))),
            'dismiss_for_session' => !empty($options['behavior']['dismiss_for_session']),
        );
    }

    /**
     * Sanitize behavior settings from submitted options.
     *
     * @param array $options Raw submitted options.
     * @return array<string, mixed> Sanitized behavior settings.
     * @since 1.0.3
     */
    private function sanitize_behavior_settings($options) {
        $defaults = $this->get_default_behavior_settings();

        if (!is_array($options) || !isset($options['behavior']) || !is_array($options['behavior'])) {
            return $defaults;
        }

        $default_state = isset($options['behavior']['default_state'])
            ? sanitize_text_field($options['behavior']['default_state'])
            : $defaults['default_state'];

        if (!in_array($default_state, array('closed', 'open'), true)) {
            $default_state = $defaults['default_state'];
        }

        return array(
            'default_state' => $default_state,
            'display_delay' => max(0, min(30, (int) ($options['behavior']['display_delay'] ?? $defaults['display_delay']))),
            'scroll_trigger_percent' => max(0, min(100, (int) ($options['behavior']['scroll_trigger_percent'] ?? $defaults['scroll_trigger_percent']))),
            'dismiss_for_session' => isset($options['behavior']['dismiss_for_session'])
                ? (bool) $options['behavior']['dismiss_for_session']
                : false,
        );
    }

    /**
     * Normalize schedule settings from stored options.
     *
     * @param array $options Raw stored options.
     * @return array<string, mixed> Normalized schedule settings.
     * @since 1.0.3
     */
    private function normalize_schedule_settings($options) {
        $defaults = $this->get_default_schedule_settings();

        if (!is_array($options) || !isset($options['schedule']) || !is_array($options['schedule'])) {
            return $defaults;
        }

        $schedule = $options['schedule'];
        $normalized = array(
            'enabled' => !empty($schedule['enabled']),
            'timezone' => $this->sanitize_schedule_timezone($schedule['timezone'] ?? ''),
            'closed_behavior' => 'hide',
            'weekly_hours' => array(),
        );

        foreach ($this->get_schedule_days() as $day_key => $day_label) {
            $day_defaults = $defaults['weekly_hours'][ $day_key ];
            $day_settings = isset($schedule['weekly_hours'][ $day_key ]) && is_array($schedule['weekly_hours'][ $day_key ])
                ? $schedule['weekly_hours'][ $day_key ]
                : array();

            $normalized['weekly_hours'][ $day_key ] = array(
                'enabled' => !empty($day_settings['enabled']),
                'open' => $this->sanitize_schedule_time($day_settings['open'] ?? $day_defaults['open'], $day_defaults['open']),
                'close' => $this->sanitize_schedule_time($day_settings['close'] ?? $day_defaults['close'], $day_defaults['close']),
            );
        }

        return $normalized;
    }

    /**
     * Sanitize schedule settings from submitted options.
     *
     * @param array $options Raw submitted options.
     * @return array<string, mixed> Sanitized schedule settings.
     * @since 1.0.3
     */
    private function sanitize_schedule_settings($options) {
        $defaults = $this->get_default_schedule_settings();

        if (!is_array($options) || !isset($options['schedule']) || !is_array($options['schedule'])) {
            return $defaults;
        }

        $schedule = $options['schedule'];
        $sanitized = array(
            'enabled' => isset($schedule['enabled']) ? (bool) $schedule['enabled'] : false,
            'timezone' => $this->sanitize_schedule_timezone($schedule['timezone'] ?? ''),
            'closed_behavior' => 'hide',
            'weekly_hours' => array(),
        );

        foreach ($this->get_schedule_days() as $day_key => $day_label) {
            $day_defaults = $defaults['weekly_hours'][ $day_key ];
            $day_settings = isset($schedule['weekly_hours'][ $day_key ]) && is_array($schedule['weekly_hours'][ $day_key ])
                ? $schedule['weekly_hours'][ $day_key ]
                : array();

            $sanitized['weekly_hours'][ $day_key ] = array(
                'enabled' => isset($day_settings['enabled']) ? (bool) $day_settings['enabled'] : false,
                'open' => $this->sanitize_schedule_time($day_settings['open'] ?? $day_defaults['open'], $day_defaults['open']),
                'close' => $this->sanitize_schedule_time($day_settings['close'] ?? $day_defaults['close'], $day_defaults['close']),
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize a schedule timezone string.
     *
     * @param string $timezone Raw timezone value.
     * @return string Sanitized timezone or empty string to use the site timezone.
     * @since 1.0.3
     */
    private function sanitize_schedule_timezone($timezone) {
        $timezone = sanitize_text_field($timezone);

        if ('' === $timezone) {
            return '';
        }

        try {
            new DateTimeZone($timezone);
            return $timezone;
        } catch (Exception $exception) {
            return '';
        }
    }

    /**
     * Sanitize a schedule time in HH:MM format.
     *
     * @param string $time Raw time value.
     * @param string $default Default time.
     * @return string Sanitized time string.
     * @since 1.0.3
     */
    private function sanitize_schedule_time($time, $default) {
        $time = sanitize_text_field($time);

        if (1 === preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
            return $time;
        }

        return $default;
    }

    /**
     * Get supported day keys for schedule settings.
     *
     * @return array<string, string> Day key to label map.
     * @since 1.0.3
     */
    private function get_schedule_days() {
        return array(
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'sun' => 'Sunday',
        );
    }

    /**
     * Normalize appearance settings from stored options.
     *
     * @param array $options Raw stored options.
     * @return array<string, mixed> Normalized appearance settings.
     * @since 1.0.3
     */
    private function normalize_appearance_settings($options) {
        $defaults = $this->get_default_appearance_settings();

        if (!is_array($options) || !isset($options['appearance']) || !is_array($options['appearance'])) {
            return $defaults;
        }

        return $this->sanitize_appearance_settings(
            array(
                'appearance' => $options['appearance'],
            )
        );
    }

    /**
     * Sanitize appearance settings from submitted options.
     *
     * @param array $options Raw submitted options.
     * @return array<string, mixed> Sanitized appearance settings.
     * @since 1.0.3
     */
    private function sanitize_appearance_settings($options) {
        $defaults = $this->get_default_appearance_settings();

        if (!is_array($options) || !isset($options['appearance']) || !is_array($options['appearance'])) {
            return $defaults;
        }

        $appearance = $options['appearance'];
        $label_text_color = isset($appearance['label_text_color'])
            ? sanitize_hex_color($appearance['label_text_color'])
            : $defaults['label_text_color'];

        if (empty($label_text_color)) {
            $label_text_color = $defaults['label_text_color'];
        }

        return array(
            'bubble_size' => max(48, min(96, (int) ($appearance['bubble_size'] ?? $defaults['bubble_size']))),
            'panel_width' => max(160, min(320, (int) ($appearance['panel_width'] ?? $defaults['panel_width']))),
            'panel_radius' => max(0, min(24, (int) ($appearance['panel_radius'] ?? $defaults['panel_radius']))),
            'modal_width' => max(240, min(420, (int) ($appearance['modal_width'] ?? $defaults['modal_width']))),
            'modal_radius' => max(0, min(24, (int) ($appearance['modal_radius'] ?? $defaults['modal_radius']))),
            'item_padding_y' => max(0, min(20, (int) ($appearance['item_padding_y'] ?? $defaults['item_padding_y']))),
            'item_padding_x' => max(0, min(24, (int) ($appearance['item_padding_x'] ?? $defaults['item_padding_x']))),
            'label_text_color' => $label_text_color,
            'z_index' => max(100, min(99999, (int) ($appearance['z_index'] ?? $defaults['z_index']))),
        );
    }
}
