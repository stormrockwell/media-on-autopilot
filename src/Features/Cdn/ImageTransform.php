<?php
/**
 * Provider-agnostic image transform spec + attachment context.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * One requested image transform, plus the attachment it belongs to.
 */
final class ImageTransform {

	/**
	 * Creates an image transform spec for a CDN delivery request.
	 *
	 * @param int             $attachment_id Source attachment ID, 0 when unknown.
	 * @param int             $width         Output width in pixels.
	 * @param int             $height        Output height, <= 0 = unconstrained.
	 * @param bool            $crop          Whether this is a hard-crop size.
	 * @param FocalPoint|null $focal         Focal point anchoring a crop.
	 * @param int             $orig_width    Original upload width.
	 * @param int             $orig_height   Original upload height.
	 */
	public function __construct(
		public readonly int $attachment_id,
		public readonly int $width,
		public readonly int $height,
		public readonly bool $crop,
		public readonly ?FocalPoint $focal,
		public readonly int $orig_width,
		public readonly int $orig_height
	) {}

	/**
	 * Uncropped, full-width spec for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $width         Output width.
	 * @return self
	 */
	public static function center( int $attachment_id, int $width ): self {
		return new self( $attachment_id, $width, 0, false, null, 0, 0 );
	}
}
