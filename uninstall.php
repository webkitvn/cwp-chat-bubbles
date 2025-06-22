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

// Verify this is our plugin being uninstalled
if (plugin_basename(__FILE__) !== 'cwp-chat-bubbles/uninstall.php') {
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

// Clear any cached data
wp_cache_delete('cwp_chat_bubbles_frontend_data', 'cwp_chat_bubbles');
wp_cache_flush();
