<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Support\Batch;

use MediaOnAutopilot\Support\Batch\ProgressState;
use PHPUnit\Framework\TestCase;

final class ProgressStateTest extends TestCase {

	public function test_idle_factory_has_zero_totals(): void {
		$state = ProgressState::idle( 'Cleanup' );
		$this->assertSame( ProgressState::STATUS_IDLE, $state->status );
		$this->assertSame( 0, $state->total );
		$this->assertSame( 'Cleanup', $state->label );
		$this->assertFalse( $state->is_active() );
	}

	public function test_remaining_and_percent(): void {
		$state = new ProgressState( 10, 3, 0, 0, ProgressState::STATUS_RUNNING, 'X', 100, 200 );
		$this->assertSame( 7, $state->remaining() );
		$this->assertSame( 30, $state->percent() );
		$this->assertTrue( $state->is_active() );
	}

	public function test_percent_is_zero_when_total_is_zero(): void {
		$state = ProgressState::idle();
		$this->assertSame( 0, $state->percent() );
	}

	public function test_running_and_cancelling_are_active_done_is_not(): void {
		$this->assertTrue( ( new ProgressState( 1, 0, 0, 0, ProgressState::STATUS_CANCELLING, '', 1, 1 ) )->is_active() );
		$this->assertFalse( ( new ProgressState( 1, 1, 0, 0, ProgressState::STATUS_DONE, '', 1, 1 ) )->is_active() );
	}

	public function test_array_round_trip(): void {
		$state = new ProgressState( 5, 2, 0, 0, ProgressState::STATUS_RUNNING, 'Tag', 11, 22 );
		$this->assertEquals( $state, ProgressState::from_array( $state->to_array() ) );
	}

	/**
	 * Skipped and failed counts round-trip through toArray/fromArray and succeeded() is correct.
	 */
	public function test_skipped_failed_round_trip(): void {
		$state = new ProgressState( 10, 7, 2, 1, ProgressState::STATUS_DONE, 'Bulk', 100, 200 );

		$this->assertSame( 2, $state->skipped );
		$this->assertSame( 1, $state->failed );
		$this->assertSame( 4, $state->succeeded() );

		$restored = ProgressState::from_array( $state->to_array() );
		$this->assertEquals( $state, $restored );
		$this->assertSame( 2, $restored->skipped );
		$this->assertSame( 1, $restored->failed );
		$this->assertSame( 4, $restored->succeeded() );
	}
}
