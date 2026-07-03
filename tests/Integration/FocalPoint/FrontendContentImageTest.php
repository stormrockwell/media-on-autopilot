<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use MediaOnAutopilot\Features\FocalPoint\Frontend;
use WP_UnitTestCase;

final class FrontendContentImageTest extends WP_UnitTestCase {

	public function test_adds_object_position_to_content_image(): void {
		$meta = new FocalPointMeta();
		$id   = self::factory()->attachment->create();
		$meta->set( $id, new FocalPoint( 0.25, 0.75 ) );

		$img    = sprintf( '<img src="x.jpg" class="wp-image-%d" alt="" />', $id );
		$result = ( new Frontend( $meta ) )->add_object_position_to_content_image( $img, 'the_content', $id );

		$this->assertStringContainsString( 'object-position: 25% 75%', $result );
	}

	public function test_adds_cache_bust_to_content_image_src(): void {
		$meta = new FocalPointMeta();
		$id   = self::factory()->attachment->create();
		$meta->set( $id, new FocalPoint( 0.25, 0.75 ) );

		$img    = sprintf( '<img src="http://example.test/x.jpg" class="wp-image-%d" alt="" />', $id );
		$result = ( new Frontend( $meta ) )->add_object_position_to_content_image( $img, 'the_content', $id );

		$this->assertStringContainsString( 'src="http://example.test/x.jpg?moap=25,75"', $result );
	}

	public function test_merges_with_existing_inline_style(): void {
		$meta = new FocalPointMeta();
		$id   = self::factory()->attachment->create();
		$meta->set( $id, new FocalPoint( 0.25, 0.75 ) );

		$img    = sprintf( '<img src="x.jpg" class="wp-image-%d" style="aspect-ratio:1;" alt="" />', $id );
		$result = ( new Frontend( $meta ) )->add_object_position_to_content_image( $img, 'the_content', $id );

		$this->assertStringContainsString( 'aspect-ratio:1', $result );
		$this->assertStringContainsString( 'object-position: 25% 75%', $result );
		$this->assertSame( 1, substr_count( $result, 'style=' ) );
	}

	public function test_is_idempotent_when_filtered_twice(): void {
		$meta = new FocalPointMeta();
		$id   = self::factory()->attachment->create();
		$meta->set( $id, new FocalPoint( 0.82, 0.46 ) );

		$frontend = new Frontend( $meta );
		$img      = sprintf( '<img src="x.jpg" class="wp-image-%d" alt="" />', $id );

		$once  = $frontend->add_object_position_to_content_image( $img, 'the_content', $id );
		$twice = $frontend->add_object_position_to_content_image( $once, 'the_content', $id );

		$this->assertSame( 1, substr_count( $twice, 'object-position' ) );
	}

	public function test_respects_an_author_defined_object_position(): void {
		$meta = new FocalPointMeta();
		$id   = self::factory()->attachment->create();
		$meta->set( $id, new FocalPoint( 0.82, 0.46 ) );

		$img    = '<img src="x.jpg" class="wp-image-' . $id . '" style="object-position: 10% 10%;" alt="" />';
		$result = ( new Frontend( $meta ) )->add_object_position_to_content_image( $img, 'the_content', $id );

		// The author's object-position is preserved (never overwritten), but the
		// src is still cache-busted since that is an orthogonal concern.
		$this->assertStringContainsString( 'object-position: 10% 10%', $result );
		$this->assertSame( 1, substr_count( $result, 'object-position' ) );
		$this->assertStringContainsString( 'src="x.jpg?moap=82,46"', $result );
	}

	public function test_leaves_centered_point_unchanged(): void {
		$meta   = new FocalPointMeta();
		$id     = self::factory()->attachment->create();
		$img    = sprintf( '<img src="x.jpg" class="wp-image-%d" alt="" />', $id );
		$result = ( new Frontend( $meta ) )->add_object_position_to_content_image( $img, 'the_content', $id );

		$this->assertSame( $img, $result );
	}

	public function test_leaves_non_attachment_image_unchanged(): void {
		$img    = '<img src="external.jpg" alt="" />';
		$result = ( new Frontend( new FocalPointMeta() ) )->add_object_position_to_content_image( $img, 'the_content', 0 );

		$this->assertSame( $img, $result );
	}
}
