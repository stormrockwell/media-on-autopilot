<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImagesApiClient;
use WP_UnitTestCase;

final class ImagesApiClientVerifyTest extends WP_UnitTestCase {

	private function config(): CloudflareConfig {
		return new CloudflareConfig( 'acct123', 'token456', 'hashabc', 85, 'auto' );
	}

	public function test_unconfigured_without_credentials(): void {
		$result = ( new ImagesApiClient( new CloudflareConfig( '', '', '', 85, 'auto' ) ) )->verify();
		$this->assertSame( 'unconfigured', $result->state );
	}

	public function test_ok_on_success_response(): void {
		add_filter(
			'pre_http_request',
			static fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'success' => true, 'result' => array( 'images' => array() ) ) ),
			),
			10,
			3
		);
		$this->assertSame( 'ok', ( new ImagesApiClient( $this->config() ) )->verify()->state );
	}

	public function test_error_on_rejected_credentials(): void {
		add_filter(
			'pre_http_request',
			static fn() => array(
				'response' => array( 'code' => 401 ),
				'body'     => wp_json_encode( array( 'success' => false, 'errors' => array( array( 'message' => 'Invalid token' ) ) ) ),
			),
			10,
			3
		);
		$result = ( new ImagesApiClient( $this->config() ) )->verify();
		$this->assertSame( 'error', $result->state );
		$this->assertSame( 'Invalid token', $result->detail );
	}
}
