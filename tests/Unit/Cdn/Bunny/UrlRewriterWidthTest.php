<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn\Bunny;

use MediaOnAutopilot\Features\Cdn\WidthLadder;
use PHPUnit\Framework\TestCase;

final class UrlRewriterWidthTest extends TestCase {

	public function test_returns_requested_width_within_bounds(): void {
		$this->assertSame( 500, WidthLadder::target_width( 500, 4000, 2560 ) );
	}

	public function test_caps_at_original_width(): void {
		$this->assertSame( 800, WidthLadder::target_width( 1200, 800, 2560 ) );
	}

	public function test_caps_at_max_width(): void {
		$this->assertSame( 2560, WidthLadder::target_width( 4000, 6000, 2560 ) );
	}

	public function test_never_below_one(): void {
		$this->assertSame( 1, WidthLadder::target_width( 0, 0, 2560 ) );
	}
}
