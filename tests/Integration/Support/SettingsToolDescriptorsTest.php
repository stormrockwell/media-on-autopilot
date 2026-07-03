<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Support;

use MediaOnAutopilot\Features\AiTagging\AutoTagSetting;
use MediaOnAutopilot\Features\AiTagging\BackgroundTagger;
use MediaOnAutopilot\Features\AiTagging\RetagTool;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareOffloader;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImageIdStore;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImagesApiClient;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\OffloadTool;
use MediaOnAutopilot\Support\Batch\ProgressState;
use MediaOnAutopilot\Support\Batch\ProgressProcess;
use MediaOnAutopilot\Support\Batch\ProgressStore;
use MediaOnAutopilot\Support\Settings\SettingsState;
use WP_UnitTestCase;

final class SettingsToolDescriptorsTest extends WP_UnitTestCase {

	/**
	 * OffloadTool should contribute a cdn-group descriptor to the settings-state
	 * tools filter (React renders the tool; PHP no longer emits HTML for it).
	 */
	public function test_offload_tool_contributes_cdn_descriptor(): void {
		$config    = new CloudflareConfig( 'a', 't', '', 85, 'auto' );
		$offloader = new CloudflareOffloader( $config, new ImagesApiClient( $config ), new ImageIdStore(), new ProgressStore() );
		( new OffloadTool( $offloader ) )->register();

		$tools = ( new SettingsState() )->to_array()['tools'];
		$slugs = array_column( $tools, 'slug' );

		$this->assertContains( 'cloudflare_offload', $slugs );

		$descriptor = $tools[ array_search( 'cloudflare_offload', $slugs, true ) ];
		$this->assertSame( 'cdn', $descriptor['group'] );
	}

	/**
	 * RetagTool should contribute an ai-group descriptor to the settings-state
	 * tools filter (React renders the tool; PHP no longer emits HTML for it).
	 */
	public function test_retag_tool_contributes_ai_descriptor(): void {
		$process = new class( new ProgressStore() ) extends ProgressProcess {
			protected function handle_item( array $item ): string { return ProgressState::OUTCOME_WRITTEN; }
			public function slug(): string { return BackgroundTagger::SLUG; }
			public function label(): string { return 'Test retag'; }
		};
		( new RetagTool( $process ) )->register();

		$tools = ( new SettingsState() )->to_array()['tools'];
		$slugs = array_column( $tools, 'slug' );

		$this->assertContains( 'ai_enrichment', $slugs );

		$descriptor = $tools[ array_search( 'ai_enrichment', $slugs, true ) ];
		$this->assertSame( 'ai', $descriptor['group'] );
	}
}
