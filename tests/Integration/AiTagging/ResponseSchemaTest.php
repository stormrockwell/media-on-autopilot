<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\ResponseSchema;
use WP_UnitTestCase;

final class ResponseSchemaTest extends WP_UnitTestCase {

	public function test_schema_requires_alt_and_tags(): void {
		$schema = ( new ResponseSchema() )->schema();
		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'alt', $schema['properties'] );
		$this->assertArrayHasKey( 'tags', $schema['properties'] );
		$this->assertSame( 'array', $schema['properties']['tags']['type'] );
	}

	public function test_schema_forbids_additional_properties(): void {
		// Providers' strict structured-output mode (e.g. OpenAI) rejects the
		// request unless the object schema explicitly sets this to false.
		$schema = ( new ResponseSchema() )->schema();
		$this->assertArrayHasKey( 'additionalProperties', $schema );
		$this->assertFalse( $schema['additionalProperties'] );
	}

	public function test_prompt_is_filterable(): void {
		add_filter( 'moap_ai_tagging_prompt', static fn(): string => 'CUSTOM' );
		$this->assertSame( 'CUSTOM', ( new ResponseSchema() )->prompt() );
	}

	public function test_target_tag_count_defaults_to_twenty_and_is_filterable(): void {
		$this->assertSame( 20, ( new ResponseSchema() )->target_tag_count() );
		add_filter( 'moap_ai_tagging_target_tag_count', static fn(): int => 3 );
		$this->assertSame( 3, ( new ResponseSchema() )->target_tag_count() );
	}

	public function test_schema_includes_focal_object(): void {
		$schema = ( new \MediaOnAutopilot\Features\AiTagging\ResponseSchema() )->schema();
		$this->assertArrayHasKey( 'focal', $schema['properties'] );
		$this->assertContains( 'focal', $schema['required'] );
		$this->assertSame( false, $schema['properties']['focal']['additionalProperties'] );
		$this->assertEqualSets( array( 'x', 'y' ), $schema['properties']['focal']['required'] );
	}
}
