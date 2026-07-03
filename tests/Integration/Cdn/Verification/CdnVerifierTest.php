<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Verification;

use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Features\Cdn\Verification\CdnVerifier;
use WP_UnitTestCase;

final class CdnVerifierTest extends WP_UnitTestCase {

	private function verifier(): CdnVerifier {
		return new CdnVerifier( new BunnySettings(), new CloudflareSettings() );
	}

	public function test_unknown_provider_is_unconfigured(): void {
		$this->assertSame( 'unconfigured', $this->verifier()->verify( 'nope' )->state );
	}

	public function test_bunny_unconfigured_without_host(): void {
		delete_option( BunnySettings::OPTION_HOST );
		$this->assertSame( 'unconfigured', $this->verifier()->verify( 'bunny' )->state );
	}

	public function test_bunny_ok_is_cached(): void {
		update_option( BunnySettings::OPTION_HOST, 'zone.b-cdn.net' );
		$calls = 0;
		add_filter(
			'pre_http_request',
			static function () use ( &$calls ) {
				$calls++;
				return array(
					'response' => array( 'code' => 200 ),
					'headers'  => array( 'content-type' => 'image/webp' ),
					'body'     => 'fake-image-bytes',
				);
			},
			10,
			3
		);

		$first  = $this->verifier()->verify( 'bunny' );
		$second = $this->verifier()->verify( 'bunny' );

		$this->assertSame( 'ok', $first->state );
		$this->assertSame( 'ok', $second->state );
		$this->assertSame( 1, $calls, 'Second call should hit the transient cache.' );
	}
}
