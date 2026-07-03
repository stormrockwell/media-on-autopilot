<?php
/**
 * Live reachability check for a BunnyCDN pull zone.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Bunny;

use MediaOnAutopilot\Features\Cdn\Verification\Verifiable;
use MediaOnAutopilot\Features\Cdn\Verification\VerificationResult;

defined( 'ABSPATH' ) || exit;

/**
 * Confirms the pull zone actually serves an image from this site's origin.
 */
final class BunnyVerifier implements Verifiable {

	/**
	 * Sets up the verifier with resolved Bunny settings.
	 *
	 * @param BunnyConfig $config Resolved Bunny settings.
	 */
	public function __construct( private BunnyConfig $config ) {}

	/**
	 * Check the pull zone by fetching a known core image through it. A reachable
	 * hostname alone is not enough — every *.b-cdn.net name resolves via the
	 * wildcard — so the zone counts as connected only when it returns an actual
	 * image, which proves the zone exists and its origin points to this site.
	 *
	 * @return VerificationResult
	 */
	public function verify(): VerificationResult {
		if ( '' === $this->config->host ) {
			return VerificationResult::unconfigured(
				__( 'Enter a pull-zone hostname to connect BunnyCDN.', 'media-on-autopilot' )
			);
		}

		$path = (string) wp_parse_url( includes_url( 'images/w-logo-blue-white-bg.png' ), PHP_URL_PATH );
		if ( '' === $path ) {
			$path = '/wp-includes/images/w-logo-blue-white-bg.png';
		}

		// wp_safe_remote_get rejects URLs resolving to private/reserved ranges; the
		// host comes from an admin-settable option, so this closes an SSRF probe.
		$response = wp_safe_remote_get(
			'https://' . $this->config->host . $path . '?width=20',
			array( 'timeout' => 15 )
		);

		if ( is_wp_error( $response ) ) {
			// Don't surface the raw transport error (it can echo internal host/port detail).
			return VerificationResult::error(
				__( 'Could not reach the pull-zone hostname.', 'media-on-autopilot' ),
				__( 'The request to the pull zone could not be completed.', 'media-on-autopilot' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$type = strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) );

		if ( 200 === $code && str_starts_with( $type, 'image/' ) ) {
			return VerificationResult::ok( __( 'Pull zone is serving images from your site.', 'media-on-autopilot' ) );
		}

		if ( 200 === $code ) {
			return VerificationResult::error(
				__( 'The hostname responded but did not serve an image. Check the pull-zone name and that its origin points to this site.', 'media-on-autopilot' ),
				'' !== $type ? $type : __( 'unexpected response', 'media-on-autopilot' )
			);
		}

		return VerificationResult::error(
			__( 'The pull zone did not serve the test image. Check the pull-zone name and its origin.', 'media-on-autopilot' ),
			/* translators: %d: HTTP status code. */
			sprintf( __( 'HTTP %d', 'media-on-autopilot' ), $code )
		);
	}
}
