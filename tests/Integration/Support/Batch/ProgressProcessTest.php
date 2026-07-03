<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Support\Batch;

use MediaOnAutopilot\Support\Batch\ProgressProcess;
use MediaOnAutopilot\Support\Batch\ProgressState;
use MediaOnAutopilot\Support\Batch\ProgressStore;
use WP_UnitTestCase;

final class ProgressProcessTest extends WP_UnitTestCase {

	private function makeProcess( ProgressStore $store, array &$seen ): ProgressProcess {
		return new class( $store, $seen ) extends ProgressProcess {
			protected $action = 'test_progress_job';
			public function __construct( ProgressStore $store, private array &$seen ) {
				parent::__construct( $store );
			}
			protected function handle_item( array $item ): string {
				$this->seen[] = (int) $item['id'];
				return ProgressState::OUTCOME_WRITTEN;
			}
			public function slug(): string {
				return 'test_progress_job';
			}
			public function label(): string {
				return 'Test Job';
			}
			// Expose protected task() for the test.
			public function runTask( array $item ): void {
				$this->task( $item );
			}
		};
	}

	public function test_start_sets_total_and_running(): void {
		$store = new ProgressStore();
		$seen  = array();
		$proc  = $this->makeProcess( $store, $seen );

		$ok = $proc->start( array( array( 'id' => 1 ), array( 'id' => 2 ), array( 'id' => 3 ) ) );

		$this->assertTrue( $ok );
		$state = $store->get( 'test_progress_job' );
		$this->assertSame( ProgressState::STATUS_RUNNING, $state->status );
		$this->assertSame( 3, $state->total );
	}

	public function test_start_refuses_when_already_running(): void {
		$store = new ProgressStore();
		$seen  = array();
		$proc  = $this->makeProcess( $store, $seen );
		$proc->start( array( array( 'id' => 1 ) ) );

		$this->assertFalse( $proc->start( array( array( 'id' => 9 ) ) ) );
	}

	public function test_task_runs_handler_and_increments(): void {
		$store = new ProgressStore();
		$seen  = array();
		$proc  = $this->makeProcess( $store, $seen );
		$proc->start( array( array( 'id' => 7 ), array( 'id' => 8 ) ) );

		$proc->runTask( array( 'id' => 7 ) );

		$this->assertSame( array( 7 ), $seen );
		$this->assertSame( 1, $store->get( 'test_progress_job' )->completed );
	}

	public function test_empty_start_marks_done_immediately(): void {
		$store = new ProgressStore();
		$seen  = array();
		$proc  = $this->makeProcess( $store, $seen );

		$this->assertTrue( $proc->start( array() ) );
		$this->assertSame( ProgressState::STATUS_DONE, $store->get( 'test_progress_job' )->status );
	}

	public function test_stop_marks_cancelling(): void {
		$store = new ProgressStore();
		$seen  = array();
		$proc  = $this->makeProcess( $store, $seen );
		$proc->start( array( array( 'id' => 1 ) ) );

		$proc->stop();

		$this->assertSame( ProgressState::STATUS_CANCELLING, $store->get( 'test_progress_job' )->status );
	}

	public function test_stop_on_idle_is_noop(): void {
		$store = new ProgressStore();
		$seen  = array();
		$proc  = $this->makeProcess( $store, $seen );

		$proc->stop();

		$this->assertSame( ProgressState::STATUS_IDLE, $store->get( 'test_progress_job' )->status );
	}

	public function test_failed_outcome_increments_failed(): void {
		$store    = new ProgressStore();
		$slug     = 'test_progress_fail';
		$failProc = new class( $store ) extends ProgressProcess {
			protected $action = 'test_progress_fail';
			public function __construct( ProgressStore $store ) {
				parent::__construct( $store );
			}
			protected function handle_item( array $item ): string {
				return ProgressState::OUTCOME_FAILED;
			}
			public function slug(): string {
				return 'test_progress_fail';
			}
			public function label(): string {
				return 'Fail Job';
			}
			public function runTask( array $item ): void {
				$this->task( $item );
			}
		};

		$failProc->start( array( array( 'id' => 1 ) ) );
		$failProc->runTask( array( 'id' => 1 ) );

		$this->assertSame( 1, $store->get( $slug )->failed );
	}
}
