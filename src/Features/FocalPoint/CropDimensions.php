<?php
/**
 * Pure crop-offset math mirroring core image_resize_dimensions(), but
 * positioning the crop source by focal point instead of always centering.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Pure crop-offset math for focal-point-aware image resizing.
 *
 * Mirrors core image_resize_dimensions() but positions the crop source
 * by focal point instead of always centering.
 */
final class CropDimensions {

	/**
	 * Calculate crop dimensions for focal-point-aware image resizing.
	 *
	 * @param int        $orig_w Original image width in pixels.
	 * @param int        $orig_h Original image height in pixels.
	 * @param int        $dest_w Destination image width in pixels.
	 * @param int        $dest_h Destination image height in pixels.
	 * @param FocalPoint $focal  Focal point position (0–1 on each axis).
	 *
	 * @return array{0:int,1:int,2:int,3:int,4:int,5:int,6:int,7:int}|null
	 *   [ dst_x, dst_y, src_x, src_y, dst_w, dst_h, src_w, src_h ] or null if invalid.
	 */
	public static function calculate(
		int $orig_w,
		int $orig_h,
		int $dest_w,
		int $dest_h,
		FocalPoint $focal
	): ?array {
		if ( $orig_w <= 0 || $orig_h <= 0 ) {
			return null;
		}

		$aspect_ratio = $orig_w / $orig_h;
		$new_w        = min( $dest_w, $orig_w );
		$new_h        = min( $dest_h, $orig_h );

		if ( ! $new_w ) {
			$new_w = (int) round( $new_h * $aspect_ratio );
		}
		if ( ! $new_h ) {
			$new_h = (int) round( $new_w / $aspect_ratio );
		}

		$size_ratio = max( $new_w / $orig_w, $new_h / $orig_h );
		$crop_w     = (int) round( $new_w / $size_ratio );
		$crop_h     = (int) round( $new_h / $size_ratio );

		$src_x = (int) floor( ( $orig_w - $crop_w ) * $focal->x );
		$src_y = (int) floor( ( $orig_h - $crop_h ) * $focal->y );
		$src_x = max( 0, min( $src_x, $orig_w - $crop_w ) );
		$src_y = max( 0, min( $src_y, $orig_h - $crop_h ) );

		return array( 0, 0, $src_x, $src_y, $new_w, $new_h, $crop_w, $crop_h );
	}
}
