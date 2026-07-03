<?php
/**
 * Immutable outcome of verifying a provider connection.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Verification;

defined( 'ABSPATH' ) || exit;

/**
 * Result of a CDN/AI provider connection check.
 */
final class VerificationResult {

	public const OK           = 'ok';
	public const ERROR        = 'error';
	public const UNCONFIGURED = 'unconfigured';

	/**
	 * Use the named factories instead of constructing directly.
	 *
	 * @param string $state   One of OK|ERROR|UNCONFIGURED.
	 * @param string $message Human-readable summary.
	 * @param string $detail  Optional extra detail (status code / API error).
	 */
	private function __construct(
		public readonly string $state,
		public readonly string $message,
		public readonly string $detail = ''
	) {}

	/**
	 * A successful connection.
	 *
	 * @param string $message Summary.
	 * @return self
	 */
	public static function ok( string $message ): self {
		return new self( self::OK, $message );
	}

	/**
	 * A reachable-but-rejected or unreachable connection.
	 *
	 * @param string $message Summary.
	 * @param string $detail  Optional status/API detail.
	 * @return self
	 */
	public static function error( string $message, string $detail = '' ): self {
		return new self( self::ERROR, $message, $detail );
	}

	/**
	 * Required credentials are not present yet.
	 *
	 * @param string $message Summary.
	 * @return self
	 */
	public static function unconfigured( string $message ): self {
		return new self( self::UNCONFIGURED, $message );
	}

	/**
	 * Serialize for caching / REST.
	 *
	 * @return array{state:string,message:string,detail:string}
	 */
	public function to_array(): array {
		return array(
			'state'   => $this->state,
			'message' => $this->message,
			'detail'  => $this->detail,
		);
	}

	/**
	 * Rebuild from a cached array.
	 *
	 * @param array<string,mixed> $data Serialized result.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$state = isset( $data['state'] ) && in_array( $data['state'], array( self::OK, self::ERROR, self::UNCONFIGURED ), true )
			? (string) $data['state']
			: self::ERROR;

		return new self(
			$state,
			isset( $data['message'] ) ? (string) $data['message'] : '',
			isset( $data['detail'] ) ? (string) $data['detail'] : ''
		);
	}
}
