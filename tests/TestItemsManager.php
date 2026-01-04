<?php
/**
 * Tests for CWP_Chat_Bubbles_Items_Manager class
 *
 * @package CWP_Chat_Bubbles
 */

use PHPUnit\Framework\TestCase;

class TestItemsManager extends TestCase {

    private $manager;

    protected function setUp(): void {
        $this->manager = $this->createTestableManager();
    }

    private function createTestableManager() {
        $reflection = new ReflectionClass('CWP_Chat_Bubbles_Items_Manager');
        $instance = $reflection->newInstanceWithoutConstructor();
        return $instance;
    }

    /**
     * Test validate_contact_value with valid phone numbers
     */
    public function test_validate_contact_value_valid_phone() {
        $valid_phones = array(
            '+1234567890',
            '1234567890',
            '+1 (234) 567-890',
            '123-456-7890',
        );

        foreach ($valid_phones as $phone) {
            $result = $this->manager->validate_contact_value('phone', $phone);
            $this->assertNotEmpty($result, "Phone number should be valid: $phone");
        }
    }

    /**
     * Test validate_contact_value with invalid phone numbers
     */
    public function test_validate_contact_value_invalid_phone() {
        $invalid_phones = array(
            '123',              // Too short
            'abcdefghij',       // Not numeric
            '<script>alert(1)</script>',
        );

        foreach ($invalid_phones as $phone) {
            $result = $this->manager->validate_contact_value('phone', $phone);
            $this->assertEmpty($result, "Phone number should be invalid: $phone");
        }
    }

    /**
     * Test validate_contact_value with valid Zalo numbers
     */
    public function test_validate_contact_value_valid_zalo() {
        $valid_numbers = array(
            '0123456789',
            '123456789',
            '01234567890',
        );

        foreach ($valid_numbers as $number) {
            $result = $this->manager->validate_contact_value('zalo', $number);
            $this->assertNotEmpty($result, "Zalo number should be valid: $number");
        }
    }

    /**
     * Test validate_contact_value with invalid Zalo numbers
     */
    public function test_validate_contact_value_invalid_zalo() {
        $invalid_numbers = array(
            '12345678',         // Too short (< 9 digits)
            '123456789012',     // Too long (> 11 digits)
            '+1234567890',      // Has + sign
            'abcdefghi',        // Not numeric
        );

        foreach ($invalid_numbers as $number) {
            $result = $this->manager->validate_contact_value('zalo', $number);
            $this->assertEmpty($result, "Zalo number should be invalid: $number");
        }
    }

    /**
     * Test validate_contact_value with valid WhatsApp numbers
     */
    public function test_validate_contact_value_valid_whatsapp() {
        $valid_numbers = array(
            '1234567890',
            '+1234567890',
            '12345678901234',
        );

        foreach ($valid_numbers as $number) {
            $result = $this->manager->validate_contact_value('whatsapp', $number);
            $this->assertNotEmpty($result, "WhatsApp number should be valid: $number");
        }
    }

    /**
     * Test validate_contact_value with invalid WhatsApp numbers
     */
    public function test_validate_contact_value_invalid_whatsapp() {
        $invalid_numbers = array(
            '123456',           // Too short
            '0123456789',       // Starts with 0
        );

        foreach ($invalid_numbers as $number) {
            $result = $this->manager->validate_contact_value('whatsapp', $number);
            $this->assertEmpty($result, "WhatsApp number should be invalid: $number");
        }
    }

    /**
     * Test validate_contact_value with valid Telegram usernames
     */
    public function test_validate_contact_value_valid_telegram() {
        $valid_usernames = array(
            'username',
            'user_name',
            'Username123',
            'a12345',
        );

        foreach ($valid_usernames as $username) {
            $result = $this->manager->validate_contact_value('telegram', $username);
            $this->assertNotEmpty($result, "Telegram username should be valid: $username");
        }
    }

    /**
     * Test validate_contact_value with invalid Telegram usernames
     */
    public function test_validate_contact_value_invalid_telegram() {
        $invalid_usernames = array(
            'usr',              // Too short (< 5 chars)
            '1username',        // Starts with number
            '_username',        // Starts with underscore
        );

        foreach ($invalid_usernames as $username) {
            $result = $this->manager->validate_contact_value('telegram', $username);
            $this->assertEmpty($result, "Telegram username should be invalid: $username");
        }
    }

    /**
     * Test validate_contact_value with valid Messenger usernames
     */
    public function test_validate_contact_value_valid_messenger() {
        $valid_usernames = array(
            'username',
            'user.name',
            'Username123',
        );

        foreach ($valid_usernames as $username) {
            $result = $this->manager->validate_contact_value('messenger', $username);
            $this->assertNotEmpty($result, "Messenger username should be valid: $username");
        }
    }

    /**
     * Test validate_contact_value with invalid Messenger usernames
     */
    public function test_validate_contact_value_invalid_messenger() {
        $invalid_usernames = array(
            '.username',        // Starts with dot
        );

        foreach ($invalid_usernames as $username) {
            $result = $this->manager->validate_contact_value('messenger', $username);
            $this->assertEmpty($result, "Messenger username should be invalid: $username");
        }
    }

    /**
     * Test validate_contact_value rejects empty values
     */
    public function test_validate_contact_value_empty() {
        $platforms = array('phone', 'zalo', 'whatsapp', 'viber', 'telegram', 'messenger', 'line', 'kakaotalk');
        
        foreach ($platforms as $platform) {
            $this->assertFalse(
                $this->manager->validate_contact_value($platform, ''),
                "Empty value should be invalid for $platform"
            );
        }
    }

