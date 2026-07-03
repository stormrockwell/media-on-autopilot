<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn;

use MediaOnAutopilot\Features\Cdn\CdnVerifyController;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Features\Cdn\Verification\CdnVerifier;
use WP_REST_Request;
use WP_UnitTestCase;

final class CdnVerifyControllerTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		global $wp_rest_server;
		$wp_rest_server                      = new \Spy_REST_Server();
		$wp_rest_server->override_by_default = true;

		$verifier = new CdnVerifier( new BunnySettings(), new CloudflareSettings() );
		( new CdnVerifyController( $verifier ) )->register();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function test_forbidden_for_non_admin(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$request = new WP_REST_Request( 'GET', '/moap/v1/cdn/verify' );
		$request->set_param( 'provider', 'bunny' );
		$this->assertSame( 403, rest_do_request( $request )->get_status() );
	}

	public function test_returns_state_for_admin(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		delete_option( BunnySettings::OPTION_HOST );
		$request = new WP_REST_Request( 'GET', '/moap/v1/cdn/verify' );
		$request->set_param( 'provider', 'bunny' );

		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'unconfigured', $response->get_data()['state'] );
	}
}
