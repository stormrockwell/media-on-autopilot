<?php
/**
 * REST endpoint for the AI test button: POST moap/v1/ai/test.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the manual AI test.
 */
final class AiTestController {

	/**
	 * Sets up the controller with the tester.
	 *
	 * @param AiTester $tester Sample-call runner.
	 */
	public function __construct( private AiTester $tester ) {}

	/**
	 * Register the route.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action(
			'rest_api_init',
			function (): void {
				register_rest_route(
					'moap/v1',
					'/ai/test',
					array(
						'methods'             => 'POST',
						'callback'            => fn(): \WP_REST_Response => rest_ensure_response( $this->tester->run() ),
						'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
					)
				);
			}
		);
	}
}
