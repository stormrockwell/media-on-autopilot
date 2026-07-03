<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn;

use MediaOnAutopilot\Features\Cdn\CdnModule;
use MediaOnAutopilot\Features\Cdn\CdnSettings;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Support\Settings;
use WP_UnitTestCase;

final class CdnModuleTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->reset();
	}

	public function tearDown(): void {
		$this->reset();
		parent::tearDown();
	}

	private function reset(): void {
		global $wp_settings_sections, $wp_settings_fields;
		delete_option( CdnSettings::OPTION_PROVIDER );
		delete_option( CdnSettings::OPTION_SERVE );
		delete_option( BunnySettings::OPTION_HOST );
		delete_option( CloudflareSettings::OPTION_ACCOUNT );
		delete_option( CloudflareSettings::OPTION_TOKEN );
		delete_option( 'moap_cdn_serve_migrated' );
		remove_all_filters( 'image_downsize' );
		remove_all_filters( 'wp_calculate_image_srcset' );
		remove_all_filters( 'moap_focal_point_cache_bust' );
		remove_all_actions( 'add_attachment' );
		remove_all_actions( 'admin_init' );
		unset( $wp_settings_sections[ Settings::MENU_SLUG ], $wp_settings_fields[ Settings::MENU_SLUG ] );
	}

	public function test_active_bunny_module_wires_runtime_hooks(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'bunny' );
		update_option( BunnySettings::OPTION_HOST, 'x.b-cdn.net' );
		update_option( CdnSettings::OPTION_SERVE, '1' );

		( new CdnModule() )->register();

		$this->assertNotFalse( has_filter( 'image_downsize' ) );
		$this->assertNotFalse( has_filter( 'wp_calculate_image_srcset' ) );
		$this->assertNotFalse( has_filter( 'moap_focal_point_cache_bust' ) );
	}

	public function test_provider_none_does_not_wire_runtime_hooks(): void {
		( new CdnModule() )->register();

		$this->assertFalse( has_filter( 'image_downsize' ) );
		$this->assertFalse( has_filter( 'wp_calculate_image_srcset' ) );
		$this->assertFalse( has_filter( 'moap_focal_point_cache_bust' ) );
	}

	public function test_active_cloudflare_module_wires_runtime_hooks(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'cloudflare' );
		update_option( CloudflareSettings::OPTION_ACCOUNT, 'acct123' );
		update_option( CloudflareSettings::OPTION_TOKEN, 'tok456' );
		update_option( CdnSettings::OPTION_SERVE, '1' );

		( new CdnModule() )->register();

		$this->assertNotFalse( has_filter( 'image_downsize' ) );
		$this->assertNotFalse( has_filter( 'wp_calculate_image_srcset' ) );
		$this->assertNotFalse( has_action( 'add_attachment' ) );
	}

	public function test_frontend_not_wired_when_serve_off(): void {
		update_option( \MediaOnAutopilot\Features\Cdn\CdnSettings::OPTION_PROVIDER, 'bunny' );
		update_option( \MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings::OPTION_HOST, 'example.b-cdn.net' );
		update_option( \MediaOnAutopilot\Features\Cdn\CdnSettings::OPTION_SERVE, '0' );
		update_option( 'moap_cdn_serve_migrated', '1' );
		remove_all_filters( 'image_downsize' );

		( new \MediaOnAutopilot\Features\Cdn\CdnModule() )->register();

		$this->assertFalse( has_filter( 'image_downsize' ) );
	}

	public function test_frontend_wired_when_serve_on_and_configured(): void {
		update_option( \MediaOnAutopilot\Features\Cdn\CdnSettings::OPTION_PROVIDER, 'bunny' );
		update_option( \MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings::OPTION_HOST, 'example.b-cdn.net' );
		update_option( \MediaOnAutopilot\Features\Cdn\CdnSettings::OPTION_SERVE, '1' );
		remove_all_filters( 'image_downsize' );

		( new \MediaOnAutopilot\Features\Cdn\CdnModule() )->register();

		$this->assertNotFalse( has_filter( 'image_downsize' ) );
	}

	public function test_frontend_not_wired_when_serve_on_but_unconfigured(): void {
		update_option( \MediaOnAutopilot\Features\Cdn\CdnSettings::OPTION_PROVIDER, 'bunny' );
		update_option( \MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings::OPTION_HOST, '' );
		update_option( \MediaOnAutopilot\Features\Cdn\CdnSettings::OPTION_SERVE, '1' );
		remove_all_filters( 'image_downsize' );

		( new \MediaOnAutopilot\Features\Cdn\CdnModule() )->register();

		$this->assertFalse( has_filter( 'image_downsize' ) );
	}

	public function test_migration_enables_serve_for_configured_bunny(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'bunny' );
		update_option( BunnySettings::OPTION_HOST, 'x.b-cdn.net' );
		// No moap_cdn_serve and no moap_cdn_serve_migrated set.

		( new CdnModule() )->register();

		$this->assertSame( '1', get_option( CdnSettings::OPTION_SERVE ) );
		$this->assertNotFalse( has_filter( 'image_downsize' ) );
	}

	public function test_migration_does_not_enable_serve_for_provider_none(): void {
		// No provider, no migrated flag.

		( new CdnModule() )->register();

		$this->assertNotSame( '1', get_option( CdnSettings::OPTION_SERVE ) );
		$this->assertSame( '1', get_option( 'moap_cdn_serve_migrated' ) );
	}

	public function test_migration_skipped_when_already_migrated(): void {
		update_option( 'moap_cdn_serve_migrated', '1' );
		update_option( CdnSettings::OPTION_PROVIDER, 'bunny' );
		update_option( BunnySettings::OPTION_HOST, 'x.b-cdn.net' );
		update_option( CdnSettings::OPTION_SERVE, '0' );

		( new CdnModule() )->register();

		// Migration must not re-enable serve when flag is already set.
		$this->assertSame( '0', get_option( CdnSettings::OPTION_SERVE ) );
		$this->assertFalse( has_filter( 'image_downsize' ) );
	}
}
