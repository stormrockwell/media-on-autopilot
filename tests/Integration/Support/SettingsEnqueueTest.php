<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Support;

use MediaOnAutopilot\Support\Settings;
use WP_UnitTestCase;

final class SettingsEnqueueTest extends WP_UnitTestCase {

	public function tearDown(): void {
		parent::tearDown();
		wp_dequeue_script( 'moap-admin' );
		wp_deregister_script( 'moap-admin' );
		wp_dequeue_style( 'moap-admin' );
		wp_deregister_style( 'moap-admin' );
	}

	public function test_admin_bundle_enqueued_on_options_page(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		Settings::enqueue_assets( 'settings_page_' . Settings::MENU_SLUG );
		$this->assertTrue( wp_script_is( 'moap-admin', 'enqueued' ) );
	}

	public function test_admin_style_enqueued_on_options_page(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		Settings::enqueue_assets( 'settings_page_' . Settings::MENU_SLUG );
		$this->assertTrue( wp_style_is( 'moap-admin', 'enqueued' ) );
	}

	public function test_admin_bundle_not_enqueued_elsewhere(): void {
		Settings::enqueue_assets( 'index.php' );
		$this->assertFalse( wp_script_is( 'moap-admin', 'enqueued' ) );
	}

	public function test_moap_admin_localized_with_state_keys(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		Settings::enqueue_assets( 'settings_page_' . Settings::MENU_SLUG );

		$data = wp_scripts()->get_data( 'moap-admin', 'data' );
		$this->assertNotEmpty( $data, 'moap-admin should have localized data' );

		// Extract the moapAdmin object from the inline script data.
		preg_match( '/var moapAdmin\s*=\s*(\{.*\});/s', $data, $matches );
		$this->assertNotEmpty( $matches, 'moapAdmin should be present in localized data' );

		$decoded = json_decode( $matches[1], true );
		$this->assertIsArray( $decoded );
		$this->assertArrayHasKey( 'restBase', $decoded );
		$this->assertArrayHasKey( 'nonce', $decoded );
		$this->assertArrayHasKey( 'sampleImage', $decoded );
		$this->assertArrayHasKey( 'labels', $decoded );

		// state must be present with values/status/tools sub-keys.
		$this->assertArrayHasKey( 'state', $decoded );
		$state = $decoded['state'];
		$this->assertArrayHasKey( 'values', $state );
		$this->assertArrayHasKey( 'status', $state );
		$this->assertArrayHasKey( 'tools', $state );
	}

	public function test_sections_and_status_keys_not_present(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		Settings::enqueue_assets( 'settings_page_' . Settings::MENU_SLUG );

		$data = wp_scripts()->get_data( 'moap-admin', 'data' );
		preg_match( '/var moapAdmin\s*=\s*(\{.*\});/s', $data, $matches );
		$decoded = json_decode( $matches[1], true );

		$this->assertArrayNotHasKey( 'sections', $decoded, 'sections key should be removed from moapAdmin' );
		$this->assertArrayNotHasKey( 'status', $decoded, 'top-level status key should be removed from moapAdmin' );
	}
}
