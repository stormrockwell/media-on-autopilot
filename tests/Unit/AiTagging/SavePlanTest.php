<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\AiTagging;

use MediaOnAutopilot\Features\AiTagging\SavePlan;
use PHPUnit\Framework\TestCase;

final class SavePlanTest extends TestCase {

	public function test_fill_missing_is_not_overwrite(): void {
		$plan = SavePlan::fill_missing( true, false, true );
		$this->assertTrue( $plan->alt );
		$this->assertFalse( $plan->tags );
		$this->assertTrue( $plan->focal );
		$this->assertFalse( $plan->overwrite );
	}

	public function test_overwrite_factory(): void {
		$plan = SavePlan::overwrite( true, true, true );
		$this->assertTrue( $plan->overwrite );
		$this->assertTrue( $plan->wants_anything() );
	}

	public function test_wants_anything_false_when_all_off(): void {
		$this->assertFalse( SavePlan::fill_missing( false, false, false )->wants_anything() );
	}

	public function test_item_round_trip(): void {
		$plan = SavePlan::overwrite( true, false, true );
		$item = $plan->to_item( 42 );
		$this->assertSame( 42, $item['id'] );
		$restored = SavePlan::from_item( $item );
		$this->assertEquals( $plan, $restored );
	}
}
