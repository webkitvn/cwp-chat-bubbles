<?php
/**
 * Tests for frontend template rendering contracts.
 *
 * @package CWP_Chat_Bubbles
 */

use PHPUnit\Framework\TestCase;

class TestTemplateRendering extends TestCase {

    /**
     * Test template renders accessibility and analytics attributes for items and modals.
     */
    public function test_chat_bubbles_template_renders_accessible_markup_contract() {
        $items = array(
            array(
                'id' => 7,
                'platform' => 'zalo',
                'label' => 'Sales',
                'platform_url' => 'https://example.com/zalo',
                'platform_icon' => 'https://example.com/zalo.svg',
                'platform_color' => '#008BE6',
                'qr_code_id' => 12,
                'behavior' => array(
                    'interaction_mode' => 'auto',
                    'prefill_message' => '',
                ),
            ),
            array(
                'id' => 8,
                'platform' => 'phone',
                'label' => 'Hotline',
                'platform_url' => 'tel:+84123456789',
                'platform_icon' => 'https://example.com/phone.svg',
                'platform_color' => '#52BA00',
                'qr_code_id' => 15,
                'behavior' => array(
                    'interaction_mode' => 'direct_link',
                    'prefill_message' => '',
                ),
            ),
        );
        $settings = array(
            'position' => 'bottom-right',
            'main_button_color' => '#52BA00',
            'show_labels' => true,
            'behavior' => array(
                'default_state' => 'open',
                'display_delay' => 4,
                'scroll_trigger_percent' => 25,
                'dismiss_for_session' => true,
            ),
        );
        $support_icon = 'https://example.com/support.svg';
        $cancel_icon = 'https://example.com/cancel.svg';

        ob_start();
        require CWP_CHAT_BUBBLES_PLUGIN_DIR . 'templates/chat-bubbles.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('aria-expanded="false"', $output);
        $this->assertStringContainsString('aria-controls="chat-bubbles-panel"', $output);
        $this->assertStringContainsString('data-default-state="open"', $output);
        $this->assertStringContainsString('data-display-delay="4"', $output);
        $this->assertStringContainsString('data-scroll-trigger="25"', $output);
        $this->assertStringContainsString('data-dismiss-for-session="true"', $output);
        $this->assertStringContainsString('hidden', $output);
        $this->assertStringContainsString('role="group"', $output);
        $this->assertStringContainsString('data-bubble-item-id="7"', $output);
        $this->assertStringContainsString('data-bubble-target-type="modal"', $output);
        $this->assertStringContainsString('aria-haspopup="dialog"', $output);
        $this->assertStringContainsString('role="dialog"', $output);
        $this->assertStringContainsString('aria-modal="true"', $output);
        $this->assertStringContainsString('aria-labelledby="modal-7-title"', $output);
        $this->assertStringContainsString('data-bubble-item-id="8"', $output);
        $this->assertStringContainsString('data-bubble-target-type="link"', $output);
        $this->assertStringContainsString('data-bubble-interaction-mode="direct_link"', $output);
        $this->assertStringContainsString('rel="noopener noreferrer"', $output);
        $this->assertStringNotContainsString('id="modal-8"', $output);
    }
}
