<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Bunny;

use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use WP_UnitTestCase;

final class BunnySettingsTest extends WP_UnitTestCase {

	public function test_to_config_reads_options(): void {
		update_option( BunnySettings::OPTION_HOST, 'https://x.b-cdn.net/' );
		update_option( BunnySettings::OPTION_QUALITY, '70' );
		update_option( BunnySettings::OPTION_FORMAT, 'webp' );

		$config = ( new BunnySettings() )->to_config();

		$this->assertSame( 'x.b-cdn.net', $config->host );
		$this->assertSame( 70, $config->quality );
		$this->assertSame( 'webp', $config->format );
		// A host is enough to be active — selecting BunnyCDN is the on-switch.
		$this->assertTrue( $config->is_active() );
	}

	public function test_defaults_when_unset(): void {
		$config = ( new BunnySettings() )->to_config();
		$this->assertSame( '', $config->host );
		$this->assertSame( 85, $config->quality );
		$this->assertSame( 'auto', $config->format );
		$this->assertFalse( $config->is_active() );
	}

}
