<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\CropHandler;
use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use WP_UnitTestCase;

final class CropHandlerTest extends WP_UnitTestCase {

	public function test_returns_payload_unchanged_when_no_context(): void {
		$handler = new CropHandler( new FocalPointMeta() );
		$this->assertNull( $handler->apply_focal_point( null, 1000, 500, 200, 200, true ) );
	}

	public function test_returns_payload_unchanged_for_centered_focal_point(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();
		$handler       = new CropHandler( $meta );

		$handler->capture_context( array(), array(), $attachment_id );
		$this->assertNull( $handler->apply_focal_point( null, 1000, 500, 200, 200, true ) );
	}

	public function test_applies_offset_for_off_center_focal_point(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();
		$meta->set( $attachment_id, new FocalPoint( 1.0, 0.5 ) );

		$handler = new CropHandler( $meta );
		$handler->capture_context( array(), array(), $attachment_id );

		$dims = $handler->apply_focal_point( null, 1000, 500, 200, 200, true );
		$this->assertIsArray( $dims );
		$this->assertSame( 500, $dims[2] ); // src_x shifted fully right
	}

	public function test_clear_context_disables_offset(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();
		$meta->set( $attachment_id, new FocalPoint( 1.0, 0.5 ) );

		$handler = new CropHandler( $meta );
		$handler->capture_context( array(), array(), $attachment_id );
		$handler->clear_context( array(), $attachment_id );

		$this->assertNull( $handler->apply_focal_point( null, 1000, 500, 200, 200, true ) );
	}
}
