<?php
/**
 * "Offload existing media" tool row + AJAX start endpoint.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Cloudflare;

use MediaOnAutopilot\Support\Settings\SettingsStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the bulk offload tool and starts it via REST (no page reload).
 */
final class OffloadTool {

	/**
	 * Sets up the tool with its background offloader.
	 *
	 * @param CloudflareOffloader $offloader Background offloader.
	 */
	public function __construct( private CloudflareOffloader $offloader ) {}

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
			'/batch/' . CloudflareOffloader::SLUG . '/start',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
			)
		);
	}

	/**
	 * Contribute this tool's descriptor when Cloudflare is the live provider.
	 *
	 * @param array<int, array<string, mixed>> $tools Tool descriptors.
	 * @return array<int, array<string, mixed>>
	 */
	public function descriptor( array $tools ): array {
		$status    = ( new SettingsStatus() )->to_array();
		$available = 'cloudflare' === $status['provider'] && $this->offloader->is_configured();
		$offloaded = (array) ( $status['offloaded'] ?? array() );
		$done      = (int) ( $offloaded['done'] ?? 0 );
		$total     = (int) ( $offloaded['total'] ?? 0 );

		$tools[] = array(
			'slug'          => CloudflareOffloader::SLUG,
			'group'         => 'cdn',
			'title'         => __( 'Offload existing media', 'media-on-autopilot' ),
			'description'   => __( 'Push existing media to Cloudflare in the background. Local originals are always kept.', 'media-on-autopilot' ),
			'startEndpoint' => 'moap/v1/batch/' . CloudflareOffloader::SLUG . '/start',
			'showSummary'   => true,
			'available'     => $available,
			'syncedLine'    => sprintf(
				/* translators: 1: synced count, 2: total image count */
				__( '%1$s of %2$s images synced to Cloudflare', 'media-on-autopilot' ),
				number_format_i18n( $done ),
				number_format_i18n( $total )
			),
			'options'       => array(),
		);
		return $tools;
	}

	/**
	 * Enqueue all not-yet-offloaded images and return the queued count.
	 *
	 * @return \WP_REST_Response
	 */
	public function handle(): \WP_REST_Response {
		if ( ! $this->offloader->is_configured() ) {
			return rest_ensure_response(
				array(
					'started' => false,
					'queued'  => 0,
				)
			);
		}

		$count = $this->offloader->enqueue_all();

		return rest_ensure_response(
			array(
				'started' => true,
				'queued'  => $count,
			)
		);
	}
}
