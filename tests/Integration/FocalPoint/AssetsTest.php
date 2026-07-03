<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\Assets;
use WP_UnitTestCase;

final class AssetsTest extends WP_UnitTestCase {

	public function test_script_enqueues_without_plupload(): void {
		if ( ! file_exists( MOAP_PLUGIN_DIR . 'build/index.asset.php' ) ) {
			$this->fail( 'Build assets are missing; run `npm run build` before the integration suite.' );
		}

		( new Assets() )->enqueue( 'post.php' );

		$registered = wp_scripts()->registered['moap-focal-point'] ?? null;
		$this->assertNotNull( $registered, 'The focal point script was not enqueued.' );
		$this->assertNotContains(
			'wp-plupload',
			$registered->deps,
			'The focal point picker no longer depends on Plupload.'
		);

		$data = wp_scripts()->get_data( 'moap-focal-point', 'data' );
		$this->assertIsString( $data );
		$this->assertStringNotContainsString( 'autoDetect', $data );
	}
}
