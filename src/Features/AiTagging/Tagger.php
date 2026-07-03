<?php
/**
 * The single shared "enrich one attachment" pipeline.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Resize -> vision call -> parse -> selectively apply alt/tags/focal.
 */
final class Tagger {

	/**
	 * Constructs the Tagger with its collaborators.
	 *
	 * @param VisionClient   $client     Vision backend.
	 * @param ImageResizer   $resizer    Image downscaler.
	 * @param ResponseSchema $schema     Prompt/schema/target-tag-count provider.
	 * @param FocalPointMeta $focal_meta Focal point storage.
	 */
	public function __construct(
		private VisionClient $client,
		private ImageResizer $resizer,
		private ResponseSchema $schema,
		private FocalPointMeta $focal_meta
	) {}

	/**
	 * Enrich a single attachment per the save plan.
	 *
	 * @param int      $attachment_id Attachment post ID.
	 * @param SavePlan $plan          Per-field write policy.
	 * @return TagResult
	 */
	public function tag( int $attachment_id, SavePlan $plan ): TagResult {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return TagResult::error(
				new \WP_Error( 'moap_ai_not_image', __( 'Attachment is not an image.', 'media-on-autopilot' ) )
			);
		}

		if ( ! $plan->overwrite && $this->already_complete( $attachment_id, $plan ) ) {
			return TagResult::skipped();
		}

		$path = $this->resizer->resize( $attachment_id );
		if ( is_wp_error( $path ) ) {
			return TagResult::error( $path );
		}

		$response = $this->client->describe( $path, $this->schema->prompt(), $this->schema->schema() );
		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}

		if ( is_wp_error( $response ) ) {
			return TagResult::error( $response );
		}

		$suggestion = TagSuggestion::from_response( $response, $this->schema->target_tag_count() );
		$suggestion = apply_filters( 'moap_ai_tagging_result', $suggestion, $attachment_id );

		$wrote_alt   = $plan->alt && $this->apply_alt( $attachment_id, $suggestion->alt, $plan->overwrite );
		$wrote_focal = $plan->focal ? $this->apply_focal( $attachment_id, $suggestion->focal, $plan->overwrite ) : null;
		if ( $plan->tags ) {
			$this->apply_tags( $attachment_id, $suggestion->tags );
		}

		return TagResult::success(
			$wrote_alt ? $suggestion->alt : null,
			$plan->tags ? $suggestion->tags : array(),
			$wrote_focal
		);
	}

	/**
	 * Write alt text honoring the overwrite policy.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $alt           Proposed alt.
	 * @param bool   $overwrite     Replace existing alt.
	 * @return bool Whether alt was written.
	 */
	private function apply_alt( int $attachment_id, string $alt, bool $overwrite ): bool {
		if ( '' === $alt ) {
			return false;
		}
		if ( ! $overwrite ) {
			$existing = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			if ( '' !== trim( $existing ) ) {
				return false;
			}
		}
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		return true;
	}

	/**
	 * Write the focal point honoring the overwrite policy.
	 *
	 * @param int             $attachment_id Attachment ID.
	 * @param FocalPoint|null $focal         Proposed focal point.
	 * @param bool            $overwrite     Replace existing focal.
	 * @return FocalPoint|null The point written, or null if skipped.
	 */
	private function apply_focal( int $attachment_id, ?FocalPoint $focal, bool $overwrite ): ?FocalPoint {
		if ( null === $focal ) {
			return null;
		}
		if ( ! $overwrite && $this->focal_meta->has( $attachment_id ) ) {
			return null;
		}
		$this->focal_meta->set( $attachment_id, $focal );
		return $focal;
	}

	/**
	 * Merge tags additively (never replace).
	 *
	 * @param int      $attachment_id Attachment ID.
	 * @param string[] $tags          Tags to add.
	 * @return void
	 */
	private function apply_tags( int $attachment_id, array $tags ): void {
		if ( empty( $tags ) ) {
			return;
		}
		wp_set_object_terms( $attachment_id, $tags, MediaTaxonomy::TAXONOMY, true );
	}

	/**
	 * Returns true when every requested field already has a value.
	 *
	 * @param int      $id   Attachment post ID.
	 * @param SavePlan $plan The write policy being evaluated.
	 * @return bool
	 */
	private function already_complete( int $id, SavePlan $plan ): bool {
		$alt_ok   = ! $plan->alt || '' !== trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		$focal_ok = ! $plan->focal || $this->focal_meta->has( $id );
		$tags_ok  = ! $plan->tags || $this->has_tags( $id );
		return $alt_ok && $focal_ok && $tags_ok;
	}

	/**
	 * Returns true when the attachment has at least one media tag.
	 *
	 * @param int $id Attachment post ID.
	 * @return bool
	 */
	private function has_tags( int $id ): bool {
		$terms = get_the_terms( $id, MediaTaxonomy::TAXONOMY );
		return is_array( $terms ) && count( $terms ) >= 1;
	}
}
