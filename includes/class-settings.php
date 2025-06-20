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
            
            // Display settings
            'main_button_color' => '#52BA00',
            'animation_enabled' => true,
            'show_labels' => true,              // Global setting for all items
            
            // Advanced settings
            'custom_css' => '',
            'load_on_mobile' => true,
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
        $sanitized['enabled'] = isset($options['enabled']) ? (bool) $options['enabled'] : true;
        $sanitized['auto_load'] = isset($options['auto_load']) ? (bool) $options['auto_load'] : true;
        $sanitized['position'] = isset($options['position']) ? sanitize_text_field($options['position']) : 'bottom-right';

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
            : true;
            
        $sanitized['show_labels'] = isset($options['show_labels'])
            ? (bool) $options['show_labels']
            : true;

        // Sanitize advanced settings
        $sanitized['custom_css'] = isset($options['custom_css'])
            ? wp_strip_all_tags($options['custom_css'])
            : '';
            
        $sanitized['load_on_mobile'] = isset($options['load_on_mobile'])
            ? (bool) $options['load_on_mobile']
            : true;

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
        return get_option($this->option_name, $this->get_default_options());
    }

    /**
     * Update plugin options
     *
     * @param array $options New options
     * @return bool Whether the option was updated
     * @since 1.0.0
     */
    public function update_options($options) {
        $sanitized_options = $this->sanitize_options($options);
        return update_option($this->option_name, $sanitized_options);
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
} 