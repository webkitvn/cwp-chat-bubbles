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
}
