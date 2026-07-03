<?php
/**
 * Background process base that tracks progress and supports cancel.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Support\Batch;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps WP_Background_Process with persisted progress + cancel.
 */
abstract class ProgressProcess extends \WP_Background_Process {

	/**
	 * Queue identifier prefix (shared across MOAP jobs).
	 *
	 * @var string
	 */
	protected $prefix = 'moap';

	/**
	 * Constructs the process with its progress store.
	 *
	 * @param ProgressStore $store Progress persistence.
	 */
	public function __construct( protected ProgressStore $store ) {
		parent::__construct();
	}

	/**
	 * Handle one queued item.
	 *
	 * @param array<string, mixed> $item Queue item.
	 * @return string One of ProgressState::OUTCOME_*.
	 */
	abstract protected function handle_item( array $item ): string;

	/**
	 * Stable job slug (used for the progress option + REST route).
	 *
	 * @return string
	 */
	abstract public function slug(): string;

	/**
	 * Human-readable job label.
	 *
	 * @return string
	 */
	abstract public function label(): string;

	/**
	 * Enqueue items and start a run; refuses if one is already active.
	 *
	 * @param array<int, array<string, mixed>> $items Queue items.
	 * @return bool True if started, false if a run is already active.
	 */
	public function start( array $items ): bool {
		if ( $this->store->get( $this->slug() )->is_active() ) {
			return false;
		}

		$this->store->begin( $this->slug(), $this->label(), count( $items ) );

		if ( array() === $items ) {
			$this->store->finish( $this->slug() );
			return true;
		}

		foreach ( $items as $item ) {
			$this->push_to_queue( $item );
		}
		$this->save()->dispatch();

		return true;
	}

	/**
	 * Request cancellation of the active run.
	 *
	 * @return void
	 */
	public function stop(): void {
		if ( ! $this->store->get( $this->slug() )->is_active() ) {
			return;
		}
		$this->store->cancelling( $this->slug() );
		$this->cancel();
	}

	/**
	 * Process one item then advance the counter.
	 *
	 * @param mixed $item Queue item.
	 * @return bool False to remove the item from the queue.
	 */
	final protected function task( $item ): bool {
		$outcome = is_array( $item ) ? $this->handle_item( $item ) : ProgressState::OUTCOME_FAILED;
		$this->store->record( $this->slug(), $outcome );

		return false;
	}

	/**
	 * Mark the run done when the queue drains.
	 *
	 * @return void
	 */
	protected function complete() {
		parent::complete();
		$this->store->finish( $this->slug() );
	}

	/**
	 * Reset progress to idle when a cancel completes.
	 *
	 * @return void
	 */
	protected function cancelled() {
		parent::cancelled();
		$this->store->reset( $this->slug() );
	}
}
