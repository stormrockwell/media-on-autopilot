<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\ImageResizer;
use MediaOnAutopilot\Features\AiTagging\MediaTaxonomy;
use MediaOnAutopilot\Features\AiTagging\ResponseSchema;
use MediaOnAutopilot\Features\AiTagging\SavePlan;
use MediaOnAutopilot\Features\AiTagging\Tagger;
use MediaOnAutopilot\Features\AiTagging\VisionClient;
use WP_UnitTestCase;

final class TaggerTest extends WP_UnitTestCase {

	private function fakeClient( array|\WP_Error $response ): VisionClient {
		return new class( $response ) implements VisionClient {
			public function __construct( private array|\WP_Error $response ) {}
			public function describe( string $file_path, string $prompt, array $schema ) {
				return $this->response;
			}
		};
	}

	private function imageAttachment(): int {
		return self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
	}

	private function tagger( VisionClient $client ): Tagger {
		return new Tagger(
			$client,
			new ImageResizer(),
			new ResponseSchema(),
			new \MediaOnAutopilot\Features\FocalPoint\FocalPointMeta()
		);
	}

	public function setUp(): void {
		parent::setUp();
		( new MediaTaxonomy() )->register_taxonomy();
	}

	public function test_applies_alt_and_merges_tags(): void {
		$id     = $this->imageAttachment();
		$client = $this->fakeClient( array( 'alt' => 'a red bicycle', 'tags' => array( 'bike', 'red' ), 'focal' => array( 'x' => 0.5, 'y' => 0.5 ) ) );

		$result = $this->tagger( $client )->tag( $id, SavePlan::overwrite( true, true, false ) );

		$this->assertTrue( $result->ok );
		$this->assertSame( 'Red bicycle', get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		$terms = wp_get_object_terms( $id, MediaTaxonomy::TAXONOMY, array( 'fields' => 'names' ) );
		$this->assertEqualSets( array( 'bike', 'red' ), $terms );
	}

	public function test_fill_missing_does_not_overwrite_existing_alt(): void {
		$id = $this->imageAttachment();
		update_post_meta( $id, '_wp_attachment_image_alt', 'Human written alt' );
		$client = $this->fakeClient( array( 'alt' => 'ai alt', 'tags' => array( 'x' ), 'focal' => array( 'x' => 0.5, 'y' => 0.5 ) ) );

		$this->tagger( $client )->tag( $id, SavePlan::fill_missing( true, true, false ) );

		$this->assertSame( 'Human written alt', get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	}

	public function test_writes_focal_when_requested(): void {
		$id   = $this->imageAttachment();
		$meta = new \MediaOnAutopilot\Features\FocalPoint\FocalPointMeta();
		$client = $this->fakeClient( array( 'alt' => 'x', 'tags' => array(), 'focal' => array( 'x' => 0.2, 'y' => 0.8 ) ) );

		$this->tagger( $client )->tag( $id, SavePlan::overwrite( false, false, true ) );

		$point = $meta->get( $id );
		$this->assertSame( 0.2, $point->x );
		$this->assertSame( 0.8, $point->y );
	}

	public function test_fill_missing_keeps_existing_focal(): void {
		$id   = $this->imageAttachment();
		$meta = new \MediaOnAutopilot\Features\FocalPoint\FocalPointMeta();
		$meta->set( $id, new \MediaOnAutopilot\Features\FocalPoint\FocalPoint( 0.1, 0.1 ) );
		$client = $this->fakeClient( array( 'alt' => 'x', 'tags' => array(), 'focal' => array( 'x' => 0.9, 'y' => 0.9 ) ) );

		$this->tagger( $client )->tag( $id, SavePlan::fill_missing( false, false, true ) );

		$this->assertSame( 0.1, $meta->get( $id )->x );
	}

	public function test_does_not_write_focal_when_not_requested(): void {
		$id   = $this->imageAttachment();
		$meta = new \MediaOnAutopilot\Features\FocalPoint\FocalPointMeta();
		$client = $this->fakeClient( array( 'alt' => 'x', 'tags' => array(), 'focal' => array( 'x' => 0.2, 'y' => 0.8 ) ) );

		$this->tagger( $client )->tag( $id, SavePlan::overwrite( true, false, false ) );

		$this->assertFalse( $meta->has( $id ) );
	}

	public function test_tags_merge_without_dropping_existing(): void {
		$id = $this->imageAttachment();
		wp_set_object_terms( $id, array( 'manual' ), MediaTaxonomy::TAXONOMY );
		$client = $this->fakeClient( array( 'alt' => 'x', 'tags' => array( 'ai' ), 'focal' => array( 'x' => 0.5, 'y' => 0.5 ) ) );

		$this->tagger( $client )->tag( $id, SavePlan::overwrite( true, true, false ) );

		$terms = wp_get_object_terms( $id, MediaTaxonomy::TAXONOMY, array( 'fields' => 'names' ) );
		$this->assertEqualSets( array( 'manual', 'ai' ), $terms );
	}

	public function test_returns_error_when_client_errors(): void {
		$id     = $this->imageAttachment();
		$client = $this->fakeClient( new \WP_Error( 'boom', 'nope' ) );

		$result = $this->tagger( $client )->tag( $id, SavePlan::overwrite( true, true, false ) );

		$this->assertFalse( $result->ok );
		$this->assertSame( 'boom', $result->error->get_error_code() );
	}

	public function test_result_filter_can_mutate_before_apply(): void {
		$id = $this->imageAttachment();
		add_filter(
			'moap_ai_tagging_result',
			static function ( $suggestion ) {
				return new \MediaOnAutopilot\Features\AiTagging\TagSuggestion( 'Filtered', array( 'filtered' ) );
			}
		);
		$client = $this->fakeClient( array( 'alt' => 'orig', 'tags' => array( 'orig' ), 'focal' => array( 'x' => 0.5, 'y' => 0.5 ) ) );

		$this->tagger( $client )->tag( $id, SavePlan::overwrite( true, true, false ) );

		$this->assertSame( 'Filtered', get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	}

	public function test_fill_missing_skips_when_all_fields_present(): void {
		$id   = $this->imageAttachment();
		$meta = new \MediaOnAutopilot\Features\FocalPoint\FocalPointMeta();

		update_post_meta( $id, '_wp_attachment_image_alt', 'existing alt' );
		$meta->set( $id, new \MediaOnAutopilot\Features\FocalPoint\FocalPoint( 0.5, 0.5 ) );
		wp_set_object_terms( $id, array( 'existing-tag' ), MediaTaxonomy::TAXONOMY );

		$called = false;
		$client = new class( $called ) implements VisionClient {
			public function __construct( private bool &$called ) {}
			public function describe( string $file_path, string $prompt, array $schema ) {
				$this->called = true;
				return new \WP_Error( 'should_not_be_called', 'Vision client must not be called when all fields are present.' );
			}
		};

		$result = $this->tagger( $client )->tag( $id, SavePlan::fill_missing( true, true, true ) );

		$this->assertTrue( $result->skipped );
		$this->assertFalse( $called );
	}

	public function test_overwrite_never_skips(): void {
		$id   = $this->imageAttachment();
		$meta = new \MediaOnAutopilot\Features\FocalPoint\FocalPointMeta();

		update_post_meta( $id, '_wp_attachment_image_alt', 'existing alt' );
		$meta->set( $id, new \MediaOnAutopilot\Features\FocalPoint\FocalPoint( 0.5, 0.5 ) );
		wp_set_object_terms( $id, array( 'existing-tag' ), MediaTaxonomy::TAXONOMY );

		$client = $this->fakeClient( array( 'alt' => 'new alt', 'tags' => array( 'new-tag' ), 'focal' => array( 'x' => 0.3, 'y' => 0.7 ) ) );

		$result = $this->tagger( $client )->tag( $id, SavePlan::overwrite( true, true, true ) );

		$this->assertFalse( $result->skipped );
	}
}
