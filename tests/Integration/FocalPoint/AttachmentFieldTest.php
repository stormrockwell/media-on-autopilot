<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\AttachmentField;
use MediaOnAutopilot\Features\FocalPoint\FocalPoint;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use WP_UnitTestCase;

final class AttachmentFieldTest extends WP_UnitTestCase {

	private function image_attachment(): int {
		return self::factory()->attachment->create(
			array(
				'post_mime_type' => 'image/jpeg',
				'file'           => DIR_TESTDATA . '/images/canola.jpg',
			)
		);
	}

	public function test_renders_saved_focal_point(): void {
		$meta = new FocalPointMeta();
		$id   = $this->image_attachment();
		$meta->set( $id, new FocalPoint( 0.25, 0.75 ) );

		$fields = ( new AttachmentField( $meta ) )->add_field( array(), get_post( $id ) );
		$html   = $fields['moap_focal_point']['html'];

		$this->assertStringContainsString( 'data-x="0.25"', $html );
		$this->assertStringContainsString( 'data-y="0.75"', $html );
		// Marker pre-positioned server-side so there is no flash-of-center.
		$this->assertStringContainsString( 'left:25%;top:75%;', $html );
		$this->assertStringContainsString( 'moap-focal-point__save', $html );
		$this->assertStringContainsString( 'moap-focal-point__save" hidden>', $html );
	}

	public function test_defaults_to_center_when_no_focal_point(): void {
		$id     = $this->image_attachment();
		$fields = ( new AttachmentField( new FocalPointMeta() ) )->add_field( array(), get_post( $id ) );
		$html   = $fields['moap_focal_point']['html'];

		$this->assertStringContainsString( 'data-x="0.5"', $html );
		$this->assertStringContainsString( 'data-y="0.5"', $html );
	}

	public function test_skips_non_image_attachments(): void {
		$id     = self::factory()->attachment->create( array( 'post_mime_type' => 'application/pdf' ) );
		$fields = ( new AttachmentField( new FocalPointMeta() ) )->add_field( array(), get_post( $id ) );

		$this->assertArrayNotHasKey( 'moap_focal_point', $fields );
	}
}
