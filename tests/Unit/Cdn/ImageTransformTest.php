<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn;

use MediaOnAutopilot\Features\Cdn\ImageTransform;
use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use PHPUnit\Framework\TestCase;

final class ImageTransformTest extends TestCase {

	public function test_holds_all_fields(): void {
		$t = new ImageTransform( 42, 600, 400, true, new FocalPoint( 0.8, 0.4 ), 1200, 900 );
		$this->assertSame( 42, $t->attachment_id );
		$this->assertSame( 600, $t->width );
		$this->assertSame( 400, $t->height );
		$this->assertTrue( $t->crop );
		$this->assertSame( 0.8, $t->focal->x );
		$this->assertSame( 1200, $t->orig_width );
	}

	public function test_center_builds_uncropped_full_width_spec(): void {
		$t = ImageTransform::center( 7, 800 );
		$this->assertSame( 7, $t->attachment_id );
		$this->assertSame( 800, $t->width );
		$this->assertSame( 0, $t->height );
		$this->assertFalse( $t->crop );
		$this->assertNull( $t->focal );
	}
}
