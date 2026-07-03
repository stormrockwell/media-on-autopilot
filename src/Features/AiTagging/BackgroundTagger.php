<?php
/**
 * Background queue that enriches attachments out-of-band.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

use MediaOnAutopilot\Support\Batch\ProgressProcess;
use MediaOnAutopilot\Support\Batch\ProgressState;
use MediaOnAutopilot\Support\Batch\ProgressStore;

defined( 'ABSPATH' ) || exit;

/**
 * Processes queued attachment IDs through the Tagger.
 */
final class BackgroundTagger extends ProgressProcess {

	public const SLUG = 'ai_enrichment';

	/**
	 * Queue action name.
	 *
	 * @var string
	 */
	protected $action = 'ai_enrichment';

	/**
	 * Constructs BackgroundTagger with its pipeline and progress store.
	 *
	 * @param Tagger        $tagger Shared pipeline.
	 * @param ProgressStore $store  Progress persistence.
	 */
	public function __construct( private Tagger $tagger, ProgressStore $store ) {
		parent::__construct( $store );
	}

	/**
	 * Job slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return self::SLUG;
	}

	/**
	 * Job label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Generating alt text, tags, and focal points', 'media-on-autopilot' );
	}

	/**
	 * Enrich one queued attachment.
	 *
	 * @param array<string, mixed> $item Queue item built by SavePlan::to_item().
	 * @return string One of ProgressState::OUTCOME_*.
	 */
	protected function handle_item( array $item ): string {
		$id = (int) ( $item['id'] ?? 0 );
		if ( $id <= 0 ) {
			return ProgressState::OUTCOME_FAILED;
		}
		$result = $this->tagger->tag( $id, SavePlan::from_item( $item ) );
		if ( ! $result->ok ) {
			return ProgressState::OUTCOME_FAILED;
		}
		if ( $result->skipped ) {
			return ProgressState::OUTCOME_SKIPPED;
		}
		return ProgressState::OUTCOME_WRITTEN;
	}
}
