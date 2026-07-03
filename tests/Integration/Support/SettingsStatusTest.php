<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Support;

use MediaOnAutopilot\Features\Cdn\CdnSettings;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Support\Settings\SettingsStatus;
use WP_UnitTestCase;

final class SettingsStatusTest extends WP_UnitTestCase {

	public function test_reports_configured_but_not_serving(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'bunny' );
		update_option( BunnySettings::OPTION_HOST, 'mysite.b-cdn.net' );
		update_option( CdnSettings::OPTION_SERVE, '0' );

		$data = ( new SettingsStatus() )->to_array();

		$this->assertSame( 'bunny', $data['provider'] );
		$this->assertTrue( $data['cdnConfigured'] );
		$this->assertFalse( $data['serving'] );
	}

	public function test_focal_default_enabled_and_offload_zero_without_cloudflare(): void {
		$data = ( new SettingsStatus() )->to_array();
		$this->assertTrue( $data['focalEnabled'] );
		$this->assertSame( array( 'done' => 0, 'total' => 0 ), $data['offloaded'] );
	}
}
