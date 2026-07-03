<?php
/**
 * Cloudflare Images provider: serves offloaded originals via imagedelivery.net.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Cloudflare;

use MediaOnAutopilot\Features\Cdn\ImageProvider;
use MediaOnAutopilot\Features\Cdn\ImageTransform;
use MediaOnAutopilot\Features\Cdn\WidthLadder;

defined( 'ABSPATH' ) || exit;

/**
 * Builds imagedelivery.net delivery URLs for offloaded attachments.
 */
final class CloudflareProvider implements ImageProvider {

	/**
	 * Sets up the provider with resolved Cloudflare settings.
	 *
	 * @param CloudflareConfig $config Resolved settings.
	 * @param ImageIdStore     $ids    Attachment -> CF image id map.
	 */
	public function __construct(
		private CloudflareConfig $config,
		private ImageIdStore $ids
	) {}

	/**
	 * Delivery URL for one transform. Returns $original_url unchanged when the provider cannot serve this image.
	 *
	 * @param string         $original_url Origin file URL.
	 * @param ImageTransform $transform    Requested transform + attachment context.
	 * @return string
	 */
	public function build_url( string $original_url, ImageTransform $transform ): string {
		if ( $transform->attachment_id <= 0 ) {
			return $original_url;
		}
		$image_id = $this->ids->get( $transform->attachment_id );
		if ( '' === $image_id ) {
			return $original_url;
		}

		/**
		 * Filters the Cloudflare Images delivery base URL (no trailing slash).
		 * Override to serve through a custom Images delivery domain instead of
		 * the default imagedelivery.net host.
		 *
		 * @param string $base         Default base, e.g. https://imagedelivery.net/<hash>.
		 * @param string $account_hash The Cloudflare account hash.
		 */
		$base = (string) apply_filters(
			'moap_cloudflare_delivery_base',
			$this->config->delivery_base(),
			$this->config->account_hash
		);

		return $base . '/' . $image_id . '/' . CloudflareOptions::serialize( $transform, $this->config );
	}

	/**
	 * Candidate srcset widths for a source/crop-region width.
	 *
	 * @param int $source_width Source (or crop-region) width in pixels.
	 * @param int $max_width    Hard ceiling.
	 * @return int[]
	 */
	public function srcset_widths( int $source_width, int $max_width ): array {
		return WidthLadder::ladder_widths( $source_width, $max_width );
	}

	/**
	 * Whether the provider encodes the focal point in the URL.
	 *
	 * @return bool
	 */
	public function encodes_focal_in_url(): bool {
		return true;
	}

	/**
	 * Whether a URL is already one of this provider's delivery URLs.
	 *
	 * @param string $url URL to test.
	 * @return bool
	 */
	public function is_already_rewritten( string $url ): bool {
		return str_contains( $url, 'imagedelivery.net/' );
	}
}
