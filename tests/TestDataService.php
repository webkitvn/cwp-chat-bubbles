<?php
/**
 * Tests for CWP_Chat_Bubbles_Data_Service class
 *
 * @package CWP_Chat_Bubbles
 */

use PHPUnit\Framework\TestCase;

class TestDataService extends TestCase {

    private $data_service;

    protected function setUp(): void {
        global $mock_options, $mock_is_admin, $mock_is_page, $mock_current_page_id;

        $mock_options = array();
        $mock_is_admin = false;
        $mock_is_page = false;
        $mock_current_page_id = 0;

        $this->data_service = $this->createTestableDataService();
    }

    private function createTestableDataService() {
        $reflection = new ReflectionClass('CWP_Chat_Bubbles_Data_Service');
        $instance = $reflection->newInstanceWithoutConstructor();

        $settings_property = $reflection->getProperty('settings');
        $settings_property->setAccessible(true);
        $settings_property->setValue($instance, $this->createTestableSettings());

        return $instance;
    }

    private function createTestableSettings() {
        $reflection = new ReflectionClass('CWP_Chat_Bubbles_Settings');
        return $reflection->newInstanceWithoutConstructor();
    }

    private function setDataServiceProperty($property, $value) {
        $reflection = new ReflectionClass('CWP_Chat_Bubbles_Data_Service');
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->data_service, $value);
    }

    /**
     * Test should_load_on_current_page returns false when every device visibility flag is disabled.
     */
    public function test_should_not_load_when_all_devices_are_hidden() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'enabled' => true,
            'auto_load' => true,
            'device_visibility' => array(
                'desktop' => false,
                'tablet' => false,
                'mobile' => false,
            ),
        );

        $this->assertFalse($this->data_service->should_load_on_current_page());
    }

    /**
     * Test should_load_on_current_page respects excluded pages.
     */
    public function test_should_not_load_on_excluded_page() {
        global $mock_options, $mock_is_page, $mock_current_page_id;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'enabled' => true,
            'auto_load' => true,
            'exclude_pages' => array(42),
        );
        $mock_is_page = true;
        $mock_current_page_id = 42;

        $this->assertFalse($this->data_service->should_load_on_current_page());
    }

    /**
     * Test should_load_on_current_page still loads when the page is allowed and at least one device is visible.
     */
    public function test_should_load_when_page_is_allowed_and_a_device_is_visible() {
        global $mock_options, $mock_is_page, $mock_current_page_id;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'enabled' => true,
            'auto_load' => true,
            'exclude_pages' => array(99),
            'device_visibility' => array(
                'desktop' => true,
                'tablet' => false,
                'mobile' => false,
            ),
        );
        $mock_is_page = true;
        $mock_current_page_id = 42;

        $this->assertTrue($this->data_service->should_load_on_current_page());
    }

    /**
     * Test frontend data includes normalized analytics settings for runtime consumers.
     */
    public function test_frontend_data_exposes_analytics_settings() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'position' => 'bottom-left',
            'main_button_color' => '#123456',
            'analytics' => array(
                'enabled' => true,
                'provider' => 'ga4',
                'event_prefix' => 'support_chat',
            ),
        );

        $items_manager = $this->getMockBuilder(CWP_Chat_Bubbles_Items_Manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('get_all_items', 'generate_platform_url', 'get_platform_icon_url', 'get_platform_color'))
            ->getMock();

        $items_manager->method('get_all_items')->willReturn(
            array(
                array(
                    'id' => 7,
                    'platform' => 'zalo',
                    'label' => 'Sales',
                    'contact_value' => '0123456789',
                    'enabled' => 1,
                    'qr_code_id' => 0,
                    'sort_order' => 0,
                ),
            )
        );
        $items_manager->method('generate_platform_url')->willReturn('https://example.com/zalo');
        $items_manager->method('get_platform_icon_url')->willReturn('https://example.com/icon.svg');
        $items_manager->method('get_platform_color')->willReturn('#008BE6');

        $this->setDataServiceProperty('items_manager', $items_manager);

        $frontend_data = $this->data_service->get_frontend_data();

        $this->assertTrue($frontend_data['settings']['analytics']['enabled']);
        $this->assertSame('ga4', $frontend_data['settings']['analytics']['provider']);
        $this->assertSame('support_chat', $frontend_data['settings']['analytics']['event_prefix']);
        $this->assertSame('zalo', $frontend_data['items'][0]['platform']);
    }

    /**
     * Test display-rules context exposes migration contract and runtime state.
     */
    public function test_display_rules_context_summarizes_runtime_state() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'auto_load' => true,
            'device_visibility' => array(
                'desktop' => true,
                'tablet' => false,
                'mobile' => true,
            ),
            'exclude_pages' => array(14, 21),
            'schedule' => array(
                'enabled' => true,
                'timezone' => 'UTC',
                'closed_behavior' => 'hide',
                'weekly_hours' => array(
                    'mon' => array(
                        'enabled' => true,
                        'open' => '09:00',
                        'close' => '17:00',
                    ),
                ),
            ),
        );

        $context = $this->data_service->get_display_rules_context(
            new DateTimeImmutable('2026-03-30 10:00:00', new DateTimeZone('UTC'))
        );

        $this->assertSame('conditions.device_visibility.mobile', $context['contract']['legacy_aliases']['load_on_mobile']);
        $this->assertTrue($context['runtime']['auto_load_enabled']);
        $this->assertTrue($context['runtime']['has_visible_devices']);
        $this->assertTrue($context['runtime']['schedule_allows_display']);
        $this->assertSame(array(14, 21), $context['runtime']['excluded_pages']);
    }

    /**
     * Test schedule availability returns true when schedule logic is disabled.
     */
    public function test_schedule_allows_loading_when_disabled() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'schedule' => array(
                'enabled' => false,
            ),
        );

        $this->assertTrue(
            $this->data_service->is_available_for_schedule(
                new DateTimeImmutable('2026-03-30 12:00:00', new DateTimeZone('UTC'))
            )
        );
    }

    /**
     * Test schedule availability is timezone-aware and open during configured hours.
     */
    public function test_schedule_allows_loading_during_open_hours() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'schedule' => array(
                'enabled' => true,
                'timezone' => 'Asia/Ho_Chi_Minh',
                'closed_behavior' => 'hide',
                'weekly_hours' => array(
                    'mon' => array(
                        'enabled' => true,
                        'open' => '09:00',
                        'close' => '17:00',
                    ),
                ),
            ),
        );

        $this->assertTrue(
            $this->data_service->is_available_for_schedule(
                new DateTimeImmutable('2026-03-30 10:00:00', new DateTimeZone('Asia/Ho_Chi_Minh'))
            )
        );
    }

    /**
     * Test schedule availability hides the widget outside configured hours.
     */
    public function test_schedule_blocks_loading_outside_business_hours() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'enabled' => true,
            'auto_load' => true,
            'schedule' => array(
                'enabled' => true,
                'timezone' => 'UTC',
                'closed_behavior' => 'hide',
                'weekly_hours' => array(
                    'mon' => array(
                        'enabled' => true,
                        'open' => '09:00',
                        'close' => '17:00',
                    ),
                ),
            ),
        );

        $this->assertFalse(
            $this->data_service->is_available_for_schedule(
                new DateTimeImmutable('2026-03-30 18:00:00', new DateTimeZone('UTC'))
            )
        );
    }

    /**
     * Test overnight schedule windows remain available across midnight.
     */
    public function test_schedule_supports_overnight_hours() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'schedule' => array(
                'enabled' => true,
                'timezone' => 'UTC',
                'closed_behavior' => 'hide',
                'weekly_hours' => array(
                    'mon' => array(
                        'enabled' => true,
                        'open' => '22:00',
                        'close' => '02:00',
                    ),
                ),
            ),
        );

        $this->assertTrue(
            $this->data_service->is_available_for_schedule(
                new DateTimeImmutable('2026-03-30 23:30:00', new DateTimeZone('UTC'))
            )
        );
        $this->assertTrue(
            $this->data_service->is_available_for_schedule(
                new DateTimeImmutable('2026-03-31 01:00:00', new DateTimeZone('UTC'))
            )
        );
    }
}
