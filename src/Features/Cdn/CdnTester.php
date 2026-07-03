<?php
/**
 * Runs the end-to-end CDN delivery test for the selected provider.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn;

use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyProvider;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Support\SampleImage;

defined( 'ABSPATH' ) || exit;

/**
 * Generates → (uploads) → fetches a variant and reports the round-trip.
 */
final class CdnTester {

	public const LAST_TEST_OPTION = 'moap_cdn_last_test';

	/**
	 * Sets up the tester.
	 *
	 * @param CdnSettings        $selector   Provider selector.
	 * @param BunnySettings      $bunny      Bunny config resolver.
	 * @param CloudflareSettings $cloudflare Cloudflare config resolver.
	 * @param callable           $fetch      fn(string $url): array{status:int,format:string}.
	 */
	public function __construct(
		private CdnSettings $selector,
		private BunnySettings $bunny,
		private CloudflareSettings $cloudflare,
		private $fetch
	) {}

	/**
	 * Default fetch seam: GET the URL and read its content type.
	 *
	 * @param string $url Delivery URL.
	 * @return array{status:int,format:string}
	 */
	public static function http_fetch( string $url ): array {
		// Delivery URL is built from an admin-settable host; wp_safe_remote_get
		// blocks private/reserved targets (SSRF hardening).
		$response = wp_safe_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 0,
				'format' => '',
			);
		}
		$type   = (string) wp_remote_retrieve_header( $response, 'content-type' );
		$format = '';
		if ( preg_match( '#image/(\w+)#', $type, $m ) ) {
			$format = $m[1];
		}
		return array(
			'status' => (int) wp_remote_retrieve_response_code( $response ),
			'format' => $format,
		);
	}

	/**
	 * Run the test for the selected provider.
	 *
	 * @return array{state:string,ms:int,format:string,message:string,detail:string,steps:array<int,array{label:string,status:string}>}
	 */
	public function run(): array {
		$start = microtime( true );
		$steps = array();

		switch ( $this->selector->current() ) {
			case 'bunny':
				$config = $this->bunny->to_config();
				if ( ! $config->is_active() ) {
					return $this->record( 'unconfigured', 0, '', __( 'Add your pull-zone hostname first.', 'media-on-autopilot' ), '', array() );
				}
				$origin = MOAP_PLUGIN_URL . 'assets/sample-image.jpg';
				$url    = ( new BunnyProvider( $config ) )->build_url(
					$origin,
					new ImageTransform( 0, 600, 0, false, null, 1024, 768 )
				);

				$fetched = call_user_func( $this->fetch, $url );
				$ms      = (int) round( ( microtime( true ) - $start ) * 1000 );
				$status  = (int) ( $fetched['status'] ?? 0 );
				$format  = (string) ( $fetched['format'] ?? '' );

				$steps[] = array(
					'label'  => __( 'Requested resized variant from your pull zone', 'media-on-autopilot' ),
					'status' => 'ok',
				);

				if ( 200 !== $status ) {
					$steps[] = array(
						/* translators: %d: HTTP status code */
						'label'  => sprintf( __( 'Variant did not load (HTTP %d) — is your pull-zone origin publicly reachable?', 'media-on-autopilot' ), $status ),
						'status' => 'fail',
					);
					return $this->record( 'error', $ms, '', __( 'The delivered image did not load.', 'media-on-autopilot' ), 'HTTP ' . $status, $steps );
				}

				$steps[] = array(
					/* translators: 1: HTTP status code, 2: image format (e.g. webp), 3: elapsed milliseconds */
					'label'  => sprintf( __( 'Delivered (HTTP %1$d · %2$s · %3$d ms)', 'media-on-autopilot' ), $status, $format, $ms ),
					'status' => 'ok',
				);
				return $this->record( 'ok', $ms, $format, __( 'Delivery passed.', 'media-on-autopilot' ), '', $steps );

			case 'cloudflare':
				$config = $this->cloudflare->to_config();
				if ( ! $config->is_active() ) {
					return $this->record( 'unconfigured', 0, '', __( 'Add your Account ID and API token first.', 'media-on-autopilot' ), '', array() );
				}

				$api = new Providers\Cloudflare\ImagesApiClient( $config );

				try {
					$image_id = $api->upload( SampleImage::path() );
				} catch ( \RuntimeException $e ) {
					$steps[] = array(
						'label'  => __( 'Upload to Cloudflare failed', 'media-on-autopilot' ),
						'status' => 'fail',
					);
					return $this->record( 'error', 0, '', __( 'Cloudflare upload failed.', 'media-on-autopilot' ), $e->getMessage(), $steps );
				}

				$steps[] = array(
					'label'  => __( 'Uploaded test image to Cloudflare', 'media-on-autopilot' ),
					'status' => 'ok',
				);

				$hash    = '' !== $config->account_hash ? $config->account_hash : $api->account_hash_from_last_upload();
				$url     = 'https://imagedelivery.net/' . rawurlencode( $hash ) . '/' . rawurlencode( $image_id ) . '/w=600';
				$fetched = call_user_func( $this->fetch, $url );
				$ms      = (int) round( ( microtime( true ) - $start ) * 1000 );
				$status  = (int) ( $fetched['status'] ?? 0 );
				$format  = (string) ( $fetched['format'] ?? '' );

				if ( 200 === $status ) {
					$steps[] = array(
						/* translators: 1: HTTP status code, 2: image format (e.g. webp), 3: elapsed milliseconds */
						'label'  => sprintf( __( 'Delivered resized variant (HTTP %1$d · %2$s · %3$d ms)', 'media-on-autopilot' ), $status, $format, $ms ),
						'status' => 'ok',
					);
				} else {
					$steps[] = array(
						/* translators: %d: HTTP status code */
						'label'  => sprintf( __( 'Variant did not load (HTTP %d)', 'media-on-autopilot' ), $status ),
						'status' => 'fail',
					);
				}

				// Always clean up the test image regardless of fetch outcome.
				try {
					$api->delete( $image_id );
					$steps[] = array(
						'label'  => __( 'Deleted test image from Cloudflare', 'media-on-autopilot' ),
						'status' => 'ok',
					);
				} catch ( \RuntimeException $e ) {
					$steps[] = array(
						/* translators: %s: Cloudflare image ID */
						'label'  => sprintf( __( 'Could not delete test image (%s) — remove it manually', 'media-on-autopilot' ), $image_id ),
						'status' => 'warn',
					);
				}

				if ( 200 !== $status ) {
					return $this->record( 'error', $ms, '', __( 'The delivered image did not load.', 'media-on-autopilot' ), 'HTTP ' . $status, $steps );
				}
				return $this->record( 'ok', $ms, $format, __( 'Delivery passed.', 'media-on-autopilot' ), '', $steps );

			default:
				return $this->record( 'unconfigured', 0, '', __( 'Select a CDN provider first.', 'media-on-autopilot' ), '', array() );
		}
	}

	/**
	 * Persist + return a result.
	 *
	 * @param string                                       $state   ok|error|unconfigured.
	 * @param int                                          $ms      Elapsed ms.
	 * @param string                                       $format  Served format.
	 * @param string                                       $message Human message.
	 * @param string                                       $detail  Extra detail.
	 * @param array<int,array{label:string,status:string}> $steps   Step log.
	 * @return array{state:string,ms:int,format:string,message:string,detail:string,steps:array<int,array{label:string,status:string}>}
	 */
	private function record( string $state, int $ms, string $format, string $message, string $detail, array $steps ): array {
		update_option(
			self::LAST_TEST_OPTION,
			array(
				'state'   => $state,
				'message' => $message,
				'time'    => time(),
			)
		);

		return array(
			'state'   => $state,
			'ms'      => $ms,
			'format'  => $format,
			'message' => $message,
			'detail'  => $detail,
			'steps'   => $steps,
		);
	}
}
