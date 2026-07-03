<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use PHPUnit\Framework\TestCase;

final class CloudflareConfigTest extends TestCase {

	public function test_active_requires_account_and_token(): void {
		$this->assertTrue( ( new CloudflareConfig( 'a', 't', 'h', 85, 'auto' ) )->is_active() );
		$this->assertFalse( ( new CloudflareConfig( '', 't', 'h', 85, 'auto' ) )->is_active() );
		$this->assertFalse( ( new CloudflareConfig( 'a', '', 'h', 85, 'auto' ) )->is_active() );
	}

	public function test_delivery_base_uses_hash(): void {
		$this->assertSame( 'https://imagedelivery.net/abc123', ( new CloudflareConfig( 'a', 't', 'abc123', 85, 'auto' ) )->delivery_base() );
	}
}
