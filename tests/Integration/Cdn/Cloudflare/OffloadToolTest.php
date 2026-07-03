<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareOffloader;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImageIdStore;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImagesApiClient;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\OffloadTool;
use MediaOnAutopilot\Support\Batch\ProgressStore;
use WP_UnitTestCase;

final class OffloadToolTest extends WP_UnitTestCase {

	public function test_register_adds_descriptor_filter(): void {
		$config = new CloudflareConfig( 'a', 't', '', 85, 'auto' );
		$tool   = new OffloadTool( new CloudflareOffloader( $config, new ImagesApiClient( $config ), new ImageIdStore(), new ProgressStore() ) );
		$tool->register();

		$this->assertTrue( has_filter( 'moap_settings_tools' ) !== false );
	}

	public function test_handle_does_not_start_when_cloudflare_unconfigured(): void {
		// Empty credentials: the start endpoint must refuse at runtime so the
		// always-registered tool can't queue work for an unconfigured provider.
		$config = new CloudflareConfig( '', '', '', 85, 'auto' );
		$tool   = new OffloadTool( new CloudflareOffloader( $config, new ImagesApiClient( $config ), new ImageIdStore(), new ProgressStore() ) );

		$data = $tool->handle()->get_data();

		$this->assertFalse( $data['started'] );
		$this->assertSame( 0, $data['queued'] );
	}
}
