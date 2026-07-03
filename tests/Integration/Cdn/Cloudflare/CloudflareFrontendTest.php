<?php
/**
 * Integration tests for ImageFrontend with the Cloudflare provider.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\ImageFrontend;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareProvider;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImageIdStore;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use WP_UnitTestCase;

/**
 * Covers ImageFrontend behaviour with a CloudflareProvider.
 */
final class CloudflareFrontendTest extends WP_UnitTestCase {

	/**
	 * Builds an ImageFrontend wired to a Cloudflare provider.
	 *
	 * @return ImageFrontend
	 */
	private function frontend(): ImageFrontend {
		return new ImageFrontend(
			new CloudflareProvider( new CloudflareConfig( 'a', 't', 'h7', 85, 'auto' ), new ImageIdStore() ),
			new FocalPointMeta()
		);
	}

	/**
	 * Downsize returns an imagedelivery.net URL for an offloaded attachment.
	 *
	 * @return void
	 */
	public function test_downsize_uses_imagedelivery_when_offloaded(): void {
		$id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		( new ImageIdStore() )->set( $id, 'img-front' );

		$result = $this->frontend()->downsize( false, $id, 'medium' );
		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'https://imagedelivery.net/h7/img-front/', $result[0] );
	}

	/**
	 * Downsize falls back to the local URL when the attachment is not offloaded.
	 *
	 * @return void
	 */
	public function test_downsize_falls_back_to_local_when_not_offloaded(): void {
		$id     = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$result = $this->frontend()->downsize( false, $id, 'medium' );

		// Provider returns the original URL; engine still returns a sizing array,
		// but the URL is the local uploads URL, not imagedelivery.net.
		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( 'imagedelivery.net', $result[0] );
	}

	/**
	 * Add_attachment_image_srcset synthesizes an imagedelivery.net ladder when
	 * the attachment has no local sub-sizes.
	 *
	 * @return void
	 */
	public function test_builds_imagedelivery_srcset_for_offloaded_image_without_subsizes(): void {
		$id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		( new ImageIdStore() )->set( $id, 'img-srcset' );
		$meta          = wp_get_attachment_metadata( $id );
		$meta['sizes'] = array();
		wp_update_attachment_metadata( $id, $meta );

		$attr = array(
			'width'  => (int) $meta['width'],
			'height' => (int) $meta['height'],
		);
		$out  = $this->frontend()->add_attachment_image_srcset( $attr, get_post( $id ), 'full' );

		$this->assertArrayHasKey( 'srcset', $out );
		$this->assertStringContainsString( 'https://imagedelivery.net/h7/img-srcset/', $out['srcset'] );
		$this->assertStringContainsString( '320w', $out['srcset'] );
	}
}
