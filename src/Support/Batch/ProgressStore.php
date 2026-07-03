<?php
/**
 * Option-backed persistence for batch job progress.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Support\Batch;

defined( 'ABSPATH' ) || exit;

/**
 * Reads/writes one non-autoloaded option per job (moap_batch_<slug>).
 */
final class ProgressStore {

	private const PREFIX = 'moap_batch_';

	/**
	 * Current state for a job, idle if unknown.
	 *
	 * @param string $slug Job slug.
	 * @return ProgressState
	 */
	public function get( string $slug ): ProgressState {
		$raw = get_option( self::PREFIX . $slug );
		return is_array( $raw ) ? ProgressState::from_array( $raw ) : ProgressState::idle();
	}

	/**
	 * Begin a run: reset counters and mark running.
	 *
	 * @param string $slug  Job slug.
	 * @param string $label Job label.
	 * @param int    $total Items enqueued.
	 * @return void
	 */
	public function begin( string $slug, string $label, int $total ): void {
		$now = time();
		$this->put( $slug, new ProgressState( $total, 0, 0, 0, ProgressState::STATUS_RUNNING, $label, $now, $now ) );
	}

	/**
	 * Record the outcome of one processed item.
	 *
	 * @param string $slug    Job slug.
	 * @param string $outcome One of ProgressState::OUTCOME_* constants.
	 * @return void
	 */
	public function record( string $slug, string $outcome ): void {
		$state = $this->get( $slug );
		$this->put(
			$slug,
			new ProgressState(
				$state->total,
				$state->completed + 1,
				$state->skipped + ( ProgressState::OUTCOME_SKIPPED === $outcome ? 1 : 0 ),
				$state->failed + ( ProgressState::OUTCOME_FAILED === $outcome ? 1 : 0 ),
				$state->status,
				$state->label,
				$state->started_at,
				time()
			)
		);
	}

	/**
	 * Mark the run complete and persist the last-run snapshot.
	 *
	 * @param string $slug Job slug.
	 * @return void
	 */
	public function finish( string $slug ): void {
		$this->transition( $slug, ProgressState::STATUS_DONE );
		$state = $this->get( $slug );
		update_option(
			self::PREFIX . $slug . '_last',
			array(
				'finishedAt' => time(),
				'total'      => $state->total,
				'succeeded'  => $state->succeeded(),
				'skipped'    => $state->skipped,
				'failed'     => $state->failed,
			),
			false
		);
	}

	/**
	 * Return the last-run snapshot, or null if no run has finished.
	 *
	 * @param string $slug Job slug.
	 * @return array<string, mixed>|null
	 */
	public function last_run( string $slug ): ?array {
		$raw = get_option( self::PREFIX . $slug . '_last' );
		return is_array( $raw ) ? $raw : null;
	}

	/**
	 * Mark a cancel as in progress.
	 *
	 * @param string $slug Job slug.
	 * @return void
	 */
	public function cancelling( string $slug ): void {
		$this->transition( $slug, ProgressState::STATUS_CANCELLING );
	}

	/**
	 * Reset to idle.
	 *
	 * @param string $slug Job slug.
	 * @return void
	 */
	public function reset( string $slug ): void {
		$this->transition( $slug, ProgressState::STATUS_IDLE );
	}

	/**
	 * Apply a status change while keeping counters.
	 *
	 * @param string $slug   Job slug.
	 * @param string $status New status.
	 * @return void
	 */
	private function transition( string $slug, string $status ): void {
		$state = $this->get( $slug );
		$this->put(
			$slug,
			new ProgressState( $state->total, $state->completed, $state->skipped, $state->failed, $status, $state->label, $state->started_at, time() )
		);
	}

	/**
	 * Persist a state to its option.
	 *
	 * @param string        $slug  Job slug.
	 * @param ProgressState $state State to store.
	 * @return void
	 */
	private function put( string $slug, ProgressState $state ): void {
		update_option( self::PREFIX . $slug, $state->to_array(), false );
	}
}
