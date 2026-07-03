<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use MediaOnAutopilot\Features\FocalPoint\Frontend;
use WP_UnitTestCase;

final class FrontendObjectPositionTest extends WP_UnitTestCase {

	public function test_adds_object_position_style_for_off_center_point(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();
		$meta->set( $attachment_id, new FocalPoint( 0.25, 0.75 ) );

		$frontend = new Frontend( $meta );
		$attr     = $frontend->add_object_position( array(), get_post( $attachment_id ), 'medium' );

		$this->assertStringContainsString( 'object-position: 25% 75%', $attr['style'] );
	}

	public function test_skips_centered_point(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();

		$frontend = new Frontend( $meta );
		$attr     = $frontend->add_object_position( array(), get_post( $attachment_id ), 'medium' );

		$this->assertArrayNotHasKey( 'style', $attr );
	}
}
