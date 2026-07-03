<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\FocalPoint;

use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use MediaOnAutopilot\Features\FocalPoint\FocalPointModule;
use WP_UnitTestCase;

final class FocalPointModuleTest extends WP_UnitTestCase {

	public function test_register_wires_core_hooks(): void {
		( new FocalPointModule() )->register();

		$this->assertNotFalse( has_filter( 'image_resize_dimensions' ) );
		$this->assertNotFalse( has_filter( 'wp_get_attachment_image_attributes' ) );
		$this->assertNotFalse( has_action( 'updated_post_meta' ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts' ) );
		$this->assertNotFalse( has_filter( 'attachment_fields_to_edit' ) );

		$registered = get_registered_meta_keys( 'post', 'attachment' );
		$this->assertArrayHasKey( FocalPointMeta::META_KEY, $registered );
	}

	public function test_attachment_field_is_added(): void {
		( new FocalPointModule() )->register();
		$attachment_id = self::factory()->attachment->create(
			array(
				'post_mime_type' => 'image/jpeg',
				'file'           => DIR_TESTDATA . '/images/canola.jpg',
			)
		);

		$fields = apply_filters( 'attachment_fields_to_edit', array(), get_post( $attachment_id ) );
		$this->assertArrayHasKey( 'moap_focal_point', $fields );
		$this->assertStringContainsString( 'moap-focal-point', $fields['moap_focal_point']['html'] );
	}

	public function test_crop_hooks_skipped_when_disabled(): void {
		update_option( \MediaOnAutopilot\Features\FocalPoint\FocalPointSetting::OPTION, '0' );
		remove_all_filters( 'wp_get_attachment_image_attributes' );
		remove_all_filters( 'image_resize_dimensions' );

		( new \MediaOnAutopilot\Features\FocalPoint\FocalPointModule() )->register();

		// Frontend object-position filter is the observable signal of the crop path.
		$this->assertFalse( has_filter( 'wp_get_attachment_image_attributes' ) );
	}

	public function test_crop_hooks_registered_when_enabled(): void {
		update_option( \MediaOnAutopilot\Features\FocalPoint\FocalPointSetting::OPTION, '1' );
		remove_all_filters( 'wp_get_attachment_image_attributes' );

		( new \MediaOnAutopilot\Features\FocalPoint\FocalPointModule() )->register();

		$this->assertNotFalse( has_filter( 'wp_get_attachment_image_attributes' ) );
	}

	public function test_omits_focal_field_when_disabled(): void {
		update_option( \MediaOnAutopilot\Features\FocalPoint\FocalPointSetting::OPTION, '0' );
		remove_all_filters( 'attachment_fields_to_edit' );
		( new FocalPointModule() )->register();

		$attachment_id = self::factory()->attachment->create(
			array(
				'post_mime_type' => 'image/jpeg',
				'file'           => DIR_TESTDATA . '/images/canola.jpg',
			)
		);

		$fields = apply_filters( 'attachment_fields_to_edit', array(), get_post( $attachment_id ) );
		$this->assertArrayNotHasKey( 'moap_focal_point', $fields );
	}
}
