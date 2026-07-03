<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\ImageResizer;
use WP_UnitTestCase;

final class ImageResizerTest extends WP_UnitTestCase {

	public function test_resizes_to_max_edge_and_returns_path(): void {
		add_filter( 'moap_ai_tagging_resize_max_edge', static fn(): int => 100 );

		$file          = DIR_TESTDATA . '/images/canola.jpg'; // 640x480
		$attachment_id = self::factory()->attachment->create_upload_object( $file );

		$path = ( new ImageResizer() )->resize( $attachment_id );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );
		$size = getimagesize( $path );
		$this->assertLessThanOrEqual( 100, $size[0] );
		$this->assertLessThanOrEqual( 100, $size[1] );

		unlink( $path );
	}

	public function test_returns_error_for_missing_file(): void {
		$attachment_id = self::factory()->attachment->create(); // no real file
		$this->assertWPError( ( new ImageResizer() )->resize( $attachment_id ) );
	}
}
