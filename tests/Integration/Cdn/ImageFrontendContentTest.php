<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn;

use MediaOnAutopilot\Features\Cdn\ImageFrontend;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyProvider;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use WP_UnitTestCase;

final class ImageFrontendContentTest extends WP_UnitTestCase {

	private function frontend(): ImageFrontend {
		return new ImageFrontend(
			new BunnyProvider( new BunnyConfig( 'x.b-cdn.net', 80, 'auto' ) ),
			new FocalPointMeta()
		);
	}

	public function test_rewrites_full_attachment_url_for_image(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $id, '/uploads/2026/06/canola.jpg' );
		$out = $this->frontend()->rewrite_attachment_url( 'https://example.test/wp-content/uploads/2026/06/canola.jpg', $id );
		$this->assertStringContainsString( '//x.b-cdn.net/', $out );
	}

	public function test_leaves_non_image_url_untouched(): void {
		$id  = self::factory()->attachment->create( array( 'post_mime_type' => 'application/pdf' ) );
		$out = $this->frontend()->rewrite_attachment_url( 'https://example.test/wp-content/uploads/2026/06/doc.pdf', $id );
		$this->assertSame( 'https://example.test/wp-content/uploads/2026/06/doc.pdf', $out );
	}

	public function test_rewrites_src_in_content_img_tag(): void {
		$id  = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		$tag = '<img src="https://example.test/wp-content/uploads/2026/06/canola.jpg" width="600" height="400" />';
		$out = $this->frontend()->rewrite_content_image( $tag, 'the_content', $id );
		$this->assertStringContainsString( '//x.b-cdn.net/', $out );
		$this->assertStringContainsString( 'width=600', $out ); // display width; retina handled by srcset
	}

	public function test_content_crop_with_focal_serves_original_with_focus_crop(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $id, '/uploads/2026/06/pic.jpg' );
		wp_update_attachment_metadata(
			$id,
			array( 'width' => 1200, 'height' => 900, 'file' => '2026/06/pic.jpg', 'sizes' => array() )
		);
		( new FocalPointMeta() )->set( $id, new \MediaOnAutopilot\Features\FocalPoint\FocalPoint( 0.5, 0.6 ) );

		// A 1:1 box on a 4:3 source is a crop; the embedded src is an intermediate.
		$tag = '<img src="https://example.test/wp-content/uploads/2026/06/pic-300x300.jpg" width="300" height="300" />';
		$out = $this->frontend()->rewrite_content_image( $tag, 'the_content', $id );

		// Serves the original file, not the embedded -300x300 intermediate.
		$this->assertStringContainsString( '/wp-content/uploads/2026/06/pic.jpg?', $out );
		$this->assertStringNotContainsString( 'pic-300x300', $out );
		// Largest 1:1 region of 1200x900 is 900x900, focal-anchored, resized to the 300 display width.
		$this->assertStringContainsString( 'focus_crop=900,900,0.5,0.6', $out );
		$this->assertStringContainsString( 'width=300', $out );
	}

	public function test_content_non_crop_box_stays_width_only(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $id, '/uploads/2026/06/pic.jpg' );
		wp_update_attachment_metadata(
			$id,
			array( 'width' => 1200, 'height' => 900, 'file' => '2026/06/pic.jpg', 'sizes' => array() )
		);
		( new FocalPointMeta() )->set( $id, new \MediaOnAutopilot\Features\FocalPoint\FocalPoint( 0.5, 0.6 ) );

		// A 4:3 box on a 4:3 source is not a crop.
		$tag = '<img src="https://example.test/wp-content/uploads/2026/06/pic-800x600.jpg" width="800" height="600" />';
		$out = $this->frontend()->rewrite_content_image( $tag, 'the_content', $id );

		$this->assertStringNotContainsString( 'focus_crop', $out );
		$this->assertStringNotContainsString( 'crop=', $out );
		$this->assertStringContainsString( 'width=800', $out ); // display width, no doubling.
	}

	public function test_content_img_untouched_when_inactive(): void {
		set_current_screen( 'upload' );
		$tag = '<img src="https://example.test/x.jpg" width="600" />';
		$out = $this->frontend()->rewrite_content_image( $tag, 'the_content', 5 );
		set_current_screen( 'front' );
		$this->assertSame( $tag, $out );
	}

	public function test_loose_url_without_attachment_strips_size_suffix(): void {
		$tag = '<img src="https://example.test/wp-content/uploads/2026/06/orphan-300x200.jpg" width="300" height="200" />';
		$out = $this->frontend()->rewrite_content_image( $tag, 'the_content', 0 );

		$this->assertStringContainsString( '//x.b-cdn.net/wp-content/uploads/2026/06/orphan.jpg?', $out );
		$this->assertStringNotContainsString( 'orphan-300x200', $out );
	}
}
