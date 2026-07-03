<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn;

use MediaOnAutopilot\Features\Cdn\WidthLadder;
use PHPUnit\Framework\TestCase;

final class WidthLadderTest extends TestCase {

	public function test_target_width_is_capped_by_orig_and_max(): void {
		$this->assertSame( 600, WidthLadder::target_width( 600, 1200, 2560 ) );
		$this->assertSame( 1200, WidthLadder::target_width( 4000, 1200, 2560 ) );
		$this->assertSame( 2560, WidthLadder::target_width( 4000, 9000, 2560 ) );
	}

	public function test_ladder_widths_cap_and_include_cap(): void {
		$this->assertSame( array( 320, 480, 640, 768, 1000 ), WidthLadder::ladder_widths( 1000, 2560 ) );
		$this->assertSame( WidthLadder::LADDER, WidthLadder::ladder_widths( 2560, 2560 ) );
		$this->assertSame( array(), WidthLadder::ladder_widths( 0, 2560 ) );
	}
}
