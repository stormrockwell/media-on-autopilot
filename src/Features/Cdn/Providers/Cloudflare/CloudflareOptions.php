<?php
/**
 * Pure Cloudflare flexible-variant option-string serializer.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Cloudflare;

use MediaOnAutopilot\Features\Cdn\ImageTransform;
use MediaOnAutopilot\Features\FocalPoint\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the comma-joined option segment of an imagedelivery.net URL.
 */
final class CloudflareOptions {

	/**
	 * Serialize a transform into a flexible-variant option string.
	 *
	 * @param ImageTransform   $t Requested transform.
	 * @param CloudflareConfig $c Resolved settings.
	 * @return string
	 */
	public static function serialize( ImageTransform $t, CloudflareConfig $c ): string {
		$parts = array( 'w=' . $t->width );

		if ( $t->crop && $t->height > 0 ) {
			$f       = $t->focal ?? FocalPoint::center();
			$parts[] = 'h=' . $t->height;
			$parts[] = 'fit=cover';
			$parts[] = 'gravity=' . self::coord( $f->x ) . 'x' . self::coord( $f->y );
		} elseif ( $t->height > 0 ) {
			$parts[] = 'h=' . $t->height;
			$parts[] = 'fit=scale-down';
		}

		if ( 'off' !== $c->format ) {
			$parts[] = 'quality=' . $c->quality;
			if ( 'webp' === $c->format || 'avif' === $c->format || 'auto' === $c->format ) {
				$parts[] = 'format=' . $c->format;
			}
		}

		return implode( ',', $parts );
	}

	/**
	 * Format a normalized coordinate (0..1) trimmed to 4 decimals.
	 *
	 * @param float $value Coordinate.
	 * @return string
	 */
	private static function coord( float $value ): string {
		$clamped = max( 0.0, min( 1.0, $value ) );
		return rtrim( rtrim( number_format( $clamped, 4, '.', '' ), '0' ), '.' );
	}
}
