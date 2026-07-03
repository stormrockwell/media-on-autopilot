<?php
/**
 * Pure BunnyCDN URL and optimizer-parameter builder.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Bunny;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Builds BunnyCDN pull-zone URLs with optimizer query parameters.
 *
 * Pure: no WordPress calls. Callers supply the host, dimensions, and any
 * filtered values. Width math is delegated to WidthLadder.
 */
final class UrlRewriter {

	/**
	 * Largest source-pixel crop region matching the target aspect ratio that
	 * fits inside the original image (a "cover" region). Bunny crops this region
	 * around the focal point, then resizes it down to the requested width.
	 *
	 * @param int $orig_width  Original upload width.
	 * @param int $orig_height Original upload height.
	 * @param int $target_w    Target/output width (used for aspect ratio).
	 * @param int $target_h    Target/output height (used for aspect ratio).
	 * @return array{0:int,1:int} [ region_width, region_height ] in source pixels.
	 */
	public static function crop_region( int $orig_width, int $orig_height, int $target_w, int $target_h ): array {
		if ( $orig_width <= 0 || $orig_height <= 0 || $target_w <= 0 || $target_h <= 0 ) {
			return array( max( 0, $target_w ), max( 0, $target_h ) );
		}

		$target_ratio = $target_w / $target_h;

		if ( ( $orig_width / $orig_height ) >= $target_ratio ) {
			// Original is wider than the target: height is the limiting dimension.
			return array( (int) round( $orig_height * $target_ratio ), $orig_height );
		}

		// Original is taller: width is the limiting dimension.
		return array( $orig_width, (int) round( $orig_width / $target_ratio ) );
	}

	/**
	 * Assemble Bunny optimizer query params.
	 *
	 * @param BunnyConfig     $config      Resolved settings.
	 * @param int             $width       Output width in pixels (resize target).
	 * @param int             $height      Output height, or <= 0 for unconstrained.
	 * @param bool            $crop        Whether this is a hard-crop size.
	 * @param FocalPoint|null $focal       Focal point that anchors the crop.
	 * @param int             $orig_width  Original upload width (crop sizes only).
	 * @param int             $orig_height Original upload height (crop sizes only).
	 * @return array<string, int|string>
	 */
	public static function params( BunnyConfig $config, int $width, int $height, bool $crop, ?FocalPoint $focal, int $orig_width = 0, int $orig_height = 0 ): array {
		if ( $crop && $height > 0 ) {
			$f                       = $focal ?? FocalPoint::center();
			[ $region_w, $region_h ] = self::crop_region( $orig_width, $orig_height, $width, $height );
			if ( $f->is_center() ) {
				// No focal point: a plain center crop of the aspect-matched region.
				$params = array(
					'crop'  => $region_w . ',' . $region_h,
					'width' => $width,
				);
			} else {
				$params = array(
					'focus_crop' => $region_w . ',' . $region_h . ',' . self::coord( $f->x ) . ',' . self::coord( $f->y ),
					'width'      => $width,
				);
			}
		} else {
			$params = array( 'width' => $width );
			if ( $height > 0 ) {
				$params['height'] = $height;
			}
		}

		if ( 'off' === $config->format ) {
			return $params;
		}

		$params['quality'] = $config->quality;

		if ( 'webp' === $config->format || 'avif' === $config->format ) {
			$params['format'] = $config->format;
		}

		return $params;
	}

	/**
	 * Format a normalized focal coordinate (0..1) for a Bunny focus_crop value.
	 *
	 * @param float $value Coordinate in the range [0, 1].
	 * @return string
	 */
	private static function coord( float $value ): string {
		$clamped = max( 0.0, min( 1.0, $value ) );

		return rtrim( rtrim( number_format( $clamped, 4, '.', '' ), '0' ), '.' );
	}

	/**
	 * Build the final pull-zone URL.
	 *
	 * @param string                    $original_url Origin file URL.
	 * @param string                    $host         Pull-zone host.
	 * @param array<string, int|string> $params       Optimizer query params.
	 * @return string
	 */
	public static function build( string $original_url, string $host, array $params ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Pure class; no WP bootstrap in unit tests, and PHP 8.3 has no protocol-relative bug.
		$parts  = parse_url( $original_url );
		$scheme = $parts['scheme'] ?? 'https';
		$path   = $parts['path'] ?? '';
		$url    = $scheme . '://' . $host . $path;

		if ( array() !== $params ) {
			// Bunny optimizer params use literal commas (e.g. focus_crop=300,300,0.8,0.4).
			$url .= '?' . str_replace( '%2C', ',', http_build_query( $params ) );
		}

		return $url;
	}
}
