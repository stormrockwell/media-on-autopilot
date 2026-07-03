<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\Connector;
use MediaOnAutopilot\Features\AiTagging\ImageResizer;
use MediaOnAutopilot\Features\AiTagging\MediaTaxonomy;
use MediaOnAutopilot\Features\AiTagging\ResponseSchema;
use MediaOnAutopilot\Features\AiTagging\RestController;
use MediaOnAutopilot\Features\AiTagging\Tagger;
use MediaOnAutopilot\Features\AiTagging\VisionClient;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use WP_REST_Request;
use WP_UnitTestCase;

final class RestControllerTest extends WP_UnitTestCase {

	private function fakeTagger(): Tagger {
		$client = new class implements VisionClient {
			public function describe( string $file_path, string $prompt, array $schema ) {
				return array( 'alt' => 'a cat', 'tags' => array( 'cat' ), 'focal' => array( 'x' => 0.5, 'y' => 0.5 ) );
			}
		};
		return new Tagger( $client, new ImageResizer(), new ResponseSchema(), new FocalPointMeta() );
	}

	public function setUp(): void {
		parent::setUp();
		( new MediaTaxonomy() )->register_taxonomy();
		add_filter( 'moap_ai_tagging_connector_available', '__return_true' );

		// Reset the REST server so route registrations from a globally-booted
		// AiTaggingModule don't interfere. override_by_default ensures the last
		// registration wins, so the fake tagger registered below takes precedence.
		global $wp_rest_server;
		$wp_rest_server                    = new \Spy_REST_Server();
		$wp_rest_server->override_by_default = true;

		( new RestController( $this->fakeTagger(), new Connector() ) )->register();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function test_requires_edit_capability(): void {
		$id      = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$request = new WP_REST_Request( 'POST', "/moap/v1/ai-tagging/$id" );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = rest_do_request( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_tags_attachment_for_editor(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id      = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$request = new WP_REST_Request( 'POST', "/moap/v1/ai-tagging/$id" );

		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'Cat', $data['alt'] );
		$this->assertSame( array( 'cat' ), $data['tags'] );
	}

	public function test_returns_503_when_connector_unavailable(): void {
		remove_filter( 'moap_ai_tagging_connector_available', '__return_true' );
		add_filter( 'moap_ai_tagging_connector_available', '__return_false' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id      = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$request = new WP_REST_Request( 'POST', "/moap/v1/ai-tagging/$id" );

		$this->assertSame( 503, rest_do_request( $request )->get_status() );
	}
}
