<?php
/**
 * Contract for a CDN image provider the shared frontend rewrites through.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn;

defined( 'ABSPATH' ) || exit;

/**
 * A CDN provider that turns an origin image URL + transform into a delivery URL.
 */
interface ImageProvider {

	/**
	 * Delivery URL for one transform. MUST return $original_url unchanged when the
	 * provider cannot serve this image (so pages never break).
	 *
	 * @param string         $original_url Origin file URL.
	 * @param ImageTransform $transform    Requested transform + attachment context.
	 * @return string
	 */
	public function build_url( string $original_url, ImageTransform $transform ): string;

	/**
	 * Candidate srcset widths for a source/crop-region width.
	 *
	 * @param int $source_width Source (or crop-region) width in pixels.
	 * @param int $max_width    Hard ceiling.
	 * @return int[]
	 */
	public function srcset_widths( int $source_width, int $max_width ): array;

	/**
	 * Whether the provider encodes the focal point in the URL (so the focal
	 * query-string cache-bust must be disabled).
	 *
	 * @return bool
	 */
	public function encodes_focal_in_url(): bool;

	/**
	 * Whether a URL is already one of this provider's delivery URLs.
	 *
	 * @param string $url URL to test.
	 * @return bool
	 */
	public function is_already_rewritten( string $url ): bool;
}
