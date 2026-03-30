<?php
/**
 * Tests for CWP_Chat_Bubbles_Settings class
 *
 * @package CWP_Chat_Bubbles
 */

use PHPUnit\Framework\TestCase;

class TestSettings extends TestCase {

    private $settings;

    protected function setUp(): void {
        global $mock_options;
        $mock_options = array();

        $reflection = new ReflectionClass('CWP_Chat_Bubbles_Settings');
        $this->settings = $reflection->newInstanceWithoutConstructor();
    }

    public function test_get_default_options_matches_minimal_runtime_scope() {
        $defaults = $this->settings->get_default_options();

        $this->assertArrayHasKey('enabled', $defaults);
        $this->assertArrayHasKey('auto_load', $defaults);
        $this->assertArrayHasKey('behavior', $defaults);
        $this->assertArrayHasKey('analytics', $defaults);
        $this->assertArrayHasKey('appearance', $defaults);
        $this->assertArrayHasKey('exclude_pages', $defaults);

        $this->assertArrayNotHasKey('load_on_mobile', $defaults);
        $this->assertArrayNotHasKey('device_visibility', $defaults);
        $this->assertArrayNotHasKey('schedule', $defaults);
        $this->assertArrayNotHasKey('targeting', $defaults);
    }

    public function test_sanitize_options_drops_removed_display_rule_fields() {
        $sanitized = $this->settings->sanitize_options(
            array(
                'enabled' => true,
                'auto_load' => true,
                'behavior' => array(
                    'default_state' => 'open',
                    'display_delay' => 5,
                    'scroll_trigger_percent' => 25,
                    'dismiss_for_session' => true,
                ),
                'exclude_pages' => array('1', '2', 'abc'),
                'device_visibility' => array(
                    'desktop' => false,
                    'tablet' => false,
                    'mobile' => false,
                ),
                'schedule' => array(
                    'enabled' => true,
                    'timezone' => 'UTC',
                ),
                'targeting' => array(
                    'operator' => 'any',
                ),
            )
        );

        $this->assertTrue($sanitized['enabled']);
        $this->assertTrue($sanitized['auto_load']);
        $this->assertSame('open', $sanitized['behavior']['default_state']);
        $this->assertSame(array(1, 2, 0), $sanitized['exclude_pages']);

        $this->assertArrayNotHasKey('load_on_mobile', $sanitized);
        $this->assertArrayNotHasKey('device_visibility', $sanitized);
        $this->assertArrayNotHasKey('schedule', $sanitized);
        $this->assertArrayNotHasKey('targeting', $sanitized);
    }

    public function test_get_options_ignores_removed_display_rule_fields_from_storage() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'enabled' => true,
            'auto_load' => true,
            'exclude_pages' => array(12, 34),
            'load_on_mobile' => false,
            'device_visibility' => array(
                'desktop' => false,
                'tablet' => false,
                'mobile' => false,
            ),
            'schedule' => array(
                'enabled' => true,
            ),
            'targeting' => array(
                'operator' => 'any',
            ),
        );

        $options = $this->settings->get_options();

        $this->assertTrue($options['enabled']);
        $this->assertTrue($options['auto_load']);
        $this->assertSame(array(12, 34), $options['exclude_pages']);
        $this->assertArrayNotHasKey('load_on_mobile', $options);
        $this->assertArrayNotHasKey('device_visibility', $options);
        $this->assertArrayNotHasKey('schedule', $options);
        $this->assertArrayNotHasKey('targeting', $options);
    }

    public function test_behavior_helpers_still_return_normalized_values() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'behavior' => array(
                'default_state' => 'open',
                'display_delay' => 7,
                'scroll_trigger_percent' => 40,
                'dismiss_for_session' => true,
            ),
        );

        $behavior = $this->settings->get_behavior_settings();

        $this->assertSame('open', $behavior['default_state']);
        $this->assertSame(7, $this->settings->get_behavior_setting('display_delay'));
        $this->assertTrue($this->settings->get_behavior_setting('dismiss_for_session'));
        $this->assertNull($this->settings->get_behavior_setting('missing_key'));
    }

    public function test_appearance_and_analytics_helpers_remain_available() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'analytics' => array(
                'enabled' => true,
                'provider' => 'gtm',
                'event_prefix' => 'bubble_metrics',
            ),
            'appearance' => array(
                'bubble_size' => 68,
                'panel_width' => 220,
                'label_text_color' => '#445566',
                'z_index' => 1500,
            ),
        );

        $this->assertTrue($this->settings->get_analytics_setting('enabled'));
        $this->assertSame('bubble_metrics', $this->settings->get_analytics_setting('event_prefix'));
        $this->assertSame(68, $this->settings->get_appearance_setting('bubble_size'));
        $this->assertSame('#445566', $this->settings->get_appearance_setting('label_text_color'));
    }
}
