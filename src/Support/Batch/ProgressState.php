<?php
/**
 * Immutable progress snapshot for a background batch job.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Support\Batch;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable progress snapshot for one batch job.
 */
final class ProgressState {

	public const STATUS_IDLE       = 'idle';
	public const STATUS_RUNNING    = 'running';
	public const STATUS_CANCELLING = 'cancelling';
	public const STATUS_DONE       = 'done';

	public const OUTCOME_WRITTEN = 'written';
	public const OUTCOME_SKIPPED = 'skipped';
	public const OUTCOME_FAILED  = 'failed';

	/**
	 * Constructs an immutable progress snapshot.
	 *
	 * @param int    $total      Items enqueued for this run.
	 * @param int    $completed  Items processed so far.
	 * @param int    $skipped    Items skipped (already tagged, not eligible, etc.).
	 * @param int    $failed     Items that encountered an error.
	 * @param string $status     One of the STATUS_* constants.
	 * @param string $label      Human-readable job label.
	 * @param int    $started_at Unix timestamp of run start.
	 * @param int    $updated_at Unix timestamp of last update.
	 */
	public function __construct(
		public readonly int $total,
		public readonly int $completed,
		public readonly int $skipped,
		public readonly int $failed,
		public readonly string $status,
		public readonly string $label,
		public readonly int $started_at,
		public readonly int $updated_at
	) {}

	/**
	 * An idle (never-run or finished-and-reset) state.
	 *
	 * @param string $label Job label.
	 * @return self
	 */
	public static function idle( string $label = '' ): self {
		return new self( 0, 0, 0, 0, self::STATUS_IDLE, $label, 0, 0 );
	}

	/**
	 * Remaining item count, never negative.
	 *
	 * @return int
	 */
	public function remaining(): int {
		return max( 0, $this->total - $this->completed );
	}

	/**
	 * Completion percentage 0-100.
	 *
	 * @return int
	 */
	public function percent(): int {
		if ( $this->total <= 0 ) {
			return 0;
		}
		return (int) min( 100, round( $this->completed / $this->total * 100 ) );
	}

	/**
	 * Whether a run is in flight (running or cancelling).
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return self::STATUS_RUNNING === $this->status || self::STATUS_CANCELLING === $this->status;
	}

	/**
	 * Items that completed successfully (completed minus skipped and failed), never negative.
	 *
	 * @return int
	 */
	public function succeeded(): int {
		return max( 0, $this->completed - $this->skipped - $this->failed );
	}

	/**
	 * Serialize to a plain array for option storage / REST.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'total'     => $this->total,
			'completed' => $this->completed,
			'skipped'   => $this->skipped,
			'failed'    => $this->failed,
			'status'    => $this->status,
			'label'     => $this->label,
			'startedAt' => $this->started_at,
			'updatedAt' => $this->updated_at,
		);
	}

	/**
	 * Rebuild from a stored array.
	 *
	 * @param array<string, mixed> $data Stored state.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			(int) ( $data['total'] ?? 0 ),
			(int) ( $data['completed'] ?? 0 ),
			(int) ( $data['skipped'] ?? 0 ),
			(int) ( $data['failed'] ?? 0 ),
			is_string( $data['status'] ?? null ) ? $data['status'] : self::STATUS_IDLE,
			is_string( $data['label'] ?? null ) ? $data['label'] : '',
			(int) ( $data['startedAt'] ?? 0 ),
			(int) ( $data['updatedAt'] ?? 0 )
		);
	}
}
