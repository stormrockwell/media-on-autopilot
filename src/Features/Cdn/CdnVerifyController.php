<?php
/**
 * REST endpoint for the admin connection-status badges.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn;

use MediaOnAutopilot\Features\Cdn\Verification\CdnVerifier;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes GET /moap/v1/cdn/verify?provider=… for the settings page.
 */
final class CdnVerifyController {

	/**
	 * Sets up the controller with the verifier.
	 *
	 * @param CdnVerifier $verifier Provider verifier.
	 */
	public function __construct( private CdnVerifier $verifier ) {}

	/**
	 * Register the REST route.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Define the route.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'moap/v1',
			'/cdn/verify',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'provider' => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'bunny', 'cloudflare' ),
					),
				),
			)
		);
	}

	/**
	 * Only site admins may probe provider credentials.
	 *
	 * @return bool
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Return the (cached) verification result for the requested provider.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$provider = (string) $request->get_param( 'provider' );

		return rest_ensure_response( $this->verifier->verify( $provider )->to_array() );
	}
}
