<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn;

use MediaOnAutopilot\Features\Cdn\ImageFrontend;
use MediaOnAutopilot\Features\Cdn\ImageTransform;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyProvider;
use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use WP_UnitTestCase;

final class ImageFrontendDownsizeTest extends WP_UnitTestCase {

	private function attachment(): int {
		$id = self::factory()->attachment->create(
			array(
				'post_mime_type' => 'image/jpeg',
				'file'           => DIR_TESTDATA . '/images/canola.jpg',
			)
		);
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 4000,
				'height' => 3000,
				'file'   => '2026/06/canola.jpg',
				'sizes'  => array(),
			)
		);
		update_attached_file( $id, '/uploads/2026/06/canola.jpg' );
		return $id;
	}

	private function frontend(): ImageFrontend {
		return new ImageFrontend(
			new BunnyProvider( new BunnyConfig( 'x.b-cdn.net', 80, 'auto' ) ),
			new FocalPointMeta()
		);
	}

	public function test_soft_size_returns_bunny_url_with_capped_width(): void {
		$id = $this->attachment();
		// 'medium' is 300x300 soft by default; constrained to 300x225 for a 4:3 image.
		$result = $this->frontend()->downsize( false, $id, 'medium' );
		$this->assertIsArray( $result );
		$this->assertStringContainsString( '//x.b-cdn.net/', $result[0] );
		$this->assertStringContainsString( 'width=300', $result[0] ); // display width; retina via srcset
		$this->assertSame( 300, $result[1] ); // layout width unchanged
		$this->assertTrue( $result[3] );       // is_intermediate
	}

	public function test_array_size_is_soft_no_crop_params(): void {
		$id     = $this->attachment();
		$result = $this->frontend()->downsize( false, $id, array( 500, 500 ) );
		$this->assertStringContainsString( 'width=500', $result[0] );
		$this->assertStringNotContainsString( 'crop=', $result[0] );
	}

	public function test_full_size_has_no_width_or_crop(): void {
		$id     = $this->attachment();
		$result = $this->frontend()->downsize( false, $id, 'full' );
		$this->assertStringContainsString( '//x.b-cdn.net/', $result[0] );
		$this->assertFalse( $result[3] );
	}

	public function test_inactive_in_admin_returns_false(): void {
		$id = $this->attachment();
		set_current_screen( 'upload' ); // is_admin() true
		$result = $this->frontend()->downsize( false, $id, 'medium' );
		set_current_screen( 'front' );
		$this->assertFalse( $result );
	}

	public function test_crop_size_includes_focus_crop_from_focal(): void {
		add_image_size( 'moap_test_crop', 300, 300, true );

		$id = $this->attachment();
		( new FocalPointMeta() )->set( $id, new FocalPoint( 0.25, 0.75 ) );

		$result = $this->frontend()->downsize( false, $id, 'moap_test_crop' );

		$this->assertIsArray( $result );
		// 4000x3000 original, 1:1 crop: the source region is the largest square
		// (3000x3000) anchored at the focal point, then resized to width 300.
		$this->assertStringContainsString( 'focus_crop=3000,3000,0.25,0.75', $result[0] );
		$this->assertStringContainsString( 'width=300', $result[0] );
		$this->assertStringNotContainsString( '%2C', $result[0] );
		$this->assertStringNotContainsString( 'crop_gravity', $result[0] );

		remove_image_size( 'moap_test_crop' );
	}

	public function test_moap_cdn_image_transform_filter_applied(): void {
		$id = $this->attachment();

		add_filter(
			'moap_cdn_image_transform',
			static function ( ImageTransform $transform ): ImageTransform {
				// Override width to a distinct sentinel to verify the filter fires.
				return new ImageTransform(
					$transform->attachment_id,
					999,
					$transform->height,
					$transform->crop,
					$transform->focal,
					$transform->orig_width,
					$transform->orig_height
				);
			}
		);

		$result = $this->frontend()->downsize( false, $id, 'medium' );

		remove_all_filters( 'moap_cdn_image_transform' );

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'width=999', $result[0] );
	}
}
