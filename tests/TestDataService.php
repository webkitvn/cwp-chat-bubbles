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

        $reflection = new ReflectionClass('CWP_Chat_Bubbles_Data_Service');
        $this->data_service = $reflection->newInstanceWithoutConstructor();

        $settings_property = $reflection->getProperty('settings');
        $settings_property->setAccessible(true);

        $settings_reflection = new ReflectionClass('CWP_Chat_Bubbles_Settings');
        $settings_property->setValue($this->data_service, $settings_reflection->newInstanceWithoutConstructor());
    }

    private function setDataServiceProperty($property, $value) {
        $reflection = new ReflectionClass('CWP_Chat_Bubbles_Data_Service');
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->data_service, $value);
    }

    public function test_should_not_load_when_plugin_is_disabled() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'enabled' => false,
            'auto_load' => true,
        );

        $this->assertFalse($this->data_service->should_load_on_current_page());
    }

    public function test_should_not_load_when_auto_load_is_disabled() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'enabled' => true,
            'auto_load' => false,
        );

        $this->assertFalse($this->data_service->should_load_on_current_page());
    }

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

    public function test_should_load_when_page_is_allowed() {
        global $mock_options, $mock_is_page, $mock_current_page_id;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'enabled' => true,
            'auto_load' => true,
            'exclude_pages' => array(99),
        );
        $mock_is_page = true;
        $mock_current_page_id = 42;

        $this->assertTrue($this->data_service->should_load_on_current_page());
    }

    public function test_frontend_data_no_longer_exposes_removed_display_rule_settings() {
        global $mock_options;

        $mock_options['cwp_chat_bubbles_options'] = array(
            'position' => 'bottom-left',
            'behavior' => array(
                'default_state' => 'open',
                'display_delay' => 5,
                'scroll_trigger_percent' => 20,
                'dismiss_for_session' => true,
            ),
            'analytics' => array(
                'enabled' => true,
                'provider' => 'ga4',
                'event_prefix' => 'support_chat',
            ),
        );

        $items_manager = $this->getMockBuilder(CWP_Chat_Bubbles_Items_Manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('get_all_items', 'generate_platform_url', 'get_platform_icon_url', 'get_platform_color', 'get_item_behavior_settings'))
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
        $items_manager->method('get_item_behavior_settings')->willReturn(
            array(
                'schema_version' => 1,
                'interaction_mode' => 'direct_link',
                'prefill_message' => 'Xin chao',
            )
        );

        $this->setDataServiceProperty('items_manager', $items_manager);

        $frontend_data = $this->data_service->get_frontend_data();

        $this->assertArrayNotHasKey('device_visibility', $frontend_data['settings']);
        $this->assertArrayNotHasKey('schedule', $frontend_data['settings']);
        $this->assertSame('open', $frontend_data['settings']['behavior']['default_state']);
        $this->assertTrue($frontend_data['settings']['analytics']['enabled']);
    }
}
