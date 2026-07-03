<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\AiTester;
use MediaOnAutopilot\Features\AiTagging\AiTestController;
use MediaOnAutopilot\Features\AiTagging\Connector;
use MediaOnAutopilot\Features\AiTagging\ResponseSchema;
use MediaOnAutopilot\Features\AiTagging\VisionClient;
use WP_REST_Request;
use WP_UnitTestCase;

final class AiTestControllerTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		global $wp_rest_server;
		$wp_rest_server                      = new \Spy_REST_Server();
		$wp_rest_server->override_by_default = true;

		$client = new class implements VisionClient {
			public function describe( string $file_path, string $prompt, array $schema ) {
				return array( 'alt' => 'Sample.', 'tags' => array() );
			}
		};
		$tester = new AiTester( $client, new ResponseSchema(), new Connector() );
		( new AiTestController( $tester ) )->register();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function test_editor_forbidden(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$res = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/moap/v1/ai/test' ) );
		$this->assertSame( 403, $res->get_status() );
	}

	public function test_admin_allowed(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$res = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/moap/v1/ai/test' ) );
		$this->assertNotSame( 403, $res->get_status() );
	}
}
