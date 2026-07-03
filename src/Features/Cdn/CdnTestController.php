<?php
/**
 * REST endpoint for the admin CDN delivery test.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes POST /moap/v1/cdn/test for the settings page.
 */
final class CdnTestController {

	/**
	 * Sets up the controller with the tester.
	 *
	 * @param CdnTester $tester Delivery tester.
	 */
	public function __construct( private CdnTester $tester ) {}

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
			'/cdn/test',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * Only site admins may trigger a delivery test.
	 *
	 * @return bool
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Run the delivery test and return the result.
	 *
	 * @return \WP_REST_Response
	 */
	public function handle(): \WP_REST_Response {
		return rest_ensure_response( $this->tester->run() );
	}
}
