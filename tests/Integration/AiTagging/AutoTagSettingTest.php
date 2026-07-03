<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\AutoTagSetting;
use WP_UnitTestCase;

final class AutoTagSettingTest extends WP_UnitTestCase {

	public function test_defaults_off(): void {
		$this->assertFalse( ( new AutoTagSetting() )->is_enabled() );
	}

	public function test_target_tag_count_filter_reads_option(): void {
		update_option( AutoTagSetting::TARGET_TAG_COUNT_OPTION, 4 );
		$setting = new AutoTagSetting();
		$this->assertSame( 4, $setting->filter_target_tag_count( 10 ) );
	}
}
