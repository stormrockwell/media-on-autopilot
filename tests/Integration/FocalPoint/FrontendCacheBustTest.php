<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use MediaOnAutopilot\Features\FocalPoint\Frontend;
use WP_UnitTestCase;

final class FrontendCacheBustTest extends WP_UnitTestCase {

	public function test_appends_moap_param_to_src(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();
		$meta->set( $attachment_id, new FocalPoint( 0.52, 0.46 ) );

		$frontend = new Frontend( $meta );
		$image    = $frontend->add_cache_bust_to_src(
			array( 'http://example.test/wp-content/uploads/x.jpg', 300, 200, true ),
			$attachment_id,
			'medium',
			false
		);

		$this->assertStringContainsString( 'moap=52,46', $image[0] );
	}

	public function test_centered_point_leaves_src_untouched(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();

		$frontend = new Frontend( $meta );
		$image    = $frontend->add_cache_bust_to_src(
			array( 'http://example.test/x.jpg', 300, 200, true ),
			$attachment_id,
			'medium',
			false
		);

		$this->assertSame( 'http://example.test/x.jpg', $image[0] );
	}

	public function test_appends_moap_param_to_srcset_entries(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();
		$meta->set( $attachment_id, new FocalPoint( 0.52, 0.46 ) );

		$frontend = new Frontend( $meta );
		$sources  = array(
			300 => array( 'url' => 'http://example.test/x-300.jpg', 'descriptor' => 'w', 'value' => 300 ),
		);
		$result = $frontend->add_cache_bust_to_srcset( $sources, array(), '', array(), $attachment_id );

		$this->assertStringContainsString( 'moap=52,46', $result[300]['url'] );
	}

	public function test_cache_bust_filter_can_suppress_param(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();
		$meta->set( $attachment_id, new FocalPoint( 0.52, 0.46 ) );

		add_filter( 'moap_focal_point_cache_bust', '__return_false' );
		$frontend = new Frontend( $meta );
		$image    = $frontend->add_cache_bust_to_src(
			array( 'http://example.test/x.jpg', 300, 200, true ),
			$attachment_id,
			'medium',
			false
		);
		remove_filter( 'moap_focal_point_cache_bust', '__return_false' );

		$this->assertSame( 'http://example.test/x.jpg', $image[0] );
	}
}
