<?php
/**
 * Tags newly uploaded images when the setting is enabled.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks attachment creation to auto-tag images (fill-empty).
 */
final class AutoTagOnUpload {

	/**
	 * Constructs AutoTagOnUpload with its collaborators.
	 *
	 * @param Tagger         $tagger    Shared pipeline.
	 * @param AutoTagSetting $setting   Enablement gate for alt, tags, and focal.
	 * @param Connector      $connector Availability gate.
	 */
	public function __construct(
		private Tagger $tagger,
		private AutoTagSetting $setting,
		private Connector $connector
	) {}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_attachment', array( $this, 'maybe_tag' ) );
	}

	/**
	 * Tag the attachment if eligible.
	 *
	 * Fires one combined call writing alt, tags, and focal when auto-tag is on.
	 *
	 * @param int $attachment_id New attachment ID.
	 * @return void
	 */
	public function maybe_tag( int $attachment_id ): void {
		if ( ! $this->connector->is_available() || ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		if ( ! $this->setting->is_enabled() ) {
			return;
		}

		$this->tagger->tag( $attachment_id, SavePlan::fill_missing( true, true, true ) );
	}
}
