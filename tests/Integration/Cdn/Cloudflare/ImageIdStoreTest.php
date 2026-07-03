<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImageIdStore;
use WP_UnitTestCase;

final class ImageIdStoreTest extends WP_UnitTestCase {

	public function test_set_get_clear_roundtrip(): void {
		$id    = self::factory()->attachment->create();
		$store = new ImageIdStore();

		$this->assertSame( '', $store->get( $id ) );
		$store->set( $id, 'cf-img-123' );
		$this->assertSame( 'cf-img-123', $store->get( $id ) );
		$store->clear( $id );
		$this->assertSame( '', $store->get( $id ) );
	}
}
