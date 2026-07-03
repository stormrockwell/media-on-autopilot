<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn\Bunny;

use MediaOnAutopilot\Features\Cdn\ImageTransform;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyProvider;
use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use PHPUnit\Framework\TestCase;

final class BunnyProviderTest extends TestCase {

	private function provider( string $format = 'auto' ): BunnyProvider {
		return new BunnyProvider( new BunnyConfig( 'x.b-cdn.net', 80, $format ) );
	}

	public function test_build_url_swaps_host_and_appends_params(): void {
		$t   = ImageTransform::center( 1, 600 );
		$url = $this->provider()->build_url( 'https://example.test/wp-content/uploads/a.jpg', $t );
		$this->assertStringStartsWith( 'https://x.b-cdn.net/wp-content/uploads/a.jpg?', $url );
		$this->assertStringContainsString( 'width=600', $url );
	}

	public function test_build_url_crop_uses_focus_crop(): void {
		$t   = new ImageTransform( 1, 300, 300, true, new FocalPoint( 0.8, 0.4 ), 1200, 900 );
		$url = $this->provider()->build_url( 'https://example.test/a.jpg', $t );
		$this->assertStringContainsString( 'focus_crop=900,900,0.8,0.4', $url );
	}

	public function test_encodes_focal_and_detects_own_urls(): void {
		$p = $this->provider();
		$this->assertTrue( $p->encodes_focal_in_url() );
		$this->assertTrue( $p->is_already_rewritten( 'https://x.b-cdn.net/a.jpg?width=10' ) );
		$this->assertFalse( $p->is_already_rewritten( 'https://example.test/a.jpg' ) );
	}
}
