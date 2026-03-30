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
define('CWP_CHAT_BUBBLES_TEXT_DOMAIN', 'cwp-chat-bubbles');
define('DAY_IN_SECONDS', 86400);
define('HOUR_IN_SECONDS', 3600);

// Mock WordPress functions needed for testing
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        $filtered = trim($str);
        $filtered = strip_tags($filtered);
        return preg_replace('/[\r\n\t]+/', ' ', $filtered);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null) {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return (string) $url;
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = null) {
        echo esc_attr(__($text, $domain));
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = null) {
        echo esc_html(__($text, $domain));
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

if (!function_exists('is_admin')) {
    function is_admin() {
        global $mock_is_admin;
        return (bool) $mock_is_admin;
    }
}

if (!function_exists('is_page')) {
    function is_page() {
        global $mock_is_page;
        return (bool) $mock_is_page;
    }
}

if (!function_exists('is_front_page')) {
    function is_front_page() {
        global $mock_is_front_page;
        return (bool) $mock_is_front_page;
    }
}

if (!function_exists('is_home')) {
    function is_home() {
        global $mock_is_home;
        return (bool) $mock_is_home;
    }
}

if (!function_exists('is_search')) {
    function is_search() {
        global $mock_is_search;
        return (bool) $mock_is_search;
    }
}

if (!function_exists('is_404')) {
    function is_404() {
        global $mock_is_404;
        return (bool) $mock_is_404;
    }
}

if (!function_exists('is_archive')) {
    function is_archive() {
        global $mock_is_archive;
        return (bool) $mock_is_archive;
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        global $mock_current_page_id;
        return $mock_current_page_id;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($post = null) {
        global $mock_post_type;
        return $mock_post_type;
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
$mock_is_admin = false;
$mock_is_page = false;
$mock_is_front_page = false;
$mock_is_home = false;
$mock_is_search = false;
$mock_is_404 = false;
$mock_is_archive = false;
$mock_current_page_id = 0;
$mock_post_type = '';

// Load the classes we want to test (without WordPress hooks)
require_once CWP_CHAT_BUBBLES_PLUGIN_DIR . 'includes/class-items-manager.php';
require_once CWP_CHAT_BUBBLES_PLUGIN_DIR . 'includes/class-settings.php';
require_once CWP_CHAT_BUBBLES_PLUGIN_DIR . 'includes/class-data-service.php';
