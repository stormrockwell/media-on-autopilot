<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use WP_UnitTestCase;

final class CloudflareConfigSanitizeTest extends WP_UnitTestCase {

	public function test_quality_clamped_and_format_validated(): void {
		$this->assertSame( 100, CloudflareConfig::sanitize_quality( 500 ) );
		$this->assertSame( 1, CloudflareConfig::sanitize_quality( 0 ) );
		$this->assertSame( 'auto', CloudflareConfig::sanitize_format( 'nonsense' ) );
		$this->assertSame( 'avif', CloudflareConfig::sanitize_format( 'avif' ) );
	}
}
