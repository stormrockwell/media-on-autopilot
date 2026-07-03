<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\MediaTaxonomy;
use WP_UnitTestCase;

final class MediaTaxonomyTest extends WP_UnitTestCase {

	public function test_taxonomy_is_registered_for_attachments(): void {
		( new MediaTaxonomy() )->register_taxonomy();

		$this->assertTrue( taxonomy_exists( MediaTaxonomy::TAXONOMY ) );
		$taxonomy = get_taxonomy( MediaTaxonomy::TAXONOMY );
		$this->assertContains( 'attachment', $taxonomy->object_type );
		$this->assertFalse( $taxonomy->public );
		$this->assertTrue( $taxonomy->show_in_rest );
		$this->assertFalse( $taxonomy->hierarchical );
	}

	public function test_taxonomy_args_are_filterable(): void {
		add_filter(
			'moap_ai_tagging_taxonomy_args',
			static function ( array $args ): array {
				$args['show_admin_column'] = false;
				return $args;
			}
		);

		( new MediaTaxonomy() )->register_taxonomy();

		$this->assertFalse( get_taxonomy( MediaTaxonomy::TAXONOMY )->show_admin_column );
	}
}
