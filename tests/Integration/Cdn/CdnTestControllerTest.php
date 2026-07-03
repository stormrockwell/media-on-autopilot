<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn;

use MediaOnAutopilot\Features\Cdn\CdnSettings;
use MediaOnAutopilot\Features\Cdn\CdnTester;
use MediaOnAutopilot\Features\Cdn\CdnTestController;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use WP_REST_Request;
use WP_UnitTestCase;

final class CdnTestControllerTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		global $wp_rest_server;
		$wp_rest_server                      = new \Spy_REST_Server();
		$wp_rest_server->override_by_default = true;

		$tester = new CdnTester(
			new CdnSettings(),
			new BunnySettings(),
			new CloudflareSettings(),
			static fn() => array( 'status' => 200, 'format' => 'webp' )
		);
		( new CdnTestController( $tester ) )->register();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function test_editor_forbidden(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$res = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/moap/v1/cdn/test' ) );
		$this->assertSame( 403, $res->get_status() );
	}

	public function test_admin_allowed(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$res = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/moap/v1/cdn/test' ) );
		$this->assertNotSame( 403, $res->get_status() );
	}
}
