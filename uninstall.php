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

// Clear any cached data
wp_cache_flush();
