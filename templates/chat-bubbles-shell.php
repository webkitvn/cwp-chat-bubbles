<?php
/**
 * Chat Bubbles Lazy Shell Template
 *
 * Lightweight auto-inject shell for lazy loading chat items on demand.
 *
 * @package CWP_Chat_Bubbles
 * @since 1.1.1
 */

defined('ABSPATH') or exit;
?>

<div id="chat-bubbles"
    class="cwp-chat-bubbles"
    data-position="<?php echo esc_attr($settings['position']); ?>"
    data-lazy="<?php echo !empty($lazy_enabled) ? '1' : '0'; ?>"
    data-endpoint="<?php echo esc_url($lazy_endpoint); ?>"
    data-prefetch="<?php echo !empty($prefetch_enabled) ? '1' : '0'; ?>">

    <div class="chat-icon chat-btn-toggle"
        style="background-color: <?php echo esc_attr($settings['main_button_color']); ?>">
        <img src="<?php echo esc_url($support_icon); ?>"
            alt="<?php esc_attr_e('Support', 'cwp-chat-bubbles'); ?>"
            class="chat-icon-open">
        <img src="<?php echo esc_url($cancel_icon); ?>"
            alt="<?php esc_attr_e('Close', 'cwp-chat-bubbles'); ?>"
            class="chat-icon-close">
    </div>

    <div class="item-group <?php echo !$settings['show_labels'] ? 'no-labels' : ''; ?>"></div>
    <p class="cwp-chat-loading" hidden><?php esc_html_e('Loading...', 'cwp-chat-bubbles'); ?></p>
    <p class="cwp-chat-error" hidden><?php esc_html_e('Unable to load chat options. Please try again.', 'cwp-chat-bubbles'); ?></p>
    <div class="cwp-chat-modals"></div>
</div>

