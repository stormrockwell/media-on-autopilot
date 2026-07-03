<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\ImageTransform;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareOptions;
use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use PHPUnit\Framework\TestCase;

final class CloudflareOptionsTest extends TestCase {

	private function config( string $format = 'auto', int $quality = 85 ): CloudflareConfig {
		return new CloudflareConfig( 'acct', 'token', 'hash', $quality, $format );
	}

	public function test_non_crop_emits_width_quality_format(): void {
		$opts = CloudflareOptions::serialize( ImageTransform::center( 1, 600 ), $this->config() );
		$this->assertSame( 'w=600,quality=85,format=auto', $opts );
	}

	public function test_crop_emits_fit_cover_and_gravity(): void {
		$t    = new ImageTransform( 1, 300, 300, true, new FocalPoint( 0.8169, 0.4625 ), 1200, 900 );
		$opts = CloudflareOptions::serialize( $t, $this->config( 'off' ) );
		$this->assertSame( 'w=300,h=300,fit=cover,gravity=0.8169x0.4625', $opts );
	}

	public function test_off_format_omits_quality_and_format(): void {
		$opts = CloudflareOptions::serialize( ImageTransform::center( 1, 600 ), $this->config( 'off' ) );
		$this->assertSame( 'w=600', $opts );
	}

	public function test_webp_format_is_explicit(): void {
		$opts = CloudflareOptions::serialize( ImageTransform::center( 1, 400 ), $this->config( 'webp' ) );
		$this->assertSame( 'w=400,quality=85,format=webp', $opts );
	}
}
