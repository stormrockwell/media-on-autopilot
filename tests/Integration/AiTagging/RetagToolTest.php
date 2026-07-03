<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\RetagTool;
use MediaOnAutopilot\Support\Batch\ProgressProcess;
use MediaOnAutopilot\Support\Batch\ProgressState;
use MediaOnAutopilot\Support\Batch\ProgressStore;
use WP_UnitTestCase;

final class RetagToolTest extends WP_UnitTestCase {

	private function makeTool(): array {
		$captured = new \stdClass();
		$captured->items = null;
		// Concrete ProgressProcess double that records what gets started.
		$process = new class( new ProgressStore(), $captured ) extends ProgressProcess {
			public function __construct( ProgressStore $s, private \stdClass $cap ) { parent::__construct( $s ); }
			public function start( array $items ): bool { $this->cap->items = $items; return true; }
			protected function handle_item( array $item ): string { return ProgressState::OUTCOME_WRITTEN; }
			public function slug(): string { return 'test_retag'; }
			public function label(): string { return 'Test retag'; }
		};
		return array( new RetagTool( $process ), $captured );
	}

	public function test_enqueue_all_queues_image_attachments(): void {
		$this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		[ $tool, $captured ] = $this->makeTool();

		$count = $tool->enqueue_all( false );

		$this->assertGreaterThanOrEqual( 1, $count );
		$this->assertNotNull( $captured->items );
		$this->assertFalse( $captured->items[0]['overwrite'] );
	}

	public function test_overwrite_flag_sets_overwrite_items(): void {
		$this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		[ $tool, $captured ] = $this->makeTool();

		$tool->enqueue_all( true );

		$this->assertTrue( $captured->items[0]['overwrite'] );
	}

	public function test_handle_builds_plan_from_checked_fields(): void {
		$this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		[ $tool, $captured ] = $this->makeTool();

		$request = new \WP_REST_Request();
		$request->set_param( 'alt', true );
		$request->set_param( 'tags', false );
		$request->set_param( 'focal', false );
		$request->set_param( 'overwrite', false );

		$data = $tool->handle( $request )->get_data();

		$this->assertTrue( $data['started'] );
		$this->assertTrue( $captured->items[0]['alt'] );
		$this->assertFalse( $captured->items[0]['tags'] );
		$this->assertFalse( $captured->items[0]['focal'] );
	}

	public function test_handle_does_not_start_when_no_field_selected(): void {
		$this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		[ $tool, $captured ] = $this->makeTool();

		$request = new \WP_REST_Request();
		$request->set_param( 'alt', false );
		$request->set_param( 'tags', false );
		$request->set_param( 'focal', false );

		$data = $tool->handle( $request )->get_data();

		$this->assertFalse( $data['started'] );
		$this->assertSame( 0, $data['queued'] );
		$this->assertNull( $captured->items );
	}
}
