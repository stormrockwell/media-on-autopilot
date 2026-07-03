<?php
/**
 * Immutable Cloudflare Images configuration.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Cloudflare;

defined( 'ABSPATH' ) || exit;

/**
 * Resolved Cloudflare Images settings.
 */
final class CloudflareConfig {

	/**
	 * Sets up the configuration with resolved Cloudflare credentials and delivery options.
	 *
	 * @param string $account_id   Cloudflare account ID.
	 * @param string $api_token    API token with Images:Edit.
	 * @param string $account_hash Delivery hash (imagedelivery.net/<hash>).
	 * @param int    $quality      Image quality 1-100.
	 * @param string $format       auto|webp|avif|off.
	 */
	public function __construct(
		public readonly string $account_id,
		public readonly string $api_token,
		public readonly string $account_hash,
		public readonly int $quality,
		public readonly string $format
	) {}

	/**
	 * Whether uploads/delivery can run (credentials present).
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return '' !== $this->account_id && '' !== $this->api_token;
	}

	/**
	 * Default delivery URL base (no trailing slash). The filterable base used
	 * for actual delivery is applied in CloudflareProvider::build_url().
	 *
	 * @return string
	 */
	public function delivery_base(): string {
		return 'https://imagedelivery.net/' . $this->account_hash;
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
