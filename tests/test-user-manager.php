<?php

use PHPUnit\Framework\TestCase;
use Mockery as m;

class TestUserManager extends TestCase {
    private $user_manager;
    private $wpdb;

    public function setUp(): void {
        parent::setUp();
        
        // Mock wpdb
        $this->wpdb = m::mock('wpdb');
        global $wpdb;
        $wpdb = $this->wpdb;
        
        $this->user_manager = new UserManager();
    }

    public function tearDown(): void {
        m::close();
        parent::tearDown();
    }

    public function testFindOrCreateUserWithExistingUser() {
        $phone_number = '1234567890';
        $existing_user = (object)[
            'wp_user_id' => 1,
            'wc_customer_id' => 1
        ];

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('prepared query');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->with('prepared query')
            ->andReturn($existing_user);

        $result = $this->user_manager->find_or_create_user($phone_number);

        $this->assertFalse($result['is_new']);
        $this->assertEquals(1, $result['user_id']);
        $this->assertEquals(1, $result['customer_id']);
    }

    public function testFindOrCreateUserWithNewUser() {
        $phone_number = '1234567890';

        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('prepared query');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->with('prepared query')
            ->andReturn(null);

        // Mock wp_create_user
        $this->assertTrue(function_exists('wp_create_user'), 'wp_create_user function not found');
        
        // Mock WC_Customer
        $customer = m::mock('WC_Customer');
        $customer->shouldReceive('set_username')->once()->andReturn(null);
        $customer->shouldReceive('set_email')->once()->andReturn(null);
        $customer->shouldReceive('set_billing_phone')->once()->andReturn(null);
        $customer->shouldReceive('save')->once()->andReturn(1);
        $customer->shouldReceive('get_id')->once()->andReturn(1);

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(1);

        $result = $this->user_manager->find_or_create_user($phone_number);

        $this->assertTrue($result['is_new']);
        $this->assertIsInt($result['user_id']);
        $this->assertEquals(1, $result['customer_id']);
    }

    public function testUpdateLastInteraction() {
        $phone_number = '1234567890';

        $this->wpdb->shouldReceive('update')
            ->once()
            ->andReturn(1);

        $result = $this->user_manager->update_last_interaction($phone_number);

        $this->assertEquals(1, $result);
    }
}
