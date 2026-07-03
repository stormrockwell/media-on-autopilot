<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\AutoTagOnUpload;
use MediaOnAutopilot\Features\AiTagging\AutoTagSetting;
use MediaOnAutopilot\Features\AiTagging\Connector;
use MediaOnAutopilot\Features\AiTagging\ImageResizer;
use MediaOnAutopilot\Features\AiTagging\MediaTaxonomy;
use MediaOnAutopilot\Features\AiTagging\ResponseSchema;
use MediaOnAutopilot\Features\AiTagging\Tagger;
use MediaOnAutopilot\Features\AiTagging\VisionClient;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use WP_UnitTestCase;

final class AutoTagOnUploadTest extends WP_UnitTestCase {

	private function tagger(): Tagger {
		$client = new class() implements VisionClient {
			public function describe( string $file_path, string $prompt, array $schema ) {
				return array(
					'alt'   => 'auto alt',
					'tags'  => array( 'auto' ),
					'focal' => array(
						'x' => 0.5,
						'y' => 0.5,
					),
				);
			}
		};
		return new Tagger( $client, new ImageResizer(), new ResponseSchema(), new FocalPointMeta() );
	}

	public function setUp(): void {
		parent::setUp();
		( new MediaTaxonomy() )->register_taxonomy();
		add_filter( 'moap_ai_tagging_connector_available', '__return_true' );
	}

	public function test_no_tagging_when_setting_off(): void {
		update_option( AutoTagSetting::OPTION, '0' );
		$auto = new AutoTagOnUpload( $this->tagger(), new AutoTagSetting(), new Connector() );
		$id   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		$auto->maybe_tag( $id );

		$this->assertSame( '', (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	}

	public function test_fills_empty_alt_when_setting_on(): void {
		update_option( AutoTagSetting::OPTION, '1' );
		$auto = new AutoTagOnUpload( $this->tagger(), new AutoTagSetting(), new Connector() );
		$id   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		$auto->maybe_tag( $id );

		$this->assertSame( 'Auto alt', get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		$terms = wp_get_object_terms( $id, MediaTaxonomy::TAXONOMY, array( 'fields' => 'names' ) );
		$this->assertContains( 'auto', $terms );
	}

	public function test_no_tagging_when_connector_unavailable(): void {
		remove_all_filters( 'moap_ai_tagging_connector_available' );
		add_filter( 'moap_ai_tagging_connector_available', '__return_false' );
		update_option( AutoTagSetting::OPTION, '1' );
		$auto = new AutoTagOnUpload( $this->tagger(), new AutoTagSetting(), new Connector() );
		$id   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		$auto->maybe_tag( $id );

		$this->assertSame( '', (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	}

	public function test_no_tagging_for_non_image(): void {
		update_option( AutoTagSetting::OPTION, '1' );
		$auto = new AutoTagOnUpload( $this->tagger(), new AutoTagSetting(), new Connector() );
		$id   = self::factory()->attachment->create( array( 'post_mime_type' => 'application/pdf' ) );

		$auto->maybe_tag( $id );

		$this->assertSame( '', (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	}

	public function test_writes_focal_when_setting_on(): void {
		update_option( AutoTagSetting::OPTION, '1' );
		$auto = new AutoTagOnUpload( $this->tagger(), new AutoTagSetting(), new Connector() );
		$id   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		$auto->maybe_tag( $id );

		$this->assertTrue( ( new FocalPointMeta() )->has( $id ) );
	}
}
