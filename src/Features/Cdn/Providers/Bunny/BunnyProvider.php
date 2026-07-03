<?php
/**
 * BunnyCDN provider: wraps the pure UrlRewriter behind ImageProvider.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Bunny;

use MediaOnAutopilot\Features\Cdn\ImageProvider;
use MediaOnAutopilot\Features\Cdn\ImageTransform;
use MediaOnAutopilot\Features\Cdn\WidthLadder;

defined( 'ABSPATH' ) || exit;

/**
 * Serves images through a BunnyCDN pull zone.
 */
final class BunnyProvider implements ImageProvider {

	/**
	 * Sets up the provider with resolved Bunny settings.
	 *
	 * @param BunnyConfig $config Resolved Bunny settings.
	 */
	public function __construct( private BunnyConfig $config ) {}

	/**
	 * Delivery URL for one transform. Returns $original_url unchanged when the provider cannot serve this image.
	 *
	 * @param string         $original_url Origin file URL.
	 * @param ImageTransform $transform    Requested transform + attachment context.
	 * @return string
	 */
	public function build_url( string $original_url, ImageTransform $transform ): string {
		$params = UrlRewriter::params(
			$this->config,
			$transform->width,
			$transform->height,
			$transform->crop,
			$transform->focal,
			$transform->orig_width,
			$transform->orig_height
		);

		return UrlRewriter::build( $original_url, $this->config->host, $params );
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
		return '' !== $this->config->host && str_contains( $url, '//' . $this->config->host . '/' );
	}
}
