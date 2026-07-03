<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn\Bunny;

use MediaOnAutopilot\Features\Cdn\WidthLadder;
use PHPUnit\Framework\TestCase;

final class UrlRewriterLadderTest extends TestCase {

	public function test_ladder_filters_and_dedupes_when_cap_is_a_breakpoint(): void {
		$this->assertSame( array( 320, 480, 640, 768 ), WidthLadder::ladder_widths( 768, 2560 ) );
	}

	public function test_ladder_appends_original_width_when_not_a_breakpoint(): void {
		$this->assertSame( array( 320, 480, 640, 768, 800 ), WidthLadder::ladder_widths( 800, 900 ) );
	}

	public function test_ladder_capped_by_max_width(): void {
		$this->assertSame( array( 320, 480, 640, 768, 1024, 1366, 1600, 1920, 2560 ), WidthLadder::ladder_widths( 6000, 2560 ) );
	}
}
