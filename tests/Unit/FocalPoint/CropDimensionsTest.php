<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\CropDimensions;
use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use PHPUnit\Framework\TestCase;

final class CropDimensionsTest extends TestCase {

	public function test_centered_focal_point_centers_the_crop(): void {
		// 1000x500 source cropped to a 200x200 square.
		$dims = CropDimensions::calculate( 1000, 500, 200, 200, FocalPoint::center() );
		// src_w = src_h = 500 (square from the 500px height); src_x centered = (1000-500)/2 = 250.
		$this->assertSame( 250, $dims[2] ); // src_x
		$this->assertSame( 0, $dims[3] );   // src_y
		$this->assertSame( 500, $dims[6] ); // src_w
		$this->assertSame( 500, $dims[7] ); // src_h
	}

	public function test_left_focal_point_shifts_crop_left(): void {
		$dims = CropDimensions::calculate( 1000, 500, 200, 200, new FocalPoint( 0.0, 0.5 ) );
		$this->assertSame( 0, $dims[2] ); // src_x flush left
	}

	public function test_right_focal_point_shifts_crop_right(): void {
		$dims = CropDimensions::calculate( 1000, 500, 200, 200, new FocalPoint( 1.0, 0.5 ) );
		$this->assertSame( 500, $dims[2] ); // src_x = orig_w - crop_w = 1000-500
	}

	public function test_returns_null_for_zero_source(): void {
		$this->assertNull( CropDimensions::calculate( 0, 0, 200, 200, FocalPoint::center() ) );
	}
}
