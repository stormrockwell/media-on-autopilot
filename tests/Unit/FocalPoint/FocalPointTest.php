<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use PHPUnit\Framework\TestCase;

final class FocalPointTest extends TestCase {

	public function test_center_is_half_half(): void {
		$point = FocalPoint::center();
		$this->assertSame( 0.5, $point->x );
		$this->assertSame( 0.5, $point->y );
		$this->assertTrue( $point->is_center() );
	}

	public function test_from_array_clamps_out_of_range_values(): void {
		$point = FocalPoint::from_array( array( 'x' => 1.4, 'y' => -0.2 ) );
		$this->assertSame( 1.0, $point->x );
		$this->assertSame( 0.0, $point->y );
	}

	public function test_to_array_round_trips(): void {
		$point = FocalPoint::from_array( array( 'x' => 0.3, 'y' => 0.7 ) );
		$this->assertSame( array( 'x' => 0.3, 'y' => 0.7 ), $point->to_array() );
	}

	public function test_percent_helpers_round_to_int(): void {
		$point = FocalPoint::from_array( array( 'x' => 0.524, 'y' => 0.464 ) );
		$this->assertSame( 52, $point->x_percent() );
		$this->assertSame( 46, $point->y_percent() );
	}
}
