<?php
/**
 * PHPUnit bootstrap file for CWP Chat Bubbles tests
 *
 * @package CWP_Chat_Bubbles
 */

if (!defined('CWP_CHAT_BUBBLES_TESTING')) {
    define('CWP_CHAT_BUBBLES_TESTING', true);
}
define('ABSPATH', '/tmp/wordpress/');
define('CWP_CHAT_BUBBLES_PLUGIN_URL', 'http://example.com/wp-content/plugins/cwp-chat-bubbles/');
define('CWP_CHAT_BUBBLES_PLUGIN_DIR', dirname(__DIR__) . '/');

// Mock WordPress functions needed for testing
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        $filtered = trim($str);
        $filtered = strip_tags($filtered);
        return preg_replace('/[\r\n\t]+/', ' ', $filtered);
    }
}

if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color) {
        if ('' === $color) {
            return '';
        }
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
        return null;
    }
}

if (!function_exists('absint')) {
    function absint($value) {
        return abs((int) $value);
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string) {
        return strip_tags($string);
    }
}

if (!function_exists('wp_get_attachment_url')) {
    function wp_get_attachment_url($attachment_id) {
        if ($attachment_id > 0 && $attachment_id < 1000) {
            return 'http://example.com/wp-content/uploads/test-image.jpg';
        }
        return false;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $mock_options;
        return isset($mock_options[$option]) ? $mock_options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $mock_options;
        $mock_options[$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        global $mock_options;
        unset($mock_options[$option]);
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name, $args = array()) {
        return true;
    }
}

if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '', $force = false, &$found = null) {
        return false;
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $data, $group = '', $expire = 0) {
        return true;
    }
}

if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($key, $group = '') {
        return true;
    }
}

// Global mock options storage
$mock_options = array();

// Load the classes we want to test (without WordPress hooks)
require_once CWP_CHAT_BUBBLES_PLUGIN_DIR . 'includes/class-items-manager.php';
require_once CWP_CHAT_BUBBLES_PLUGIN_DIR . 'includes/class-settings.php';
