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
        
        $this->settings = $this->createTestableSettings();
    }

    private function createTestableSettings() {
        $reflection = new ReflectionClass('CWP_Chat_Bubbles_Settings');
        $instance = $reflection->newInstanceWithoutConstructor();
        return $instance;
    }

    /**
     * Test get_default_options returns expected structure
     */
    public function test_get_default_options() {
        $defaults = $this->settings->get_default_options();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('enabled', $defaults);
        $this->assertArrayHasKey('auto_load', $defaults);
        $this->assertArrayHasKey('position', $defaults);
        $this->assertArrayHasKey('main_button_color', $defaults);
        $this->assertArrayHasKey('animation_enabled', $defaults);
        $this->assertArrayHasKey('show_labels', $defaults);
        $this->assertArrayHasKey('custom_css', $defaults);
        $this->assertArrayHasKey('load_on_mobile', $defaults);
        $this->assertArrayHasKey('device_visibility', $defaults);
        $this->assertArrayHasKey('behavior', $defaults);
        $this->assertArrayHasKey('schedule', $defaults);
        $this->assertArrayHasKey('exclude_pages', $defaults);
        $this->assertArrayHasKey('offset_x', $defaults);
        $this->assertArrayHasKey('offset_y', $defaults);
        $this->assertArrayHasKey('custom_main_icon', $defaults);
    }

    /**
     * Test default values are correct
     */
    public function test_default_values() {
        $defaults = $this->settings->get_default_options();

        $this->assertTrue($defaults['enabled']);
        $this->assertTrue($defaults['auto_load']);
        $this->assertEquals('bottom-right', $defaults['position']);
        $this->assertEquals('#52BA00', $defaults['main_button_color']);
        $this->assertTrue($defaults['animation_enabled']);
        $this->assertTrue($defaults['show_labels']);
        $this->assertTrue($defaults['load_on_mobile']);
        $this->assertEquals(
            array(
                'desktop' => true,
                'tablet' => true,
                'mobile' => true,
            ),
            $defaults['device_visibility']
        );
        $this->assertEquals(
            array(
                'default_state' => 'closed',
                'display_delay' => 0,
                'scroll_trigger_percent' => 0,
                'dismiss_for_session' => false,
            ),
            $defaults['behavior']
        );
        $this->assertFalse($defaults['schedule']['enabled']);
        $this->assertSame('', $defaults['schedule']['timezone']);
        $this->assertSame('hide', $defaults['schedule']['closed_behavior']);
        $this->assertTrue($defaults['schedule']['weekly_hours']['mon']['enabled']);
        $this->assertFalse($defaults['schedule']['weekly_hours']['sun']['enabled']);
        $this->assertSame('09:00', $defaults['schedule']['weekly_hours']['mon']['open']);
        $this->assertSame('17:00', $defaults['schedule']['weekly_hours']['mon']['close']);
        $this->assertEquals('', $defaults['custom_css']);
        $this->assertEquals(array(), $defaults['exclude_pages']);
        $this->assertEquals(0, $defaults['offset_x']);
        $this->assertEquals(0, $defaults['offset_y']);
        $this->assertEquals(0, $defaults['custom_main_icon']);
    }

    /**
     * Test sanitize_options with valid input
     */
    public function test_sanitize_options_valid_input() {
        $input = array(
            'enabled' => true,
            'auto_load' => true,
            'position' => 'bottom-left',
            'main_button_color' => '#FF5733',
            'animation_enabled' => true,
            'show_labels' => true,
            'load_on_mobile' => true,
            'device_visibility' => array(
                'desktop' => true,
                'tablet' => false,
                'mobile' => true,
            ),
            'behavior' => array(
                'default_state' => 'open',
                'display_delay' => 5,
                'scroll_trigger_percent' => 25,
                'dismiss_for_session' => true,
            ),
            'schedule' => array(
                'enabled' => true,
                'timezone' => 'Asia/Ho_Chi_Minh',
                'closed_behavior' => 'hide',
                'weekly_hours' => array(
                    'mon' => array(
                        'enabled' => true,
                        'open' => '08:30',
                        'close' => '18:00',
                    ),
                    'sun' => array(
                        'enabled' => false,
                        'open' => '10:00',
                        'close' => '12:00',
                    ),
                ),
            ),
            'custom_css' => '.test { color: red; }',
            'exclude_pages' => array(1, 2, 3),
            'offset_x' => 20,
            'offset_y' => -15,
            'custom_main_icon' => 100,
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertTrue($sanitized['enabled']);
        $this->assertTrue($sanitized['auto_load']);
        $this->assertEquals('bottom-left', $sanitized['position']);
        $this->assertEquals('#FF5733', $sanitized['main_button_color']);
        $this->assertTrue($sanitized['animation_enabled']);
        $this->assertTrue($sanitized['show_labels']);
        $this->assertTrue($sanitized['load_on_mobile']);
        $this->assertEquals(
            array(
                'desktop' => true,
                'tablet' => false,
                'mobile' => true,
            ),
            $sanitized['device_visibility']
        );
        $this->assertEquals(
            array(
                'default_state' => 'open',
                'display_delay' => 5,
                'scroll_trigger_percent' => 25,
                'dismiss_for_session' => true,
            ),
            $sanitized['behavior']
        );
        $this->assertTrue($sanitized['schedule']['enabled']);
        $this->assertSame('Asia/Ho_Chi_Minh', $sanitized['schedule']['timezone']);
        $this->assertSame('hide', $sanitized['schedule']['closed_behavior']);
        $this->assertSame('08:30', $sanitized['schedule']['weekly_hours']['mon']['open']);
        $this->assertSame('18:00', $sanitized['schedule']['weekly_hours']['mon']['close']);
        $this->assertFalse($sanitized['schedule']['weekly_hours']['sun']['enabled']);
        $this->assertEquals('.test { color: red; }', $sanitized['custom_css']);
        $this->assertEquals(array(1, 2, 3), $sanitized['exclude_pages']);
        $this->assertEquals(20, $sanitized['offset_x']);
        $this->assertEquals(-15, $sanitized['offset_y']);
        $this->assertEquals(100, $sanitized['custom_main_icon']);
    }

    /**
     * Test sanitize_options with invalid position
     */
    public function test_sanitize_options_invalid_position() {
        $input = array(
            'position' => 'invalid-position',
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertEquals('bottom-right', $sanitized['position']);
    }

    /**
     * Test sanitize_options with all valid positions
     */
    public function test_sanitize_options_valid_positions() {
        $valid_positions = array('bottom-right', 'bottom-left', 'top-right', 'top-left');

        foreach ($valid_positions as $position) {
            $input = array('position' => $position);
            $sanitized = $this->settings->sanitize_options($input);
            
            $this->assertEquals(
                $position,
                $sanitized['position'],
                "Position $position should be valid"
            );
        }
    }

    /**
     * Test sanitize_options with invalid hex color
     */
    public function test_sanitize_options_invalid_color() {
        $input = array(
            'main_button_color' => 'not-a-color',
        );

        $sanitized = $this->settings->sanitize_options($input);

        // sanitize_hex_color returns null for invalid colors, then default is used
        $this->assertThat(
            $sanitized['main_button_color'],
            $this->logicalOr(
                $this->equalTo('#52BA00'),
                $this->isNull()
            )
        );
    }

    /**
     * Test sanitize_options with valid hex colors
     */
    public function test_sanitize_options_valid_colors() {
        $valid_colors = array(
            '#000',
            '#FFF',
            '#000000',
            '#FFFFFF',
            '#52BA00',
            '#ff5733',
        );

        foreach ($valid_colors as $color) {
            $input = array('main_button_color' => $color);
            $sanitized = $this->settings->sanitize_options($input);
            
            $this->assertEquals(
                $color,
                $sanitized['main_button_color'],
                "Color $color should be valid"
            );
        }
    }

    /**
     * Test sanitize_options clamps offset values
     */
    public function test_sanitize_options_offset_clamping() {
        $input = array(
            'offset_x' => 500,
            'offset_y' => -500,
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertEquals(200, $sanitized['offset_x']);
        $this->assertEquals(-200, $sanitized['offset_y']);
    }

    /**
     * Test sanitize_options with checkbox unchecked (missing from input)
     */
    public function test_sanitize_options_checkbox_unchecked() {
        $input = array();

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertFalse($sanitized['enabled']);
        $this->assertFalse($sanitized['auto_load']);
        $this->assertFalse($sanitized['animation_enabled']);
        $this->assertFalse($sanitized['show_labels']);
        $this->assertFalse($sanitized['load_on_mobile']);
        $this->assertEquals(
            array(
                'desktop' => true,
                'tablet' => true,
                'mobile' => false,
            ),
            $sanitized['device_visibility']
        );
        $this->assertEquals(
            array(
                'default_state' => 'closed',
                'display_delay' => 0,
                'scroll_trigger_percent' => 0,
                'dismiss_for_session' => false,
            ),
            $sanitized['behavior']
        );
        $this->assertFalse($sanitized['schedule']['enabled']);
        $this->assertSame('', $sanitized['schedule']['timezone']);
        $this->assertSame('hide', $sanitized['schedule']['closed_behavior']);
    }

    /**
     * Test sanitize_options removes dangerous CSS patterns
     */
    public function test_sanitize_options_css_xss_prevention() {
        $dangerous_css_inputs = array(
            array(
                'input' => 'body { background: url(javascript:alert(1)); }',
                'description' => 'javascript: URL in CSS'
            ),
            array(
                'input' => 'body { background: expression(alert(1)); }',
                'description' => 'expression() in CSS'
            ),
            array(
                'input' => '@import "http://evil.com/styles.css";',
                'description' => '@import statement'
            ),
            array(
                'input' => 'body { behavior: url(script.htc); }',
                'description' => 'behavior property'
            ),
            array(
                'input' => 'body { -moz-binding: url(script.xml#xss); }',
                'description' => 'mozbinding property'
            ),
        );

        foreach ($dangerous_css_inputs as $case) {
            $input = array('custom_css' => $case['input']);
            $sanitized = $this->settings->sanitize_options($input);
            
            $this->assertStringNotContainsString(
                'javascript',
                strtolower($sanitized['custom_css']),
                "Should remove: {$case['description']}"
            );
            $this->assertStringNotContainsString(
                'expression',
                strtolower($sanitized['custom_css']),
                "Should remove: {$case['description']}"
            );
            $this->assertStringNotContainsString(
                '@import',
                strtolower($sanitized['custom_css']),
                "Should remove: {$case['description']}"
            );
            $this->assertStringNotContainsString(
                'behavior',
                strtolower($sanitized['custom_css']),
                "Should remove: {$case['description']}"
            );
        }
    }

    /**
     * Test sanitize_options truncates CSS to max length
     */
    public function test_sanitize_options_css_max_length() {
        $long_css = str_repeat('.test { color: red; }', 500); // Way over 5000 chars
        
        $input = array('custom_css' => $long_css);
        $sanitized = $this->settings->sanitize_options($input);

        $this->assertLessThanOrEqual(5000, strlen($sanitized['custom_css']));
    }

    /**
     * Test sanitize_options strips HTML tags from CSS
     */
    public function test_sanitize_options_css_strips_html() {
        $input = array(
            'custom_css' => '<script>alert(1)</script>.test { color: red; }',
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertStringNotContainsString('<script>', $sanitized['custom_css']);
        $this->assertStringNotContainsString('</script>', $sanitized['custom_css']);
    }

    /**
     * Test sanitize_options with exclude_pages array
     */
    public function test_sanitize_options_exclude_pages() {
        $input = array(
            'exclude_pages' => array('1', '2', 'abc', '-5', '100'),
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertEquals(array(1, 2, 0, 5, 100), $sanitized['exclude_pages']);
    }

    /**
     * Test sanitize_options with non-array exclude_pages
     */
    public function test_sanitize_options_exclude_pages_not_array() {
        $input = array(
            'exclude_pages' => 'not-an-array',
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertEquals(array(), $sanitized['exclude_pages']);
    }

    /**
     * Test sanitize_options resets invalid attachment ID
     */
    public function test_sanitize_options_invalid_attachment() {
        $input = array(
            'custom_main_icon' => 99999, // Non-existent attachment
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertEquals(0, $sanitized['custom_main_icon']);
    }

    /**
     * Test sanitize_options keeps valid attachment ID
     */
    public function test_sanitize_options_valid_attachment() {
        $input = array(
            'custom_main_icon' => 100, // Mock returns valid URL for IDs < 1000
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertEquals(100, $sanitized['custom_main_icon']);
    }

    /**
     * Test sanitize_options keeps legacy mobile flag and defaults other devices on old forms
     */
    public function test_sanitize_options_migrates_legacy_mobile_visibility() {
        $input = array(
            'load_on_mobile' => false,
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertFalse($sanitized['load_on_mobile']);
        $this->assertEquals(
            array(
                'desktop' => true,
                'tablet' => true,
                'mobile' => false,
            ),
            $sanitized['device_visibility']
        );
    }

    /**
     * Test get_options normalizes legacy stored options with the new device schema
     */
    public function test_get_options_normalizes_legacy_device_visibility() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'enabled' => true,
            'load_on_mobile' => false,
            'custom_css' => '.legacy { color: blue; }',
            'exclude_pages' => array(12, 34),
        );

        $options = $this->settings->get_options();

        $this->assertFalse($options['load_on_mobile']);
        $this->assertEquals(
            array(
                'desktop' => true,
                'tablet' => true,
                'mobile' => false,
            ),
            $options['device_visibility']
        );
        $this->assertEquals('.legacy { color: blue; }', $options['custom_css']);
        $this->assertEquals(array(12, 34), $options['exclude_pages']);
    }

    /**
     * Test helper methods expose stable device visibility accessors
     */
    public function test_device_visibility_helpers() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'device_visibility' => array(
                'desktop' => true,
                'tablet' => false,
                'mobile' => false,
            ),
        );

        $this->assertEquals(
            array(
                'desktop' => true,
                'tablet' => false,
                'mobile' => false,
            ),
            $this->settings->get_device_visibility()
        );
        $this->assertFalse($this->settings->should_load_on_mobile());
        $this->assertFalse($this->settings->is_device_enabled('tablet'));
        $this->assertTrue($this->settings->is_device_enabled('desktop'));
        $this->assertFalse($this->settings->get_option('device_visibility.mobile', true));
    }

    /**
     * Test sanitize_options clamps and validates behavior settings.
     */
    public function test_sanitize_options_behavior_settings() {
        $input = array(
            'behavior' => array(
                'default_state' => 'invalid',
                'display_delay' => 99,
                'scroll_trigger_percent' => -5,
                'dismiss_for_session' => true,
            ),
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertEquals(
            array(
                'default_state' => 'closed',
                'display_delay' => 30,
                'scroll_trigger_percent' => 0,
                'dismiss_for_session' => true,
            ),
            $sanitized['behavior']
        );
    }

    /**
     * Test behavior helpers expose normalized values for runtime consumers.
     */
    public function test_behavior_helpers() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'behavior' => array(
                'default_state' => 'open',
                'display_delay' => 7,
                'scroll_trigger_percent' => 40,
                'dismiss_for_session' => true,
            ),
        );

        $this->assertEquals(
            array(
                'default_state' => 'open',
                'display_delay' => 7,
                'scroll_trigger_percent' => 40,
                'dismiss_for_session' => true,
            ),
            $this->settings->get_behavior_settings()
        );
        $this->assertSame('open', $this->settings->get_behavior_setting('default_state'));
        $this->assertSame(7, $this->settings->get_behavior_setting('display_delay'));
        $this->assertTrue($this->settings->get_behavior_setting('dismiss_for_session'));
        $this->assertNull($this->settings->get_behavior_setting('missing_key'));
    }

    /**
     * Test sanitize_options validates schedule settings and falls back safely.
     */
    public function test_sanitize_options_schedule_settings() {
        $input = array(
            'schedule' => array(
                'enabled' => true,
                'timezone' => 'Invalid/Timezone',
                'closed_behavior' => 'show-message',
                'weekly_hours' => array(
                    'mon' => array(
                        'enabled' => true,
                        'open' => '25:00',
                        'close' => '18:70',
                    ),
                ),
            ),
        );

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertTrue($sanitized['schedule']['enabled']);
        $this->assertSame('', $sanitized['schedule']['timezone']);
        $this->assertSame('hide', $sanitized['schedule']['closed_behavior']);
        $this->assertSame('09:00', $sanitized['schedule']['weekly_hours']['mon']['open']);
        $this->assertSame('17:00', $sanitized['schedule']['weekly_hours']['mon']['close']);
    }

    /**
     * Test schedule helpers expose normalized schedule values.
     */
    public function test_schedule_helpers() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'schedule' => array(
                'enabled' => true,
                'timezone' => 'UTC',
                'closed_behavior' => 'hide',
                'weekly_hours' => array(
                    'mon' => array(
                        'enabled' => true,
                        'open' => '07:00',
                        'close' => '19:00',
                    ),
                ),
            ),
        );

        $schedule = $this->settings->get_schedule_settings();

        $this->assertTrue($schedule['enabled']);
        $this->assertSame('UTC', $schedule['timezone']);
        $this->assertSame('hide', $this->settings->get_schedule_setting('closed_behavior'));
        $this->assertSame('07:00', $schedule['weekly_hours']['mon']['open']);
        $this->assertNull($this->settings->get_schedule_setting('missing_key'));
    }
}
