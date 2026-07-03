<?php
/**
 * Locator for the bundled sample image used by the CDN/AI test tools.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the bundled sample image path.
 */
final class SampleImage {

	/**
	 * Absolute path to the bundled sample JPEG.
	 *
	 * @return string
	 */
	public static function path(): string {
		return MOAP_PLUGIN_DIR . 'assets/sample-image.jpg';
	}
}
