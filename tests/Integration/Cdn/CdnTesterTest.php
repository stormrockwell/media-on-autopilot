<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Cdn;

use MediaOnAutopilot\Features\Cdn\CdnSettings;
use MediaOnAutopilot\Features\Cdn\CdnTester;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use WP_UnitTestCase;

final class CdnTesterTest extends WP_UnitTestCase {

	private function tester( callable $fetch ): CdnTester {
		return new CdnTester( new CdnSettings(), new BunnySettings(), new \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings(), $fetch );
	}

	public function test_unconfigured_when_provider_none(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'none' );
		$result = $this->tester( static fn() => array( 'status' => 200, 'format' => 'webp' ) )->run();
		$this->assertSame( 'unconfigured', $result['state'] );
	}

	public function test_bunny_pass_records_last_test(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'bunny' );
		update_option( BunnySettings::OPTION_HOST, 'mysite.b-cdn.net' );

		$result = $this->tester( static fn() => array( 'status' => 200, 'format' => 'webp' ) )->run();

		$this->assertSame( 'ok', $result['state'] );
		$this->assertSame( 'webp', $result['format'] );
		$this->assertSame( 'ok', get_option( CdnTester::LAST_TEST_OPTION )['state'] );
		$this->assertNotEmpty( $result['steps'] );
	}

	public function test_bunny_http_failure_is_error_not_exception(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'bunny' );
		update_option( BunnySettings::OPTION_HOST, 'mysite.b-cdn.net' );

		$result = $this->tester( static fn() => array( 'status' => 502, 'format' => '' ) )->run();

		$this->assertSame( 'error', $result['state'] );
	}

	/**
	 * Intercept the Cloudflare REST calls: answer every upload POST with a fresh
	 * image id (so each test uploads a distinct image) + a variant, and record
	 * each DELETE url.
	 *
	 * @param array<int,string> $deleted   Captured DELETE urls (by reference).
	 * @param string            $upload_id Image id to return from the upload POST.
	 * @return callable The pre_http_request filter.
	 */
	private function cloudflare_intercept( array &$deleted, string $upload_id ): callable {
		return function ( $pre, $args, $url ) use ( &$deleted, $upload_id ) {
			$method = strtoupper( (string) ( $args['method'] ?? 'GET' ) );

			if ( 'DELETE' === $method ) {
				$deleted[] = $url;
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'success' => true ) ),
					'headers'  => array(),
				);
			}

			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'success' => true,
						'result'  => array(
							'id'       => $upload_id,
							'variants' => array( 'https://imagedelivery.net/HASH123/' . $upload_id . '/public' ),
						),
					)
				),
				'headers'  => array(),
			);
		};
	}

	/**
	 * The cloudflare test must upload an image, verify delivery, then DELETE it
	 * within the same request so nothing lingers in the user's account.
	 */
	public function test_cloudflare_deletes_uploaded_image_after_test(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'cloudflare' );
		update_option( CloudflareSettings::OPTION_ACCOUNT, 'acct123' );
		update_option( CloudflareSettings::OPTION_TOKEN, 'tok123' );

		$deleted   = array();
		$intercept = $this->cloudflare_intercept( $deleted, 'test-image-id' );
		add_filter( 'pre_http_request', $intercept, 10, 3 );

		try {
			$result = $this->tester( static fn() => array( 'status' => 200, 'format' => 'webp' ) )->run();
		} finally {
			remove_filter( 'pre_http_request', $intercept, 10 );
		}

		$this->assertSame( 'ok', $result['state'] );

		// The uploaded image must be deleted in the same request.
		$this->assertNotEmpty( $deleted, 'The just-uploaded test image must be deleted before returning.' );
		$this->assertStringContainsString( 'test-image-id', $deleted[0] );

		// Steps must include a deletion step with status ok.
		$deleteStep = null;
		foreach ( $result['steps'] as $step ) {
			if ( str_contains( $step['label'], 'eleted' ) ) {
				$deleteStep = $step;
				break;
			}
		}
		$this->assertNotNull( $deleteStep, 'A deletion step must be present in the steps array.' );
		$this->assertSame( 'ok', $deleteStep['status'] );
	}

	/**
	 * Cleanup (delete) must happen even when the delivery fetch returns a non-200.
	 */
	public function test_cloudflare_deletes_image_even_when_delivery_fails(): void {
		update_option( CdnSettings::OPTION_PROVIDER, 'cloudflare' );
		update_option( CloudflareSettings::OPTION_ACCOUNT, 'acct123' );
		update_option( CloudflareSettings::OPTION_TOKEN, 'tok123' );

		$deleted   = array();
		$intercept = $this->cloudflare_intercept( $deleted, 'fail-image-id' );
		add_filter( 'pre_http_request', $intercept, 10, 3 );

		try {
			$result = $this->tester( static fn() => array( 'status' => 404, 'format' => '' ) )->run();
		} finally {
			remove_filter( 'pre_http_request', $intercept, 10 );
		}

		$this->assertSame( 'error', $result['state'] );

		// Cleanup must still happen even on delivery failure.
		$this->assertNotEmpty( $deleted, 'The uploaded test image must be deleted even when delivery fails.' );
		$this->assertStringContainsString( 'fail-image-id', $deleted[0] );
	}
}
