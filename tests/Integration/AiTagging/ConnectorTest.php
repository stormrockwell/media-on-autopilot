<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\Connector;
use WP_UnitTestCase;

final class ConnectorTest extends WP_UnitTestCase {

	public function test_availability_is_filterable(): void {
		add_filter( 'moap_ai_tagging_connector_available', '__return_true' );
		$this->assertTrue( ( new Connector() )->is_available() );

		remove_filter( 'moap_ai_tagging_connector_available', '__return_true' );
		add_filter( 'moap_ai_tagging_connector_available', '__return_false' );
		$this->assertFalse( ( new Connector() )->is_available() );
	}

	public function test_settings_url_is_filterable(): void {
		add_filter( 'moap_ai_tagging_settings_url', static fn(): string => 'https://example.test/ai' );
		$this->assertSame( 'https://example.test/ai', ( new Connector() )->settings_url() );
	}
}
