<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\AiTagging;

use MediaOnAutopilot\Features\AiTagging\AttachmentField;
use MediaOnAutopilot\Features\AiTagging\Connector;
use MediaOnAutopilot\Features\AiTagging\MediaTaxonomy;
use WP_UnitTestCase;

final class AttachmentFieldTest extends WP_UnitTestCase {

	private function field_for( int $id ): array {
		( new AttachmentField( new Connector() ) )->register();
		return apply_filters( 'attachment_fields_to_edit', array(), get_post( $id ) );
	}

	public function test_button_enabled_when_connector_available(): void {
		add_filter( 'moap_ai_tagging_connector_available', '__return_true' );
		$id     = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$fields = $this->field_for( $id );

		$this->assertArrayHasKey( 'moap_ai_tagging', $fields );
		$html = $fields['moap_ai_tagging']['html'];
		$this->assertStringContainsString( 'moap-ai-tagging__generate', $html );
		$this->assertStringNotContainsString( 'disabled', $html );
	}

	public function test_no_field_when_provider_unavailable(): void {
		add_filter( 'moap_ai_tagging_connector_available', '__return_false' );
		$id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		$this->assertArrayNotHasKey( 'moap_ai_tagging', $this->field_for( $id ) );
	}

	public function test_no_field_for_non_image(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'application/pdf' ) );
		$this->assertArrayNotHasKey( 'moap_ai_tagging', $this->field_for( $id ) );
	}

	public function test_field_is_inserted_directly_after_image_alt(): void {
		add_filter( 'moap_ai_tagging_connector_available', '__return_true' );
		$id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		( new AttachmentField( new Connector() ) )->register();

		$fields = apply_filters(
			'attachment_fields_to_edit',
			array(
				'post_title'   => array(),
				'image_alt'    => array(),
				'post_excerpt' => array(),
			),
			get_post( $id )
		);

		$keys    = array_keys( $fields );
		$alt_pos = array_search( 'image_alt', $keys, true );
		$this->assertIsInt( $alt_pos );
		$this->assertSame( 'moap_ai_tagging', $keys[ $alt_pos + 1 ] );
	}

	public function test_renders_current_tags(): void {
		add_filter( 'moap_ai_tagging_connector_available', '__return_true' );
		$id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		wp_set_object_terms( $id, array( 'beach', 'sunset' ), MediaTaxonomy::TAXONOMY );

		$html = $this->field_for( $id )['moap_ai_tagging']['html'];

		$this->assertStringContainsString( '<span class="moap-ai-tagging__chip">beach</span>', $html );
		$this->assertStringContainsString( '<span class="moap-ai-tagging__chip">sunset</span>', $html );
	}
}
