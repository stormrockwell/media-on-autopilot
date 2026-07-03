<?php
/**
 * Injects the focal point into WordPress's native crop math.
 *
 * Context is captured before subsize generation (intermediate_image_sizes_advanced)
 * and read inside the image_resize_dimensions filter, then cleared once generation
 * completes. We never generate images ourselves.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the focal point into WordPress's native crop pipeline.
 */
final class CropHandler {

	/**
	 * The current attachment ID being processed, or null.
	 *
	 * @var int|null
	 */
	private ?int $current = null;

	/**
	 * Constructor.
	 *
	 * @param FocalPointMeta $meta Focal point metadata reader.
	 */
	public function __construct( private FocalPointMeta $meta ) {}

	/**
	 * Register WordPress filter hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'capture_context' ), 10, 3 );
		add_filter( 'image_resize_dimensions', array( $this, 'apply_focal_point' ), 10, 6 );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'clear_context' ), 10, 2 );
	}

	/**
	 * Capture the current attachment ID before subsize generation.
	 *
	 * @param array<string, array> $sizes         Image sizes.
	 * @param array<string, mixed> $image_meta    Image meta.
	 * @param int                  $attachment_id Attachment ID.
	 * @return array<string, array>
	 */
	public function capture_context( array $sizes, array $image_meta, int $attachment_id ): array {
		$this->current = $attachment_id;
		return $sizes;
	}

	/**
	 * Clear the current attachment context after generation completes.
	 *
	 * @param array<string, mixed> $metadata      Attachment metadata.
	 * @param int                  $attachment_id Attachment ID.
	 * @return array<string, mixed>
	 */
	public function clear_context( array $metadata, int $attachment_id ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by wp_generate_attachment_metadata filter signature.
		$this->current = null;
		return $metadata;
	}

	/**
	 * Apply focal-point crop offsets via the image_resize_dimensions filter.
	 *
	 * @param mixed      $payload Existing dimension array or null.
	 * @param int        $orig_w  Original width.
	 * @param int        $orig_h  Original height.
	 * @param int        $dest_w  Destination width.
	 * @param int        $dest_h  Destination height.
	 * @param bool|array $crop    Crop flag/position.
	 * @return mixed Adjusted dimension array, or the original payload.
	 */
	public function apply_focal_point( $payload, int $orig_w, int $orig_h, int $dest_w, int $dest_h, $crop ) {
		if ( empty( $crop ) || null === $this->current ) {
			return $payload;
		}

		$focal = $this->meta->get( $this->current );
		if ( $focal->is_center() ) {
			return $payload;
		}

		$dims = CropDimensions::calculate( $orig_w, $orig_h, $dest_w, $dest_h, $focal );

		return null === $dims ? $payload : $dims;
	}
}
