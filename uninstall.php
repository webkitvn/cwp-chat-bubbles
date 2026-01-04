<?php
/**
 * CWP Chat Bubbles Uninstall
 *
 * Fired when the plugin is uninstalled.
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Additional security check - verify current user can manage plugins
if (!current_user_can('manage_options')) {
    exit;
}

// Define plugin constants if not already defined
if (!defined('CWP_CHAT_BUBBLES_PLUGIN_DIR')) {
    define('CWP_CHAT_BUBBLES_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Load Items Manager for cleanup
require_once CWP_CHAT_BUBBLES_PLUGIN_DIR . 'includes/class-items-manager.php';

// Get instance and drop custom table
if (class_exists('CWP_Chat_Bubbles_Items_Manager')) {
    $items_manager = CWP_Chat_Bubbles_Items_Manager::get_instance();
    $items_manager->drop_table();
}

// Delete plugin options
delete_option('cwp_chat_bubbles_options');
delete_option('cwp_chat_bubbles_options_backup');
delete_option('cwp_chat_bubbles_version');
delete_option('cwp_chat_bubbles_activated');
delete_option('cwp_chat_bubbles_db_version');
delete_option('cwp_chat_bubbles_migrated');

// Delete security audit log
delete_option('cwp_chat_bubbles_audit_log');

// Clear any transients used for rate limiting
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cwp_chat_bubbles%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cwp_chat_bubbles%'");

// Clear plugin-specific cached data (avoid wp_cache_flush which clears ALL cache)
$cache_keys = array(
    'cwp_chat_bubbles_frontend_data',
    'cwp_chat_bubbles_data_version',
);

foreach ($cache_keys as $key) {
    wp_cache_delete($key, 'cwp_chat_bubbles');
}

// Clear versioned cache keys (items cache uses data version suffix)
// These may have various version suffixes, so we clear common patterns
for ($i = 1; $i <= 100; $i++) {
    wp_cache_delete('cwp_items_all_v' . $i, 'cwp_chat_bubbles');
    wp_cache_delete('cwp_items_enabled_v' . $i, 'cwp_chat_bubbles');
}

// Try to delete cache group if supported by the object cache
if (function_exists('wp_cache_delete_group')) {
    wp_cache_delete_group('cwp_chat_bubbles');
}
