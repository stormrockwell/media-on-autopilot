<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Bunny;

use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyVerifier;
use WP_UnitTestCase;

final class BunnyVerifierTest extends WP_UnitTestCase {

	public function test_unconfigured_when_host_blank(): void {
		$result = ( new BunnyVerifier( new BunnyConfig( '', 85, 'auto' ) ) )->verify();
		$this->assertSame( 'unconfigured', $result->state );
	}

	public function test_ok_when_host_serves_an_image(): void {
		add_filter(
			'pre_http_request',
			static fn() => array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'image/webp' ),
				'body'     => 'fake-image-bytes',
			),
			10,
			3
		);
		$result = ( new BunnyVerifier( new BunnyConfig( 'zone.b-cdn.net', 85, 'auto' ) ) )->verify();
		$this->assertSame( 'ok', $result->state );
	}

	public function test_error_when_host_responds_but_not_with_an_image(): void {
		// A non-existent pull zone (e.g. moap1234.b-cdn.net) still resolves via the
		// *.b-cdn.net wildcard and answers 200 with an HTML/JSON error page.
		add_filter(
			'pre_http_request',
			static fn() => array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'text/html; charset=utf-8' ),
				'body'     => '<html>Pull zone not found</html>',
			),
			10,
			3
		);
		$result = ( new BunnyVerifier( new BunnyConfig( 'moap1234.b-cdn.net', 85, 'auto' ) ) )->verify();
		$this->assertSame( 'error', $result->state );
		$this->assertSame( 'text/html; charset=utf-8', $result->detail );
	}

	public function test_error_on_http_error_status(): void {
		add_filter(
			'pre_http_request',
			static fn() => array(
				'response' => array( 'code' => 404 ),
				'headers'  => array(),
				'body'     => '',
			),
			10,
			3
		);
		$result = ( new BunnyVerifier( new BunnyConfig( 'zone.b-cdn.net', 85, 'auto' ) ) )->verify();
		$this->assertSame( 'error', $result->state );
		$this->assertStringContainsString( '404', $result->detail );
	}

	public function test_error_on_transport_failure(): void {
		add_filter(
			'pre_http_request',
			static fn() => new \WP_Error( 'http_request_failed', 'dns failure' ),
			10,
			3
		);
		$result = ( new BunnyVerifier( new BunnyConfig( 'zone.b-cdn.net', 85, 'auto' ) ) )->verify();
		$this->assertSame( 'error', $result->state );
		// Raw transport error is suppressed; a generic message is shown instead.
		$this->assertSame( 'The request to the pull zone could not be completed.', $result->detail );
		$this->assertStringNotContainsString( 'dns failure', $result->detail );
	}
}
