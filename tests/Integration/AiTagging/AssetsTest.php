<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\Assets;
use WP_UnitTestCase;

final class AssetsTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		wp_dequeue_script( 'moap-ai-tagging' );
		wp_deregister_script( 'moap-ai-tagging' );
		wp_dequeue_style( 'moap-ai-tagging' );
		wp_deregister_style( 'moap-ai-tagging' );
	}

	public function test_enqueues_bundle_on_upload_screen(): void {
		if ( ! file_exists( MOAP_PLUGIN_DIR . 'build/ai-tagging.asset.php' ) ) {
			$this->fail( 'Build assets are missing; run `npm run build` before the integration suite.' );
		}

		( new Assets() )->enqueue( 'upload.php' );

		$this->assertNotNull(
			wp_scripts()->registered['moap-ai-tagging'] ?? null,
			'The AI tagging script was not enqueued.'
		);
	}

	public function test_does_not_enqueue_on_unrelated_screen(): void {
		( new Assets() )->enqueue( 'index.php' );
		$this->assertArrayNotHasKey( 'moap-ai-tagging', wp_scripts()->registered );
	}
}
