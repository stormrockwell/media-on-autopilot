<?php
/**
 * REST endpoint that tags a single attachment (button trigger).
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes POST /moap/v1/ai-tagging/<id>.
 */
final class RestController {

	/**
	 * Constructs the RestController with its collaborators.
	 *
	 * @param Tagger    $tagger    Shared tagging pipeline.
	 * @param Connector $connector Availability gate.
	 */
	public function __construct(
		private Tagger $tagger,
		private Connector $connector
	) {}

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
			'/ai-tagging/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'can_edit' ),
			)
		);
	}

	/**
	 * Permission check: caller can edit the attachment.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	public function can_edit( \WP_REST_Request $request ): bool {
		return current_user_can( 'edit_post', (int) $request['id'] );
	}

	/**
	 * Run the tagging pipeline.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle( \WP_REST_Request $request ) {
		if ( ! $this->connector->is_available() ) {
			return new \WP_Error(
				'moap_ai_unavailable',
				__( 'No AI provider is configured.', 'media-on-autopilot' ),
				array( 'status' => 503 )
			);
		}

		$attachment_id = (int) $request['id'];
		$result        = $this->tagger->tag( $attachment_id, SavePlan::overwrite( true, true, false ) );
		if ( ! $result->ok ) {
			return new \WP_Error(
				$result->error->get_error_code(),
				$result->error->get_error_message(),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'alt'  => $result->alt,
				'tags' => $this->current_tags( $attachment_id ),
			)
		);
	}

	/**
	 * The attachment's full current media-tag set (merged, not just the new ones).
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string[]
	 */
	private function current_tags( int $attachment_id ): array {
		$terms = get_the_terms( $attachment_id, MediaTaxonomy::TAXONOMY );

		return is_array( $terms ) ? array_values( wp_list_pluck( $terms, 'name' ) ) : array();
	}
}
