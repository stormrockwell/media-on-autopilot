<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\FocalPointSetting;
use WP_UnitTestCase;

final class FocalPointSettingTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		remove_all_actions( 'admin_init' );
	}

	public function tearDown(): void {
		delete_option( FocalPointSetting::OPTION );
		parent::tearDown();
	}

	public function test_defaults_to_enabled_when_unset(): void {
		delete_option( FocalPointSetting::OPTION );
		$this->assertTrue( ( new FocalPointSetting() )->is_enabled() );
	}

	public function test_reflects_stored_off_value(): void {
		update_option( FocalPointSetting::OPTION, '0' );
		$this->assertFalse( ( new FocalPointSetting() )->is_enabled() );
	}

	public function test_registers_the_option_on_admin_init(): void {
		( new FocalPointSetting() )->register();
		do_action( 'admin_init' );
		$registered = get_registered_settings();
		$this->assertArrayHasKey( FocalPointSetting::OPTION, $registered );
	}
}
