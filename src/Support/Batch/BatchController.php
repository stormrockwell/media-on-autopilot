<?php
/**
 * Generic REST status/cancel routes for registered batch jobs.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Support\Batch;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes GET/POST batch routes for jobs registered against it.
 */
final class BatchController {

	/**
	 * Registered jobs keyed by slug: [ 'process' => ProgressProcess, 'cap' => string ].
	 *
	 * @var array<string, array{process: ProgressProcess, cap: string}>
	 */
	private array $jobs = array();

	/**
	 * Constructs the controller with its progress store.
	 *
	 * @param ProgressStore $store Progress persistence.
	 */
	public function __construct( private ProgressStore $store ) {}

	/**
	 * Register a job for status/cancel exposure.
	 *
	 * @param string          $slug       Job slug.
	 * @param ProgressProcess $process    Job process.
	 * @param string          $capability Capability required to read/cancel.
	 * @return void
	 */
	public function register_job( string $slug, ProgressProcess $process, string $capability ): void {
		$this->jobs[ $slug ] = array(
			'process' => $process,
			'cap'     => $capability,
		);
	}

	/**
	 * Register REST hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Define a concrete status + cancel route per registered job.
	 *
	 * Registering one route per slug (rather than a single `(?P<slug>)` route)
	 * keeps multiple BatchController instances from colliding on a shared route
	 * pattern, which would otherwise let the first-registered controller answer
	 * for every slug.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		foreach ( array_keys( $this->jobs ) as $slug ) {
			$capability = $this->jobs[ $slug ]['cap'];
			$process    = $this->jobs[ $slug ]['process'];

			register_rest_route(
				'moap/v1',
				'/batch/' . $slug,
				array(
					'methods'             => 'GET',
					'callback'            => function () use ( $slug ): \WP_REST_Response {
						return rest_ensure_response(
							array_merge(
								$this->store->get( $slug )->to_array(),
								array( 'lastRun' => $this->store->last_run( $slug ) )
							)
						);
					},
					'permission_callback' => static function () use ( $capability ): bool {
						return current_user_can( $capability );
					},
				)
			);
			register_rest_route(
				'moap/v1',
				'/batch/' . $slug . '/cancel',
				array(
					'methods'             => 'POST',
					'callback'            => function () use ( $slug, $process ): \WP_REST_Response {
						$process->stop();
						return rest_ensure_response( $this->store->get( $slug )->to_array() );
					},
					'permission_callback' => static function () use ( $capability ): bool {
						return current_user_can( $capability );
					},
				)
			);
		}
	}
}
