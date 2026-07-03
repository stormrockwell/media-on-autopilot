<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\Regenerator;
use WP_UnitTestCase;

final class RegeneratorTest extends WP_UnitTestCase {

	public function test_regenerate_rebuilds_metadata_sizes(): void {
		$file          = DIR_TESTDATA . '/images/canola.jpg';
		$attachment_id = self::factory()->attachment->create_upload_object( $file );

		$result = ( new Regenerator() )->regenerate( $attachment_id );

		$this->assertTrue( $result );
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$this->assertArrayHasKey( 'sizes', $metadata );
		$this->assertNotEmpty( $metadata['sizes'] );
	}

	public function test_regenerate_returns_false_for_missing_file(): void {
		$attachment_id = self::factory()->attachment->create(); // no real file
		$this->assertFalse( ( new Regenerator() )->regenerate( $attachment_id ) );
	}
}
