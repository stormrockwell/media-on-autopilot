<?php
/**
 * Immutable BunnyCDN configuration value object.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Bunny;

defined( 'ABSPATH' ) || exit;

/**
 * Resolved, sanitized BunnyCDN settings.
 */
final class BunnyConfig {

	/**
	 * Constructor.
	 *
	 * @param string $host    Pull-zone host (no scheme/path).
	 * @param int    $quality Image quality 1-100.
	 * @param string $format  Format strategy: auto|webp|avif|off.
	 */
	public function __construct(
		public readonly string $host,
		public readonly int $quality,
		public readonly string $format
	) {}

	/**
	 * Whether delivery can run (pull-zone host present). Selecting BunnyCDN as
	 * the CDN provider is the on-switch; a host is the only required credential.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return '' !== $this->host;
	}

	/**
	 * Reduce arbitrary input to a bare host.
	 *
	 * @param string $raw Raw hostname or URL.
	 * @return string
	 */
	public static function sanitize_host( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}
		if ( ! str_contains( $raw, '//' ) ) {
			$raw = '//' . $raw;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Pure class; no WP bootstrap in unit tests, and PHP 8.3 has no protocol-relative bug.
		$host = parse_url( $raw, PHP_URL_HOST );

		return is_string( $host ) ? $host : '';
	}

	/**
	 * Clamp quality to 1-100.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public static function sanitize_quality( $value ): int {
		return min( 100, max( 1, absint( $value ) ) );
	}

	/**
	 * Validate format strategy.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_format( $value ): string {
		return in_array( $value, array( 'auto', 'webp', 'avif', 'off' ), true ) ? (string) $value : 'auto';
	}
}
