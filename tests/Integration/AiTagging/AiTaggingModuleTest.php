<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\AiTaggingModule;
use MediaOnAutopilot\Features\AiTagging\MediaTaxonomy;
use WP_UnitTestCase;

final class AiTaggingModuleTest extends WP_UnitTestCase {

	public function test_register_wires_core_hooks(): void {
		( new AiTaggingModule() )->register();

		$this->assertNotFalse( has_action( 'init' ) );
		$this->assertNotFalse( has_action( 'rest_api_init' ) );
		$this->assertNotFalse( has_filter( 'attachment_fields_to_edit' ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts' ) );
		$this->assertNotFalse( has_action( 'add_attachment' ) );
		$this->assertNotFalse( has_action( 'admin_init' ) );
		$this->assertNotFalse( has_filter( 'posts_search' ) );

		do_action( 'init' );
		$this->assertTrue( taxonomy_exists( MediaTaxonomy::TAXONOMY ) );
	}

	public function test_no_ai_bulk_actions_on_media_library(): void {
		( new \MediaOnAutopilot\Features\AiTagging\AiTaggingModule() )->register();

		$actions = apply_filters( 'bulk_actions-upload', array() );

		$this->assertArrayNotHasKey( 'moap_ai_tag', $actions );
		$this->assertArrayNotHasKey( 'moap_ai_tag_overwrite', $actions );
	}

	public function test_register_wires_batch_route(): void {
		( new AiTaggingModule() )->register();
		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/moap/v1/batch/ai_enrichment', $routes );
		$this->assertArrayHasKey( '/moap/v1/batch/ai_enrichment/cancel', $routes );
	}
}
