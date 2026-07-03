<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Support;

use MediaOnAutopilot\Support\SampleImage;
use WP_UnitTestCase;

final class SampleImageTest extends WP_UnitTestCase {
	public function test_path_is_readable_image(): void {
		$path = SampleImage::path();
		$this->assertTrue( is_readable( $path ) );
		$this->assertNotFalse( getimagesize( $path ) );
	}
}
