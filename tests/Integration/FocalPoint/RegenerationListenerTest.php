<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use MediaOnAutopilot\Features\FocalPoint\RegenerationListener;
use MediaOnAutopilot\Features\FocalPoint\Regenerator;
use WP_UnitTestCase;

final class RegenerationListenerTest extends WP_UnitTestCase {

	public function test_setting_focal_point_triggers_regeneration(): void {
		$regenerator = new class() extends Regenerator {
			public array $ids = array();
			public function regenerate( int $attachment_id ): bool {
				$this->ids[] = $attachment_id;
				return true;
			}
		};

		( new RegenerationListener( $regenerator ) )->register();

		$attachment_id = self::factory()->attachment->create();
		( new FocalPointMeta() )->set( $attachment_id, new FocalPoint( 0.1, 0.1 ) );

		$this->assertSame( array( $attachment_id ), $regenerator->ids );
	}

	public function test_other_meta_keys_are_ignored(): void {
		$regenerator = new class() extends Regenerator {
			public array $ids = array();
			public function regenerate( int $attachment_id ): bool {
				$this->ids[] = $attachment_id;
				return true;
			}
		};

		( new RegenerationListener( $regenerator ) )->register();

		$attachment_id = self::factory()->attachment->create();
		update_post_meta( $attachment_id, '_unrelated', 'x' );

		$this->assertSame( array(), $regenerator->ids );
	}
}
