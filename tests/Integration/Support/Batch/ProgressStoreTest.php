<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Support\Batch;

use MediaOnAutopilot\Support\Batch\ProgressState;
use MediaOnAutopilot\Support\Batch\ProgressStore;
use WP_UnitTestCase;

final class ProgressStoreTest extends WP_UnitTestCase {

	public function test_unknown_slug_returns_idle(): void {
		$state = ( new ProgressStore() )->get( 'nope' );
		$this->assertSame( ProgressState::STATUS_IDLE, $state->status );
	}

	public function test_begin_then_record_tracks_progress(): void {
		$store = new ProgressStore();
		$store->begin( 'job', 'Job', 4 );
		$store->record( 'job', ProgressState::OUTCOME_WRITTEN );
		$store->record( 'job', ProgressState::OUTCOME_WRITTEN );

		$state = $store->get( 'job' );
		$this->assertSame( ProgressState::STATUS_RUNNING, $state->status );
		$this->assertSame( 4, $state->total );
		$this->assertSame( 2, $state->completed );
	}

	public function test_record_tracks_outcomes(): void {
		$store = new ProgressStore();
		$store->begin( 'job', 'Job', 3 );
		$store->record( 'job', ProgressState::OUTCOME_WRITTEN );
		$store->record( 'job', ProgressState::OUTCOME_SKIPPED );
		$store->record( 'job', ProgressState::OUTCOME_FAILED );

		$state = $store->get( 'job' );
		$this->assertSame( 3, $state->completed );
		$this->assertSame( 1, $state->skipped );
		$this->assertSame( 1, $state->failed );
	}

	public function test_finish_marks_done(): void {
		$store = new ProgressStore();
		$store->begin( 'job', 'Job', 1 );
		$store->finish( 'job' );
		$this->assertSame( ProgressState::STATUS_DONE, $store->get( 'job' )->status );
	}

	public function test_finish_writes_last_run_snapshot(): void {
		$store = new ProgressStore();
		$store->begin( 'job', 'Job', 3 );
		$store->record( 'job', ProgressState::OUTCOME_WRITTEN );
		$store->record( 'job', ProgressState::OUTCOME_SKIPPED );
		$store->record( 'job', ProgressState::OUTCOME_FAILED );
		$store->finish( 'job' );

		$snapshot = $store->last_run( 'job' );
		$this->assertIsArray( $snapshot );
		$this->assertArrayHasKey( 'finishedAt', $snapshot );
		$this->assertArrayHasKey( 'total', $snapshot );
		$this->assertArrayHasKey( 'succeeded', $snapshot );
		$this->assertArrayHasKey( 'skipped', $snapshot );
		$this->assertArrayHasKey( 'failed', $snapshot );
		$this->assertSame( 3, $snapshot['total'] );
		$this->assertSame( 1, $snapshot['succeeded'] );
		$this->assertSame( 1, $snapshot['skipped'] );
		$this->assertSame( 1, $snapshot['failed'] );
	}

	public function test_last_run_null_before_any_run(): void {
		$store = new ProgressStore();
		$this->assertNull( $store->last_run( 'nope' ) );
	}

	public function test_cancelling_then_reset(): void {
		$store = new ProgressStore();
		$store->begin( 'job', 'Job', 3 );
		$store->cancelling( 'job' );
		$this->assertSame( ProgressState::STATUS_CANCELLING, $store->get( 'job' )->status );
		$store->reset( 'job' );
		$this->assertSame( ProgressState::STATUS_IDLE, $store->get( 'job' )->status );
	}

	public function test_option_is_not_autoloaded(): void {
		$store = new ProgressStore();
		$store->begin( 'job', 'Job', 1 );
		$autoload = get_option( 'moap_batch_job' );
		$this->assertNotFalse( $autoload );
		wp_cache_flush();
		$row = $GLOBALS['wpdb']->get_var(
			$GLOBALS['wpdb']->prepare( "SELECT autoload FROM {$GLOBALS['wpdb']->options} WHERE option_name = %s", 'moap_batch_job' )
		);
		$this->assertContains( $row, array( 'no', 'off' ) );
	}
}
