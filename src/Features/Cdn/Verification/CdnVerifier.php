<?php
/**
 * Routes a provider key to its connection check and caches the result.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Verification;

use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyVerifier;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImagesApiClient;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies the configured CDN provider, caching the outcome briefly so the
 * settings page never blocks repeatedly on a network round-trip.
 */
final class CdnVerifier {

	private const TTL = 300;

	/**
	 * Sets up the verifier with the provider settings resolvers.
	 *
	 * @param BunnySettings      $bunny      Bunny settings resolver.
	 * @param CloudflareSettings $cloudflare Cloudflare settings resolver.
	 */
	public function __construct(
		private BunnySettings $bunny,
		private CloudflareSettings $cloudflare
	) {}

	/**
	 * Verify one provider by key, returning a cached result when fresh.
	 *
	 * @param string $provider 'bunny' | 'cloudflare'.
	 * @return VerificationResult
	 */
	public function verify( string $provider ): VerificationResult {
		$pair = $this->verifiable_for( $provider );
		if ( null === $pair ) {
			return VerificationResult::unconfigured( __( 'Unknown provider.', 'media-on-autopilot' ) );
		}

		[ $key, $verifiable ] = $pair;

		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return VerificationResult::from_array( $cached );
		}

		$result = $verifiable->verify();
		set_transient( $key, $result->to_array(), self::TTL );

		return $result;
	}

	/**
	 * Resolve [ transient key, Verifiable ] for a provider, or null if unknown.
	 * The key embeds a credential hash so changing credentials busts the cache.
	 *
	 * @param string $provider Provider key.
	 * @return array{0:string,1:Verifiable}|null
	 */
	private function verifiable_for( string $provider ): ?array {
		switch ( $provider ) {
			case 'bunny':
				$config = $this->bunny->to_config();
				return array( 'moap_verify_bunny_' . md5( $config->host ), new BunnyVerifier( $config ) );

			case 'cloudflare':
				$config = $this->cloudflare->to_config();
				$hash   = md5( $config->account_id . '|' . $config->api_token . '|' . $config->account_hash );
				return array( 'moap_verify_cloudflare_' . $hash, new ImagesApiClient( $config ) );

			default:
				return null;
		}
	}
}
