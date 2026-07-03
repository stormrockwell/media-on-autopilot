<?php
/**
 * Shared responsive width ladder + hard max-width for CDN providers.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn;

defined( 'ABSPATH' ) || exit;

/**
 * Pure width math shared by all CDN providers.
 */
final class WidthLadder {

	public const DEFAULT_MAX_WIDTH = 2560;

	/**
	 * Breakpoint widths used to build responsive srcset candidates.
	 *
	 * @var int[]
	 */
	public const LADDER = array( 320, 480, 640, 768, 1024, 1366, 1600, 1920, 2560 );

	/**
	 * Width to request, capped at the original and the hard ceiling.
	 *
	 * @param int $requested  Layout width in CSS pixels.
	 * @param int $orig_width Original upload width.
	 * @param int $max_width  Hard ceiling.
	 * @return int
	 */
	public static function target_width( int $requested, int $orig_width, int $max_width ): int {
		$bounds = array( $requested, $max_width );
		if ( $orig_width > 0 ) {
			$bounds[] = $orig_width;
		}

		return max( 1, min( $bounds ) );
	}

	/**
	 * Ladder capped at min(source, max), always including the cap.
	 *
	 * @param int $source_width Source/crop-region width.
	 * @param int $max_width    Hard ceiling.
	 * @return int[]
	 */
	public static function ladder_widths( int $source_width, int $max_width ): array {
		$cap = min( $source_width, $max_width );
		if ( $cap <= 0 ) {
			return array();
		}

		$widths = array_values( array_filter( self::LADDER, static fn( int $w ): bool => $w <= $cap ) );
		if ( ! in_array( $cap, $widths, true ) ) {
			$widths[] = $cap;
		}

		return $widths;
	}
}
