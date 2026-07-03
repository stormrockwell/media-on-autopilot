<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\MediaSearch;
use MediaOnAutopilot\Features\AiTagging\MediaTaxonomy;
use WP_Query;
use WP_UnitTestCase;

final class MediaSearchTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		( new MediaTaxonomy() )->register_taxonomy();
		( new MediaSearch() )->register();
	}

	public function test_search_matches_by_tag_term(): void {
		$id = self::factory()->attachment->create(
			array(
				'post_title'     => 'DSC_0001',
				'post_mime_type' => 'image/jpeg',
			)
		);
		wp_set_object_terms( $id, array( 'sunset' ), MediaTaxonomy::TAXONOMY );

		$query = new WP_Query(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				's'           => 'sunset',
				'fields'      => 'ids',
			)
		);

		$this->assertContains( $id, $query->posts );
	}

	public function test_unrelated_search_does_not_match(): void {
		$id = self::factory()->attachment->create(
			array(
				'post_title'     => 'DSC_0002',
				'post_mime_type' => 'image/jpeg',
			)
		);
		wp_set_object_terms( $id, array( 'mountain' ), MediaTaxonomy::TAXONOMY );

		$query = new WP_Query(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				's'           => 'sunset',
				'fields'      => 'ids',
			)
		);

		$this->assertNotContains( $id, $query->posts );
	}
}
