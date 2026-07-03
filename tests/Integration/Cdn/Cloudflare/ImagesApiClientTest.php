<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn\Cloudflare;

use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImagesApiClient;
use WP_UnitTestCase;

final class ImagesApiClientTest extends WP_UnitTestCase {

	private function client(): ImagesApiClient {
		return new ImagesApiClient( new CloudflareConfig( 'acct', 'token', '', 85, 'auto' ) );
	}

	public function test_upload_returns_image_id_and_captures_hash(): void {
		$captured = array();
		add_filter(
			'pre_http_request',
			static function ( $pre, $args ) use ( &$captured ) {
				$captured = $args;
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'success' => true,
							'result'  => array(
								'id'       => 'img-xyz',
								'variants' => array( 'https://imagedelivery.net/HASH42/img-xyz/public' ),
							),
						)
					),
				);
			},
			10,
			3
		);

		$client = $this->client();
		$this->assertSame( 'img-xyz', $client->upload( DIR_TESTDATA . '/images/canola.jpg' ) );
		$this->assertSame( 'HASH42', $client->account_hash_from_last_upload() );

		// Cloudflare requires multipart/form-data with the file bytes, not urlencoded.
		$this->assertStringStartsWith( 'multipart/form-data; boundary=', $captured['headers']['Content-Type'] );
		$this->assertStringContainsString( 'Content-Disposition: form-data; name="file"; filename="canola.jpg"', $captured['body'] );
		$this->assertStringContainsString( 'Content-Type: image/jpeg', $captured['body'] );
	}

	public function test_upload_throws_when_file_unreadable(): void {
		$this->expectException( \RuntimeException::class );
		$this->client()->upload( '/no/such/file/missing.jpg' );
	}

	public function test_upload_throws_on_api_error(): void {
		add_filter(
			'pre_http_request',
			static fn() => array(
				'response' => array( 'code' => 401 ),
				'body'     => wp_json_encode( array( 'success' => false, 'errors' => array( array( 'message' => 'bad token' ) ) ) ),
			)
		);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/bad token/' );
		$this->client()->upload( DIR_TESTDATA . '/images/canola.jpg' );
	}

	public function test_delete_succeeds_on_ok_response(): void {
		$called = false;
		add_filter(
			'pre_http_request',
			static function () use ( &$called ) {
				$called = true;
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'success' => true, 'result' => array() ) ),
				);
			}
		);

		$this->client()->delete( 'img-1' );
		$this->assertTrue( $called );
	}

	public function test_delete_throws_on_api_error(): void {
		add_filter(
			'pre_http_request',
			static fn() => array(
				'response' => array( 'code' => 401 ),
				'body'     => wp_json_encode( array( 'success' => false, 'errors' => array( array( 'message' => 'bad token' ) ) ) ),
			)
		);

		$this->expectException( \RuntimeException::class );
		$this->client()->delete( 'img-1' );
	}
}
