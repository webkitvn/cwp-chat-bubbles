<?php
/**
 * REST API Class
 *
 * Public read-only endpoints for frontend lazy loading.
 *
 * @package CWP_Chat_Bubbles
 * @since 1.1.1
 */

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * CWP Chat Bubbles REST API Class
 *
 * @since 1.1.1
 */
class CWP_Chat_Bubbles_REST_API {

    /**
     * Instance of this class
     *
     * @var CWP_Chat_Bubbles_REST_API
     * @since 1.1.1
     */
    private static $instance = null;

    /**
     * Settings instance
     *
     * @var CWP_Chat_Bubbles_Settings
     * @since 1.1.1
     */
    private $settings;

    /**
     * Data service instance
     *
     * @var CWP_Chat_Bubbles_Data_Service
     * @since 1.1.1
     */
    private $data_service;

    /**
     * Get instance
     *
     * @return CWP_Chat_Bubbles_REST_API
     * @since 1.1.1
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
     * @since 1.1.1
     */
    private function __construct() {
        $this->settings = CWP_Chat_Bubbles_Settings::get_instance();
        $this->data_service = CWP_Chat_Bubbles_Data_Service::get_instance();
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST routes.
     *
     * @since 1.1.1
     */
    public function register_routes() {
        register_rest_route('cwp-chat-bubbles/v1', '/items', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_items'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Get frontend items for lazy rendering.
     *
     * @return WP_REST_Response|WP_Error
     * @since 1.1.1
     */
    public function get_items() {
        $lazy_enabled = (bool) apply_filters('cwp_chat_bubbles_lazy_autoload_enabled', true);
        if (!$lazy_enabled) {
            return new WP_Error(
                'cwp_chat_bubbles_lazy_disabled',
                __('Lazy auto-load is disabled.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                array('status' => 404)
            );
        }

        if (!$this->settings->is_enabled() || !$this->settings->is_auto_load_enabled()) {
            return new WP_Error(
                'cwp_chat_bubbles_unavailable',
                __('Chat bubbles are currently unavailable.', CWP_CHAT_BUBBLES_TEXT_DOMAIN),
                array('status' => 403)
            );
        }

        return rest_ensure_response($this->data_service->get_lazy_frontend_items_payload());
    }
}

