<?php
/**
 * "Retag existing media" settings tool + handler.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

use MediaOnAutopilot\Support\Batch\ProgressProcess;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues every image attachment for AI tagging from the settings screen.
 */
final class RetagTool {

	/**
	 * Sets up the tool with its background queue.
	 *
	 * @param ProgressProcess $process Background tagging queue (a BackgroundTagger in production).
	 */
	public function __construct( private ProgressProcess $process ) {}

	/**
	 * Register the tool row + REST start route.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'moap_settings_tools', array( $this, 'descriptor' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the start route: POST moap/v1/batch/{slug}/start.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'moap/v1',
			'/batch/' . BackgroundTagger::SLUG . '/start',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
				'args'                => array(
					'overwrite' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'alt'       => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'tags'      => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'focal'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);
	}

	/**
	 * Contribute this tool's descriptor to the settings-state payload.
	 *
	 * @param array<int, array<string, mixed>> $tools Tool descriptors.
	 * @return array<int, array<string, mixed>>
	 */
	public function descriptor( array $tools ): array {
		$tools[] = array(
			'slug'          => BackgroundTagger::SLUG,
			'group'         => 'ai',
			'title'         => __( 'Retag existing media', 'media-on-autopilot' ),
			'description'   => __( 'Generate alt text, tags & focal points for images already in the library. Runs in the background; existing values are preserved unless you choose to overwrite (tags are always merged in).', 'media-on-autopilot' ),
			'startEndpoint' => 'moap/v1/batch/' . BackgroundTagger::SLUG . '/start',
			'showSummary'   => true,
			'available'     => true,
			'syncedLine'    => null,
			'options'       => array(
				array(
					'key'     => 'alt',
					'label'   => __( 'Alt text', 'media-on-autopilot' ),
					'default' => true,
				),
				array(
					'key'     => 'tags',
					'label'   => __( 'Tags', 'media-on-autopilot' ),
					'default' => true,
				),
				array(
					'key'     => 'focal',
					'label'   => __( 'Focal points', 'media-on-autopilot' ),
					'default' => true,
				),
				array(
					'key'     => 'overwrite',
					'label'   => __( 'Overwrite existing values', 'media-on-autopilot' ),
					'default' => false,
					'type'    => 'toggle',
				),
			),
		);
		return $tools;
	}

	/**
	 * Enqueue all image attachments per the field + overwrite flags; return count.
	 *
	 * @param \WP_REST_Request $request Request carrying the overwrite + field flags.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$plan = $this->plan_from(
			(bool) $request->get_param( 'overwrite' ),
			(bool) $request->get_param( 'alt' ),
			(bool) $request->get_param( 'tags' ),
			(bool) $request->get_param( 'focal' )
		);

		if ( ! $plan->wants_anything() ) {
			return rest_ensure_response(
				array(
					'started' => false,
					'queued'  => 0,
				)
			);
		}

		$count = $this->enqueue_plan( $plan );

		return rest_ensure_response(
			array(
				'started' => true,
				'queued'  => $count,
			)
		);
	}

	/**
	 * Build a SavePlan from the overwrite flag and the per-field selections.
	 *
	 * @param bool $overwrite Replace existing alt/focal (tags always additive).
	 * @param bool $alt       Write alt text.
	 * @param bool $tags      Write tags.
	 * @param bool $focal     Write focal points.
	 * @return SavePlan
	 */
	private function plan_from( bool $overwrite, bool $alt, bool $tags, bool $focal ): SavePlan {
		return $overwrite
			? SavePlan::overwrite( $alt, $tags, $focal )
			: SavePlan::fill_missing( $alt, $tags, $focal );
	}

	/**
	 * Queue every image attachment for tagging per the overwrite flag (all fields).
	 *
	 * @param bool $overwrite Replace existing alt/focal (tags always additive).
	 * @return int Number queued.
	 */
	public function enqueue_all( bool $overwrite ): int {
		return $this->enqueue_plan( $this->plan_from( $overwrite, true, true, true ) );
	}

	/**
	 * Queue every image attachment for tagging using the given plan.
	 *
	 * @param SavePlan $plan Per-field write policy.
	 * @return int Number queued.
	 */
	private function enqueue_plan( SavePlan $plan ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'nopaging'       => true,
			)
		);

		$items = array_map( static fn( $id ) => $plan->to_item( (int) $id ), $ids );
		$this->process->start( $items );

		return count( $items );
	}
}
