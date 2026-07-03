<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Settings;

use MediaOnAutopilot\Features\AiTagging\BackgroundTagger;
use MediaOnAutopilot\Features\AiTagging\RetagTool;
use MediaOnAutopilot\Support\Batch\ProgressProcess;
use MediaOnAutopilot\Support\Batch\ProgressState;
use MediaOnAutopilot\Support\Batch\ProgressStore;
use MediaOnAutopilot\Support\Settings\SettingsState;
use WP_UnitTestCase;

final class SettingsToolsTest extends WP_UnitTestCase {

	public function test_retag_descriptor_is_present(): void {
		// Instantiate and register RetagTool directly so its descriptor filter is
		// active regardless of whether the AI connector is available in the test env.
		$process = new class( new ProgressStore() ) extends ProgressProcess {
			protected function handle_item( array $item ): string { return ProgressState::OUTCOME_WRITTEN; }
			public function slug(): string { return BackgroundTagger::SLUG; }
			public function label(): string { return 'Test retag'; }
		};
		( new RetagTool( $process ) )->register();

		$tools = ( new SettingsState() )->to_array()['tools'];
		$slugs = array_column( $tools, 'slug' );

		$this->assertContains( 'ai_enrichment', $slugs );
	}
}
