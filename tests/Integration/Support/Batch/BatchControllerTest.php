<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Support\Batch;

use MediaOnAutopilot\Support\Batch\BatchController;
use MediaOnAutopilot\Support\Batch\ProgressProcess;
use MediaOnAutopilot\Support\Batch\ProgressState;
use MediaOnAutopilot\Support\Batch\ProgressStore;
use WP_REST_Request;
use WP_UnitTestCase;

final class BatchControllerTest extends WP_UnitTestCase {

	private function dummyProcess( ProgressStore $store ): ProgressProcess {
		return new class( $store ) extends ProgressProcess {
			protected $action = 'ctrl_job';
			protected function handle_item( array $item ): string {
				return ProgressState::OUTCOME_WRITTEN;
			}
			public function slug(): string {
				return 'ctrl_job';
			}
			public function label(): string {
				return 'Ctrl Job';
			}
		};
	}

	public function setUp(): void {
		parent::setUp();
		$store      = new ProgressStore();
		$controller = new BatchController( $store );
		$controller->register_job( 'ctrl_job', $this->dummyProcess( $store ), 'manage_options' );

		// Reset the REST server so the batch route from a globally-booted
		// AiTaggingModule/CdnModule doesn't shadow this test's controller.
		// override_by_default makes the last registration win, so the controller
		// registered below takes precedence on the shared /moap/v1/batch route.
		global $wp_rest_server;
		$wp_rest_server                      = new \Spy_REST_Server();
		$wp_rest_server->override_by_default = true;

		$controller->register();
		do_action( 'rest_api_init', $wp_rest_server );
		$this->store = $store;
	}

	private ProgressStore $store;

	public function test_status_route_returns_state(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->store->begin( 'ctrl_job', 'Ctrl Job', 5 );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/moap/v1/batch/ctrl_job' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 5, $response->get_data()['total'] );
		$this->assertSame( ProgressState::STATUS_RUNNING, $response->get_data()['status'] );
	}

	public function test_unknown_slug_is_404(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/moap/v1/batch/missing' ) );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_cancel_requires_capability(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$response = rest_do_request( new WP_REST_Request( 'POST', '/moap/v1/batch/ctrl_job/cancel' ) );
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_cancel_transitions_to_cancelling(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->store->begin( 'ctrl_job', 'Ctrl Job', 2 );

		$response = rest_do_request( new WP_REST_Request( 'POST', '/moap/v1/batch/ctrl_job/cancel' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( ProgressState::STATUS_CANCELLING, $this->store->get( 'ctrl_job' )->status );
	}

	public function test_status_response_includes_last_run_key(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/moap/v1/batch/ctrl_job' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'lastRun', $response->get_data() );
		$this->assertNull( $response->get_data()['lastRun'] );
	}

	public function test_last_run_populated_after_finish(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->store->begin( 'ctrl_job', 'Ctrl Job', 1 );
		$this->store->finish( 'ctrl_job' );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/moap/v1/batch/ctrl_job' ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'lastRun', $data );
		$this->assertIsArray( $data['lastRun'] );
		$this->assertArrayHasKey( 'finishedAt', $data['lastRun'] );
	}
}
