<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareOffloader;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImageIdStore;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImagesApiClient;
use MediaOnAutopilot\Support\Batch\ProgressState;
use MediaOnAutopilot\Support\Batch\ProgressStore;
use WP_UnitTestCase;

final class CloudflareOffloaderTest extends WP_UnitTestCase {

	private function offloader( string $account_hash = 'hash' ): CloudflareOffloader {
		$config = new CloudflareConfig( 'acct', 'token', $account_hash, 85, 'auto' );
		return new CloudflareOffloader( $config, new ImagesApiClient( $config ), new ImageIdStore(), new ProgressStore() );
	}

	public function test_handle_item_uploads_and_stores_id_keeping_local(): void {
		add_filter( 'pre_http_request', static fn() => array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => true, 'result' => array( 'id' => 'img-1', 'variants' => array() ) ) ),
		) );

		$id      = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$file    = get_attached_file( $id );
		$outcome = $this->offloader()->process_item( array( 'id' => $id ) );

		$this->assertSame( ProgressState::OUTCOME_WRITTEN, $outcome );
		$this->assertSame( 'img-1', ( new ImageIdStore() )->get( $id ) );
		// The offload is purely additive: the local original is always kept.
		$this->assertFileExists( $file );
	}

	public function test_handle_item_skips_already_offloaded_when_remote_present(): void {
		// The stored id is verified against the API before skipping; answer the
		// GET images/v1/{id} existence check with success.
		add_filter( 'pre_http_request', static fn() => array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => true, 'result' => array( 'id' => 'existing' ) ) ),
		) );

		$id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		( new ImageIdStore() )->set( $id, 'existing' );

		$this->assertSame( ProgressState::OUTCOME_SKIPPED, $this->offloader()->process_item( array( 'id' => $id ) ) );
		$this->assertSame( 'existing', ( new ImageIdStore() )->get( $id ) );
	}

	public function test_handle_item_reuploads_when_stored_id_missing_remote(): void {
		// First call (GET existence check) returns "not found"; the offloader then
		// clears the stale id and re-uploads (POST returns a fresh id).
		add_filter( 'pre_http_request', static function ( $pre, $args, $url ) {
			$method = strtoupper( (string) ( $args['method'] ?? 'GET' ) );
			if ( 'GET' === $method ) {
				return array(
					'response' => array( 'code' => 404 ),
					'body'     => wp_json_encode( array( 'success' => false, 'errors' => array( array( 'message' => 'not found' ) ) ) ),
				);
			}
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'success' => true, 'result' => array( 'id' => 'reuploaded', 'variants' => array() ) ) ),
			);
		}, 10, 3 );

		$id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		( new ImageIdStore() )->set( $id, 'stale-id' );

		$outcome = $this->offloader()->process_item( array( 'id' => $id ) );

		$this->assertSame( ProgressState::OUTCOME_WRITTEN, $outcome );
		$this->assertSame( 'reuploaded', ( new ImageIdStore() )->get( $id ) );
	}

	public function test_failed_upload_does_not_store_id(): void {
		add_filter( 'pre_http_request', static fn() => array(
			'response' => array( 'code' => 500 ),
			'body'     => wp_json_encode( array( 'success' => false, 'errors' => array( array( 'message' => 'boom' ) ) ) ),
		) );

		$id   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$file = get_attached_file( $id );
		$out  = $this->offloader()->process_item( array( 'id' => $id ) );

		$this->assertSame( ProgressState::OUTCOME_FAILED, $out );
		$this->assertSame( '', ( new ImageIdStore() )->get( $id ) );
		$this->assertFileExists( $file );
	}

	public function test_delete_sync_removes_remote_and_meta(): void {
		$deleted = false;
		add_filter( 'pre_http_request', static function () use ( &$deleted ) {
			$deleted = true;
			return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'success' => true, 'result' => array() ) ) );
		} );

		$id = self::factory()->attachment->create();
		( new ImageIdStore() )->set( $id, 'img-del' );
		$this->offloader()->on_delete( $id );

		$this->assertTrue( $deleted );
		$this->assertSame( '', ( new ImageIdStore() )->get( $id ) );
	}

	public function test_handle_item_captures_account_hash_when_config_hash_empty(): void {
		add_filter( 'pre_http_request', static fn() => array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => true, 'result' => array( 'id' => 'img-h', 'variants' => array( 'https://imagedelivery.net/CAPTURED9/img-h/public' ) ) ) ),
		) );

		$id      = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$outcome = $this->offloader( '' )->process_item( array( 'id' => $id ) );

		$this->assertSame( ProgressState::OUTCOME_WRITTEN, $outcome );
		$this->assertSame( 'CAPTURED9', get_option( 'moap_cloudflare_account_hash' ) );
	}

	public function test_on_delete_keeps_mapping_when_api_delete_fails(): void {
		add_filter( 'pre_http_request', static fn() => array(
			'response' => array( 'code' => 500 ),
			'body'     => wp_json_encode( array( 'success' => false, 'errors' => array( array( 'message' => 'boom' ) ) ) ),
		) );

		$id = self::factory()->attachment->create();
		( new ImageIdStore() )->set( $id, 'img-keep' );
		$this->offloader()->on_delete( $id );

		$this->assertSame( 'img-keep', ( new ImageIdStore() )->get( $id ) );
	}
}
