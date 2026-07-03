<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn\Bunny;

use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyConfig;
use PHPUnit\Framework\TestCase;

final class BunnyConfigTest extends TestCase {

	public function test_is_active_requires_host(): void {
		$this->assertTrue( ( new BunnyConfig( 'x.b-cdn.net', 85, 'auto' ) )->is_active() );
		$this->assertFalse( ( new BunnyConfig( '', 85, 'auto' ) )->is_active() );
	}

	public function test_sanitize_host_strips_scheme_path_and_trailing_slash(): void {
		$this->assertSame( 'x.b-cdn.net', BunnyConfig::sanitize_host( 'https://x.b-cdn.net/' ) );
		$this->assertSame( 'x.b-cdn.net', BunnyConfig::sanitize_host( 'http://x.b-cdn.net/path/' ) );
		$this->assertSame( 'x.b-cdn.net', BunnyConfig::sanitize_host( '  x.b-cdn.net  ' ) );
		$this->assertSame( '', BunnyConfig::sanitize_host( '' ) );
	}
}
