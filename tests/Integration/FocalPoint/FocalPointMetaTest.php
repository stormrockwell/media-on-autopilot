<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use WP_UnitTestCase;

final class FocalPointMetaTest extends WP_UnitTestCase {

	public function test_set_and_get_round_trip(): void {
		$meta          = new FocalPointMeta();
		$attachment_id = self::factory()->attachment->create();

		$this->assertFalse( $meta->has( $attachment_id ) );
		$this->assertTrue( $meta->get( $attachment_id )->is_center() );

		$meta->set( $attachment_id, new FocalPoint( 0.2, 0.8 ) );

		$this->assertTrue( $meta->has( $attachment_id ) );
		$stored = $meta->get( $attachment_id );
		$this->assertSame( 0.2, $stored->x );
		$this->assertSame( 0.8, $stored->y );
	}

	public function test_meta_is_registered_for_rest(): void {
		( new FocalPointMeta() )->register();
		$registered = get_registered_meta_keys( 'post', 'attachment' );
		$this->assertArrayHasKey( FocalPointMeta::META_KEY, $registered );
		$this->assertTrue( $registered[ FocalPointMeta::META_KEY ]['show_in_rest'] !== false );
	}

	public function test_auth_callback_is_scoped_to_editing_the_attachment(): void {
		( new FocalPointMeta() )->register();
		$registered = get_registered_meta_keys( 'post', 'attachment' );
		$auth       = $registered[ FocalPointMeta::META_KEY ]['auth_callback'];

		$owner      = self::factory()->user->create( array( 'role' => 'author' ) );
		$other      = self::factory()->user->create( array( 'role' => 'author' ) );
		$attachment = self::factory()->attachment->create( array( 'post_author' => $owner ) );

		// An author has upload_files but must NOT be able to write the focal point
		// of another user's attachment (the old blanket upload_files check would).
		wp_set_current_user( $other );
		$this->assertFalse( (bool) call_user_func( $auth, true, FocalPointMeta::META_KEY, $attachment ) );

		// An administrator can edit any attachment.
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$this->assertTrue( (bool) call_user_func( $auth, true, FocalPointMeta::META_KEY, $attachment ) );
	}
}
