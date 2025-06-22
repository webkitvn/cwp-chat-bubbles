<?php
/**
 * Plugin Name: CWP Chat Bubbles
 * Plugin URI: https://cuongwp.com/cwp-chat-bubbles
 * Description: Auto-injecting chat bubbles with 8 messaging platform support and QR code integration. Works immediately after activation with zero configuration required.
 * Version: 1.0.0
 * Author: CuongWP
 * Author URI: https://cuongwp.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cwp-chat-bubbles
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package CWP_Chat_Bubbles
 * @version 1.0.0
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') or exit;

// Plugin constants
define('CWP_CHAT_BUBBLES_VERSION', '1.0.0');
define('CWP_CHAT_BUBBLES_PLUGIN_FILE', __FILE__);
define('CWP_CHAT_BUBBLES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CWP_CHAT_BUBBLES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CWP_CHAT_BUBBLES_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('CWP_CHAT_BUBBLES_TEXT_DOMAIN', 'cwp-chat-bubbles');

/**
 * Main CWP Chat Bubbles Plugin Class
 * 
 * @since 1.0.0
 */
final class CWP_Chat_Bubbles {

    /**
     * Plugin instance
     * 
     * @var CWP_Chat_Bubbles
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Plugin version
     * 
     * @var string
     * @since 1.0.0
     */
    public $version = CWP_CHAT_BUBBLES_VERSION;

    /**
     * Get plugin instance
     * 
     * @return CWP_Chat_Bubbles
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
        $this->init();
    }

    /**
     * Initialize plugin
     * 
     * @since 1.0.0
     */
    private function init() {
        // Load plugin textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Initialize plugin components
        add_action('plugins_loaded', array($this, 'init_components'));
        
        // Plugin activation/deactivation hooks
        register_activation_hook(CWP_CHAT_BUBBLES_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(CWP_CHAT_BUBBLES_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . CWP_CHAT_BUBBLES_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
    }

    /**
     * Load plugin textdomain
     * 
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            CWP_CHAT_BUBBLES_TEXT_DOMAIN,
            false,
            dirname(CWP_CHAT_BUBBLES_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Initialize plugin components
     * 
     * @since 1.0.0
     */
    public function init_components() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize components
        if (class_exists('CWP_Chat_Bubbles_Settings')) {
            CWP_Chat_Bubbles_Settings::get_instance();
        }
        
        if (class_exists('CWP_Chat_Bubbles_Items_Manager')) {
            CWP_Chat_Bubbles_Items_Manager::get_instance();
        }
        
        if (class_exists('CWP_Chat_Bubbles_Data_Service')) {
            CWP_Chat_Bubbles_Data_Service::get_instance();
        }
        
        if (class_exists('CWP_Chat_Bubbles_Assets')) {
            CWP_Chat_Bubbles_Assets::get_instance();
        }
        
        if (class_exists('CWP_Chat_Bubbles_Frontend')) {
            CWP_Chat_Bubbles_Frontend::get_instance();
        }
        
        if (is_admin() && class_exists('CWP_Chat_Bubbles_Options_Page')) {
            CWP_Chat_Bubbles_Options_Page::get_instance();
        }
    }

    /**
     * Load plugin dependencies
     * 
     * @since 1.0.0
     */
    private function load_dependencies() {
        $includes_dir = CWP_CHAT_BUBBLES_PLUGIN_DIR . 'includes/';
        
        // Core classes
        $files = array(
            'class-settings.php',
            'class-items-manager.php',
            'class-data-service.php',
            'class-assets.php',
            'class-frontend.php',
            'class-options-page.php'
        );
        
        foreach ($files as $file) {
            $file_path = $includes_dir . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * Plugin activation
     * 
     * @since 1.0.0
     */
    public function activate() {
        // Load dependencies for activation
        $this->load_dependencies();
        
        // Create custom table for dynamic items
        if (class_exists('CWP_Chat_Bubbles_Items_Manager')) {
            $items_manager = CWP_Chat_Bubbles_Items_Manager::get_instance();
            $items_manager->create_table();
            
            // Migrate data from old format if exists
            $items_manager->migrate_from_options();
        }
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('cwp_chat_bubbles_activated', time());
    }

    /**
     * Plugin deactivation
     * 
     * @since 1.0.0
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Remove activation flag
        delete_option('cwp_chat_bubbles_activated');
    }

    /**
     * Set default plugin options
     * 
     * @since 1.0.0
     */
    private function set_default_options() {
        $default_options = array(
            // General settings
            'enabled' => true,
            'auto_load' => true,
            'position' => 'bottom-right',
            
            // Platform settings (all disabled by default, user configures)
            'platforms' => array(
                'phone' => array(
                    'enabled' => false,
                    'number' => '',
                    'label' => __('Call Us', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'qr_code' => ''
                ),
                'zalo' => array(
                    'enabled' => false,
                    'number' => '',
                    'label' => __('Zalo', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'qr_code' => ''
                ),
                'whatsapp' => array(
                    'enabled' => false,
                    'number' => '',
                    'label' => __('WhatsApp', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'qr_code' => ''
                ),
                'viber' => array(
                    'enabled' => false,
                    'number' => '',
                    'label' => __('Viber', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'qr_code' => ''
                ),
                'telegram' => array(
                    'enabled' => false,
                    'username' => '',
                    'label' => __('Telegram', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'qr_code' => ''
                ),
                'messenger' => array(
                    'enabled' => false,
                    'username' => '',
                    'label' => __('Messenger', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'qr_code' => ''
                ),
                'line' => array(
                    'enabled' => false,
                    'id' => '',
                    'label' => __('Line', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'qr_code' => ''
                ),
                'kakaotalk' => array(
                    'enabled' => false,
                    'id' => '',
                    'label' => __('KakaoTalk', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                    'qr_code' => ''
                )
            ),
            
            // Display settings
            'main_button_color' => '#52BA00',
            'animation_enabled' => true,
            'show_labels' => true,
            
            // Advanced settings
            'custom_css' => '',
            'load_on_mobile' => true,
            'exclude_pages' => array()
        );
        
        // Only set defaults if options don't exist
        if (!get_option('cwp_chat_bubbles_options')) {
            update_option('cwp_chat_bubbles_options', $default_options);
        }
    }

    /**
     * Add plugin action links
     * 
     * @param array $links Plugin action links
     * @return array Modified plugin action links
     * @since 1.0.0
     */
    public function plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=cwp-chat-bubbles'),
            __('Settings', CWP_CHAT_BUBBLES_TEXT_DOMAIN)
        );
        
        array_unshift($links, $settings_link);
        
        return $links;
    }

    /**
     * Get plugin options
     * 
     * @return array Plugin options
     * @since 1.0.0
     */
    public function get_options() {
        return get_option('cwp_chat_bubbles_options', array());
    }

    /**
     * Update plugin options
     * 
     * @param array $options New options
     * @return bool Whether the option was updated
     * @since 1.0.0
     */
    public function update_options($options) {
        return update_option('cwp_chat_bubbles_options', $options);
    }

    /**
     * Get specific option value
     * 
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed Option value
     * @since 1.0.0
     */
    public function get_option($key, $default = null) {
        $options = $this->get_options();
        return isset($options[$key]) ? $options[$key] : $default;
    }
}

/**
 * Initialize the plugin
 * 
 * @return CWP_Chat_Bubbles
 * @since 1.0.0
 */
function cwp_chat_bubbles() {
    return CWP_Chat_Bubbles::get_instance();
}

// Initialize plugin
cwp_chat_bubbles();