    /**
     * Test validate_contact_value rejects unknown platforms
     */
    public function test_validate_contact_value_unknown_platform() {
        $this->assertFalse(
            $this->manager->validate_contact_value('unknown_platform', '1234567890'),
            "Unknown platform should return false"
        );
    }

    /**
     * Test validate_contact_value rejects values exceeding max length
     */
    public function test_validate_contact_value_too_long() {
        $long_value = str_repeat('1', 101); // 101 characters
        
        $this->assertFalse(
            $this->manager->validate_contact_value('phone', $long_value),
            "Value exceeding 100 chars should be invalid"
        );
    }

    /**
     * Test validate_contact_value rejects XSS attempts
     */
    public function test_validate_contact_value_xss_prevention() {
        $xss_attempts = array(
            '<script>alert(1)</script>',
            'javascript:alert(1)',
            'onload=alert(1)',
            'onerror=alert(1)',
            'onclick=alert(1)',
            'onmouseover=alert(1)',
            'expression(alert(1))',
            'vbscript:alert(1)',
        );

        foreach ($xss_attempts as $xss) {
            $this->assertFalse(
                $this->manager->validate_contact_value('phone', $xss),
                "XSS attempt should be rejected: $xss"
            );
        }
    }

    /**
     * Test get_platform_config returns correct configuration
     */
    public function test_get_platform_config() {
        $config = $this->manager->get_platform_config('whatsapp');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('label', $config);
        $this->assertArrayHasKey('pattern', $config);
        $this->assertEquals('WhatsApp', $config['label']);
    }

    /**
     * Test get_platform_config returns null for unknown platform
     */
    public function test_get_platform_config_unknown() {
        $config = $this->manager->get_platform_config('unknown');
        
        $this->assertNull($config);
    }

    /**
     * Test get_platform_color returns correct colors
     */
    public function test_get_platform_color() {
        $this->assertEquals('#25D366', $this->manager->get_platform_color('whatsapp'));
        $this->assertEquals('#0088cc', $this->manager->get_platform_color('telegram'));
        $this->assertEquals('#52BA00', $this->manager->get_platform_color('unknown')); // Default
    }

    /**
     * Test generate_platform_url for various platforms
     */
    public function test_generate_platform_url() {
        $test_cases = array(
            array(
                'platform' => 'phone',
                'item' => array('contact_value' => '+1234567890'),
                'expected' => 'tel:+1234567890'
            ),
            array(
                'platform' => 'whatsapp',
                'item' => array('contact_value' => '1234567890'),
                'expected' => 'https://wa.me/1234567890'
            ),
            array(
                'platform' => 'zalo',
                'item' => array('contact_value' => '0123456789'),
                'expected' => 'https://zalo.me/0123456789?openChat=true'
            ),
            array(
                'platform' => 'telegram',
                'item' => array('contact_value' => 'username'),
                'expected' => 'https://t.me/username'
            ),
            array(
                'platform' => 'messenger',
                'item' => array('contact_value' => 'user123'),
                'expected' => 'https://m.me/user123'
            ),
            array(
                'platform' => 'viber',
                'item' => array('contact_value' => '+1234567890'),
                'expected' => 'viber://contact?number=+1234567890'
            ),
            array(
                'platform' => 'line',
                'item' => array('contact_value' => 'lineid'),
                'expected' => 'https://line.me/ti/p/lineid'
            ),
        );

        foreach ($test_cases as $case) {
            $url = $this->manager->generate_platform_url($case['platform'], $case['item']);
            $this->assertEquals(
                $case['expected'],
                $url,
                "URL for {$case['platform']} should be correct"
            );
        }
    }

    /**
     * Test generate_platform_url with empty contact value
     */
    public function test_generate_platform_url_empty() {
        $url = $this->manager->generate_platform_url('whatsapp', array('contact_value' => ''));
        $this->assertEquals('#', $url);
    }

    /**
     * Test generate_platform_url for unknown platform
     */
    public function test_generate_platform_url_unknown() {
        $url = $this->manager->generate_platform_url('unknown', array('contact_value' => 'test'));
        $this->assertEquals('#', $url);
    }

    /**
     * Test get_supported_platforms returns all platforms
     */
    public function test_get_supported_platforms() {
        $platforms = $this->manager->get_supported_platforms();
        
        $this->assertIsArray($platforms);
        $this->assertArrayHasKey('phone', $platforms);
        $this->assertArrayHasKey('whatsapp', $platforms);
        $this->assertArrayHasKey('zalo', $platforms);
        $this->assertArrayHasKey('telegram', $platforms);
        $this->assertArrayHasKey('messenger', $platforms);
        $this->assertArrayHasKey('viber', $platforms);
        $this->assertArrayHasKey('line', $platforms);
        $this->assertArrayHasKey('kakaotalk', $platforms);
    }

    /**
     * Test get_platform_icon_url returns correct URLs
     */
    public function test_get_platform_icon_url() {
        $url = $this->manager->get_platform_icon_url('whatsapp');
        
        $this->assertStringContainsString('whatsapp.svg', $url);
        $this->assertStringContainsString('assets/images/socials/', $url);
    }

    /**
     * Test get_platform_icon_url for unknown platform uses default
     */
    public function test_get_platform_icon_url_unknown() {
        $url = $this->manager->get_platform_icon_url('unknown');
        
        // Unknown platform returns a URL with 'unknown.svg'
        $this->assertStringContainsString('.svg', $url);
    }
}
