<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use WP_UnitTestCase;

final class CloudflareSettingsTest extends WP_UnitTestCase {

	public function test_to_config_reads_options(): void {
		update_option( CloudflareSettings::OPTION_ACCOUNT, 'acct-1' );
		update_option( CloudflareSettings::OPTION_TOKEN, 'tok-1' );
		update_option( CloudflareSettings::OPTION_HASH, 'hash-1' );
		update_option( CloudflareSettings::OPTION_QUALITY, 70 );
		update_option( CloudflareSettings::OPTION_FORMAT, 'webp' );

		$config = ( new CloudflareSettings() )->to_config();

		$this->assertSame( 'acct-1', $config->account_id );
		$this->assertSame( 'tok-1', $config->api_token );
		$this->assertSame( 'hash-1', $config->account_hash );
		$this->assertSame( 70, $config->quality );
		$this->assertSame( 'webp', $config->format );
		$this->assertTrue( $config->is_active() );
	}
}
