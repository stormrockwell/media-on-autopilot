<?php
/**
 * Integration tests for AiTester.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\AiTester;
use MediaOnAutopilot\Features\AiTagging\Connector;
use MediaOnAutopilot\Features\AiTagging\ResponseSchema;
use MediaOnAutopilot\Features\AiTagging\VisionClient;
use WP_UnitTestCase;

final class AiTesterTest extends WP_UnitTestCase {

	public function test_returns_alt_and_tags_without_persisting(): void {
		// Connector is final; force availability via its filter, not a subclass.
		add_filter( 'moap_ai_tagging_connector_available', '__return_true' );
		$client = new class implements VisionClient {
			public function describe( string $file_path, string $prompt, array $schema ) {
				return array( 'alt' => 'A field of canola flowers.', 'tags' => array( 'flowers', 'field' ) );
			}
		};

		$result = ( new AiTester( $client, new ResponseSchema(), new Connector() ) )->run();

		$this->assertSame( 'ok', $result['state'] );
		// normalizeAlt strips a leading article ("A ") then sentence-cases the remainder.
		$this->assertSame( 'Field of canola flowers.', $result['alt'] );
		$this->assertContains( 'flowers', $result['tags'] );
	}

	public function test_unconfigured_when_ai_unavailable(): void {
		add_filter( 'moap_ai_tagging_connector_available', '__return_false' );
		$client = $this->createMock( VisionClient::class );
		$client->expects( $this->never() )->method( 'describe' );

		$result = ( new AiTester( $client, new ResponseSchema(), new Connector() ) )->run();

		$this->assertSame( 'unconfigured', $result['state'] );
	}
}
