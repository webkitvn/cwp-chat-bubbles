<?php

/**
 * Chat Bubbles Frontend Template
 *
 * Template for displaying chat bubbles on frontend
 * Uses data from custom table via Items Manager
 * 
 * Variables available:
 * - $items: Array of enabled chat items from custom table with URLs and icons
 * - $settings: Plugin settings array
 * - $support_icon: URL to support icon
 * - $cancel_icon: URL to cancel icon
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') or exit;

// Check if we have items to display
if (empty($items)) {
    return;
}
?>

<div id="chat-bubbles" class="cwp-chat-bubbles" data-position="<?php echo esc_attr($settings['position']); ?>">
    <!-- Main Chat Button -->
    <button
        type="button"
        class="chat-icon chat-btn-toggle"
        style="background-color: <?php echo esc_attr($settings['main_button_color']); ?>"
        aria-expanded="false"
        aria-controls="chat-bubbles-panel"
        aria-label="<?php esc_attr_e('Open chat options', 'cwp-chat-bubbles'); ?>"
        data-label-open="<?php esc_attr_e('Open chat options', 'cwp-chat-bubbles'); ?>"
        data-label-close="<?php esc_attr_e('Close chat options', 'cwp-chat-bubbles'); ?>"
    >
        <img src="<?php echo esc_url($support_icon); ?>"
            alt="<?php esc_attr_e('Support', 'cwp-chat-bubbles'); ?>"
            class="chat-icon-open">
        <img src="<?php echo esc_url($cancel_icon); ?>"
            alt="<?php esc_attr_e('Close', 'cwp-chat-bubbles'); ?>"
            class="chat-icon-close">
    </button>

    <!-- Platform Items Group -->
    <div
        id="chat-bubbles-panel"
        class="item-group <?php echo !$settings['show_labels'] ? 'no-labels' : ''; ?>"
        role="group"
        aria-label="<?php esc_attr_e('Available chat channels', 'cwp-chat-bubbles'); ?>"
        aria-hidden="true"
    >
        <?php foreach ($items as $item): ?>
            <?php
            // Check if item has QR code
            $has_qr = !empty($item['qr_code_id']) && $item['qr_code_id'] > 0;
            $modal_id = 'modal-' . $item['id'];
            $item_label = $item['label'];
            ?>

            <?php if ($has_qr): ?>
                <button
                    type="button"
                    class="chat-item chat-item-<?php echo esc_attr($item['platform']); ?>"
                    data-bubble-item-id="<?php echo esc_attr($item['id']); ?>"
                    data-bubble-modal="<?php echo esc_attr($modal_id); ?>"
                    data-bubble-target-type="modal"
                    aria-haspopup="dialog"
                    aria-controls="<?php echo esc_attr($modal_id); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('Open %s QR dialog', 'cwp-chat-bubbles'), $item_label)); ?>"
                    title="<?php echo esc_attr($item_label); ?>"
                >
                    <img src="<?php echo esc_url($item['platform_icon']); ?>"
                        alt="<?php echo esc_attr($item['platform']); ?>"
                        width="24"
                        height="24"
                        loading="lazy">

                    <?php if ($settings['show_labels']): ?>
                        <span class="chat-item-text"><?php echo esc_html($item_label); ?></span>
                    <?php endif; ?>
                </button>
            <?php else: ?>
                <a href="<?php echo esc_url($item['platform_url']); ?>"
                    class="chat-item chat-item-<?php echo esc_attr($item['platform']); ?>"
                    data-bubble-item-id="<?php echo esc_attr($item['id']); ?>"
                    data-bubble-target-type="link"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php echo esc_attr(sprintf(__('Open %s', 'cwp-chat-bubbles'), $item_label)); ?>"
                    title="<?php echo esc_attr($item_label); ?>">

                    <img src="<?php echo esc_url($item['platform_icon']); ?>"
                        alt="<?php echo esc_attr($item['platform']); ?>"
                        width="24"
                        height="24"
                        loading="lazy">

                    <?php if ($settings['show_labels']): ?>
                        <span class="chat-item-text"><?php echo esc_html($item_label); ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- QR Code Modals -->
    <?php foreach ($items as $item): ?>
        <?php if (!empty($item['qr_code_id']) && $item['qr_code_id'] > 0): ?>
            <?php
            $modal_id = 'modal-' . $item['id'];
            $qr_image_url = wp_get_attachment_url($item['qr_code_id']);
            if ($qr_image_url):
            ?>

                <div class="bubble-modal"
                    id="<?php echo esc_attr($modal_id); ?>"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="<?php echo esc_attr($modal_id . '-title'); ?>"
                    tabindex="-1"
                    aria-hidden="true">

                    <button type="button"
                        class="bubble-modal-close"
                        aria-label="<?php echo esc_attr(sprintf(__('Close %s dialog', 'cwp-chat-bubbles'), $item['label'])); ?>">
                        <img src="<?php echo esc_url($cancel_icon); ?>"
                            alt="<?php esc_attr_e('Close', 'cwp-chat-bubbles'); ?>">
                    </button>

                    <div class="modal-body">
                        <div class="qrcode">
                            <h3 id="<?php echo esc_attr($modal_id . '-title'); ?>"><?php echo esc_html($item['label']); ?></h3>
                            <img src="<?php echo esc_url($qr_image_url); ?>"
                                alt="<?php echo esc_attr($item['label']); ?> QR Code"
                                loading="lazy">
                        </div>

                        <?php if ($item['platform_url'] !== '#'): ?>
                            <a class="btn"
                                href="<?php echo esc_url($item['platform_url']); ?>"
                                target="_blank"
                                rel="noopener noreferrer" style="background-color: <?php echo esc_attr($item['platform_color']); ?>;">
                                <div class="icon">
                                    <img src="<?php echo esc_url($item['platform_icon']); ?>"
                                        alt="<?php echo esc_attr($item['label']); ?>"
                                        width="24"
                                        height="24"
                                        loading="lazy">
                                </div>
                                <span><?php printf(__('Open %s', 'cwp-chat-bubbles'), esc_html($item['label'])); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
