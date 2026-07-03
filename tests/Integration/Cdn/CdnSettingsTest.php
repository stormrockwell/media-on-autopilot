<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn;

use MediaOnAutopilot\Features\Cdn\CdnSettings;
use WP_UnitTestCase;

final class CdnSettingsTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		remove_all_actions( 'admin_init' );
	}

	public function tearDown(): void {
		remove_all_actions( 'admin_init' );
		delete_option( CdnSettings::OPTION_SERVE );
		parent::tearDown();
	}

	public function test_defaults_to_none(): void {
		delete_option( CdnSettings::OPTION_PROVIDER );
		$this->assertSame( 'none', ( new CdnSettings() )->current() );
	}

	public function test_invalid_value_falls_back_to_none(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'bogus' );
		$this->assertSame( 'none', ( new CdnSettings() )->current() );
	}

	public function test_returns_selected_provider(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'cloudflare' );
		$this->assertSame( 'cloudflare', ( new CdnSettings() )->current() );
	}

	public function test_serve_defaults_off(): void {
		delete_option( CdnSettings::OPTION_SERVE );
		$this->assertFalse( ( new CdnSettings() )->should_serve() );
	}

	public function test_serve_reads_stored_value(): void {
		update_option( CdnSettings::OPTION_SERVE, '1' );
		$this->assertTrue( ( new CdnSettings() )->should_serve() );
	}

	public function test_serve_option_registers(): void {
		( new CdnSettings() )->register();
		do_action( 'admin_init' );
		$this->assertArrayHasKey(
			CdnSettings::OPTION_SERVE,
			get_registered_settings()
		);
	}
}
