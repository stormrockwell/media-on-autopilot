<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn;

use MediaOnAutopilot\Features\Cdn\ImageFrontend;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyProvider;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use WP_UnitTestCase;

final class ImageFrontendSrcsetTest extends WP_UnitTestCase {

	private function frontend(): ImageFrontend {
		return new ImageFrontend(
			new BunnyProvider( new BunnyConfig( 'x.b-cdn.net', 80, 'auto' ) ),
			new FocalPointMeta()
		);
	}

	public function test_builds_ladder_from_original_width(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $id, '/uploads/2026/06/canola.jpg' );
		$meta    = array(
			'width'  => 800,
			'height' => 600,
			'file'   => '2026/06/canola.jpg',
			'sizes'  => array(),
		);
		$sources = $this->frontend()->synthesize_srcset( array(), array( 300, 225 ), '', $meta, $id );

		$this->assertSame( array( 320, 480, 640, 768, 800 ), array_keys( $sources ) );
		$this->assertStringContainsString( '//x.b-cdn.net/', $sources[320]['url'] );
		$this->assertStringContainsString( 'width=320', $sources[320]['url'] );
		$this->assertSame( 'w', $sources[320]['descriptor'] );
		$this->assertSame( 320, $sources[320]['value'] );
	}

	public function test_crop_box_with_focal_emits_focus_crop_capped_at_region(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $id, '/uploads/2026/06/canola.jpg' );
		( new FocalPointMeta() )->set( $id, new \MediaOnAutopilot\Features\FocalPoint\FocalPoint( 0.5, 0.6 ) );
		$meta = array(
			'width'  => 800,
			'height' => 600,
			'file'   => '2026/06/canola.jpg',
			'sizes'  => array(),
		);

		// 1:1 box on a 4:3 source: crop region is 600x600, so candidates cap at 600.
		$sources = $this->frontend()->synthesize_srcset( array(), array( 300, 300 ), '', $meta, $id );

		$this->assertSame( array( 320, 480, 600 ), array_keys( $sources ) );
		$this->assertStringContainsString( 'focus_crop=600,600,0.5,0.6', $sources[320]['url'] );
		$this->assertStringContainsString( 'width=320', $sources[320]['url'] );
	}

	public function test_returns_sources_unchanged_when_inactive(): void {
		set_current_screen( 'upload' );
		$sources = $this->frontend()->synthesize_srcset( array( 'x' => 1 ), array( 300, 225 ), '', array( 'width' => 800 ), 0 );
		set_current_screen( 'front' );
		$this->assertSame( array( 'x' => 1 ), $sources );
	}

	public function test_injects_srcset_into_content_tag_without_subsizes(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $id, '/uploads/2026/06/canola.jpg' );
		// Empty 'sizes' is the crux: no local sub-sizes, yet a ladder must appear.
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 1600,
				'height' => 1200,
				'file'   => '2026/06/canola.jpg',
				'sizes'  => array(),
			)
		);

		$tag = '<img src="https://example.test/wp-content/uploads/2026/06/canola.jpg" width="1600" height="1200" />';
		$out = $this->frontend()->rewrite_content_image( $tag, 'the_content', $id );

		$this->assertStringContainsString( 'srcset="', $out );
		$this->assertStringContainsString( '320w', $out );
		$this->assertStringContainsString( '1600w', $out );
		$this->assertStringContainsString( 'sizes="', $out );
		$this->assertStringContainsString( '//x.b-cdn.net/', $out );
	}

	public function test_does_not_add_a_second_srcset_when_one_exists(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $id, '/uploads/2026/06/canola.jpg' );
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 1600,
				'height' => 1200,
				'file'   => '2026/06/canola.jpg',
				'sizes'  => array(),
			)
		);

		$tag = '<img src="https://example.test/wp-content/uploads/2026/06/canola.jpg" width="1600" height="1200" srcset="https://example.test/a.jpg 100w" sizes="50vw" />';
		$out = $this->frontend()->rewrite_content_image( $tag, 'the_content', $id );

		$this->assertSame( 1, substr_count( $out, 'srcset=' ) );
		$this->assertSame( 1, substr_count( $out, 'sizes=' ) );
	}

	public function test_adds_srcset_to_attachment_image_attributes_without_subsizes(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $id, '/uploads/2026/06/canola.jpg' );
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 1600,
				'height' => 1200,
				'file'   => '2026/06/canola.jpg',
				'sizes'  => array(),
			)
		);

		$attr = array(
			'width'  => 1600,
			'height' => 1200,
		);
		$out  = $this->frontend()->add_attachment_image_srcset( $attr, get_post( $id ), 'full' );

		$this->assertArrayHasKey( 'srcset', $out );
		$this->assertStringContainsString( '320w', $out['srcset'] );
		$this->assertStringContainsString( '1600w', $out['srcset'] );
		$this->assertArrayHasKey( 'sizes', $out );
	}

	public function test_attachment_attributes_keep_existing_srcset(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $id, '/uploads/2026/06/canola.jpg' );
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 1600,
				'height' => 1200,
				'file'   => '2026/06/canola.jpg',
				'sizes'  => array(),
			)
		);

		$attr = array(
			'width'  => 1600,
			'height' => 1200,
			'srcset' => 'https://example.test/a.jpg 100w',
		);
		$out  = $this->frontend()->add_attachment_image_srcset( $attr, get_post( $id ), 'full' );

		$this->assertSame( 'https://example.test/a.jpg 100w', $out['srcset'] );
	}
}
