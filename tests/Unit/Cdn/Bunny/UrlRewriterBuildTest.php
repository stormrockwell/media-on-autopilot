<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn\Bunny;

use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\UrlRewriter;
use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use PHPUnit\Framework\TestCase;

final class UrlRewriterBuildTest extends TestCase {

	private function config( string $format ): BunnyConfig {
		return new BunnyConfig( 'x.b-cdn.net', 80, $format );
	}

	public function test_auto_emits_width_and_quality_no_format(): void {
		$params = UrlRewriter::params( $this->config( 'auto' ), 600, 0, false, null );
		$this->assertSame( 600, $params['width'] );
		$this->assertSame( 80, $params['quality'] );
		$this->assertArrayNotHasKey( 'format', $params );
		$this->assertArrayNotHasKey( 'crop', $params );
	}

	public function test_webp_emits_format(): void {
		$params = UrlRewriter::params( $this->config( 'webp' ), 600, 0, false, null );
		$this->assertSame( 'webp', $params['format'] );
		$this->assertSame( 80, $params['quality'] );
	}

	public function test_off_emits_only_sizing(): void {
		$params = UrlRewriter::params( $this->config( 'off' ), 600, 0, false, null );
		$this->assertSame( array( 'width' => 600 ), $params );
	}

	public function test_crop_emits_source_region_focus_crop_then_resize(): void {
		// 1200x900 original, square crop output: largest 1:1 region is 900x900,
		// then resize to width 300.
		$params = UrlRewriter::params( $this->config( 'auto' ), 300, 300, true, new FocalPoint( 0.816875, 0.4625 ), 1200, 900 );
		$this->assertSame( '900,900,0.8169,0.4625', $params['focus_crop'] );
		$this->assertSame( 300, $params['width'] );
		$this->assertSame( 80, $params['quality'] );
		$this->assertArrayNotHasKey( 'crop', $params );
		$this->assertArrayNotHasKey( 'crop_gravity', $params );
	}

	public function test_crop_without_focal_uses_center_crop_not_focus_crop(): void {
		$params = UrlRewriter::params( $this->config( 'off' ), 300, 300, true, FocalPoint::center(), 1200, 900 );
		$this->assertSame( '900,900', $params['crop'] );
		$this->assertSame( 300, $params['width'] );
		$this->assertArrayNotHasKey( 'focus_crop', $params );
	}

	public function test_crop_region_matches_target_aspect_within_original(): void {
		$this->assertSame( array( 900, 900 ), UrlRewriter::crop_region( 1200, 900, 300, 300 ) );   // square from landscape
		$this->assertSame( array( 1707, 1707 ), UrlRewriter::crop_region( 2560, 1707, 600, 600 ) ); // square from 3:2
		$this->assertSame( array( 1200, 675 ), UrlRewriter::crop_region( 1200, 900, 16, 9 ) );      // 16:9 from 4:3
		$this->assertSame( array( 900, 900 ), UrlRewriter::crop_region( 900, 1200, 300, 300 ) );    // square from portrait
	}

	public function test_build_keeps_literal_commas(): void {
		$url = UrlRewriter::build(
			'https://example.test/a/pic.jpg',
			'x.b-cdn.net',
			array( 'focus_crop' => '300,300,0.82,0.46', 'quality' => 80 )
		);
		$this->assertStringContainsString( 'focus_crop=300,300,0.82,0.46', $url );
		$this->assertStringNotContainsString( '%2C', $url );
	}

	public function test_build_swaps_host_and_appends_query(): void {
		$url = UrlRewriter::build(
			'https://example.test/wp-content/uploads/2026/06/pic.jpg?old=1',
			'x.b-cdn.net',
			array( 'width' => 600, 'quality' => 80 )
		);
		$this->assertStringStartsWith( 'https://x.b-cdn.net/wp-content/uploads/2026/06/pic.jpg?', $url );
		$this->assertStringContainsString( 'width=600', $url );
		$this->assertStringContainsString( 'quality=80', $url );
		$this->assertStringNotContainsString( 'old=1', $url );
	}

	public function test_build_with_no_params_has_no_query(): void {
		$url = UrlRewriter::build( 'https://example.test/a/pic.jpg', 'x.b-cdn.net', array() );
		$this->assertSame( 'https://x.b-cdn.net/a/pic.jpg', $url );
	}
}
