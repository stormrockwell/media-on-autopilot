<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\ImageTransform;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareProvider;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImageIdStore;
use WP_UnitTestCase;

final class CloudflareProviderTest extends WP_UnitTestCase {

	private function provider(): CloudflareProvider {
		return new CloudflareProvider(
			new CloudflareConfig( 'acct', 'token', 'hash9', 85, 'auto' ),
			new ImageIdStore()
		);
	}

	public function test_builds_imagedelivery_url_when_offloaded(): void {
		$id = self::factory()->attachment->create();
		( new ImageIdStore() )->set( $id, 'img-abc' );

		$url = $this->provider()->build_url( 'https://site.test/wp-content/uploads/a.jpg', ImageTransform::center( $id, 600 ) );
		$this->assertSame( 'https://imagedelivery.net/hash9/img-abc/w=600,quality=85,format=auto', $url );
	}

	public function test_falls_back_to_original_when_not_offloaded(): void {
		$id  = self::factory()->attachment->create();
		$url = $this->provider()->build_url( 'https://site.test/wp-content/uploads/a.jpg', ImageTransform::center( $id, 600 ) );
		$this->assertSame( 'https://site.test/wp-content/uploads/a.jpg', $url );
	}

	public function test_unknown_attachment_falls_back(): void {
		$url = $this->provider()->build_url( 'https://site.test/a.jpg', ImageTransform::center( 0, 600 ) );
		$this->assertSame( 'https://site.test/a.jpg', $url );
	}

	public function test_detects_own_urls_and_encodes_focal(): void {
		$p = $this->provider();
		$this->assertTrue( $p->encodes_focal_in_url() );
		$this->assertTrue( $p->is_already_rewritten( 'https://imagedelivery.net/hash9/x/w=10' ) );
		$this->assertFalse( $p->is_already_rewritten( 'https://site.test/a.jpg' ) );
	}

	public function test_delivery_base_is_filterable(): void {
		$id = self::factory()->attachment->create();
		( new ImageIdStore() )->set( $id, 'img-abc' );

		$captured = '';
		$filter   = static function ( $base, $hash ) use ( &$captured ) {
			$captured = $hash;
			return 'https://cdn.example.com/custom';
		};
		add_filter( 'moap_cloudflare_delivery_base', $filter, 10, 2 );
		try {
			$url = $this->provider()->build_url( 'https://site.test/a.jpg', ImageTransform::center( $id, 600 ) );
		} finally {
			remove_filter( 'moap_cloudflare_delivery_base', $filter, 10 );
		}

		$this->assertSame( 'https://cdn.example.com/custom/img-abc/w=600,quality=85,format=auto', $url );
		$this->assertSame( 'hash9', $captured, 'The account hash should be passed to the filter.' );
	}
}
