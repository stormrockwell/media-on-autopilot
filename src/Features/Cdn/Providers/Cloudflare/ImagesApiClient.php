<?php
/**
 * Thin Cloudflare Images REST client (URL ingest + delete).
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Cloudflare;

use MediaOnAutopilot\Features\Cdn\Verification\Verifiable;
use MediaOnAutopilot\Features\Cdn\Verification\VerificationResult;

defined( 'ABSPATH' ) || exit;

/**
 * Uploads (by URL) and deletes images via the Cloudflare Images API.
 */
final class ImagesApiClient implements Verifiable {

	/**
	 * Account hash captured from the last successful upload.
	 *
	 * @var string
	 */
	private string $last_hash = '';

	/**
	 * Sets up the client with resolved Cloudflare credentials.
	 *
	 * @param CloudflareConfig $config Credentials.
	 */
	public function __construct( private CloudflareConfig $config ) {}

	/**
	 * Upload a local image file's bytes; returns the CF image id.
	 *
	 * Sending the file contents (rather than a URL Cloudflare must fetch) means the
	 * origin does not need to be publicly reachable.
	 *
	 * @param string $file_path Local path to the image file.
	 * @return string
	 * @throws \RuntimeException On read, transport, or API failure.
	 */
	public function upload( string $file_path ): string {
		if ( ! is_readable( $file_path ) ) {
			throw new \RuntimeException( esc_html( 'Cloudflare upload could not read the local file.' ) );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local attachment file to upload its bytes.
		$bytes = file_get_contents( $file_path );
		if ( false === $bytes ) {
			throw new \RuntimeException( esc_html( 'Cloudflare upload could not read the local file.' ) );
		}

		// Strip CR/LF/quotes before interpolating into the multipart header (defense-in-depth
		// against header injection, even though the path is server-side sanitized already).
		$filename = str_replace( array( "\r", "\n", '"' ), '', basename( $file_path ) );
		$mime     = wp_check_filetype( $file_path )['type'];
		if ( ! is_string( $mime ) || '' === $mime ) {
			$mime = 'application/octet-stream';
		}

		// Cloudflare Images requires multipart/form-data; an array body would be sent
		// as application/x-www-form-urlencoded and rejected.
		$boundary = wp_generate_password( 24, false );
		$body     = '--' . $boundary . "\r\n"
			. 'Content-Disposition: form-data; name="file"; filename="' . $filename . '"' . "\r\n"
			. 'Content-Type: ' . $mime . "\r\n\r\n"
			. $bytes . "\r\n"
			. '--' . $boundary . '--' . "\r\n";

		$response = wp_remote_post(
			'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode( $this->config->account_id ) . '/images/v1',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->config->api_token,
					'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
				),
				'body'    => $body,
			)
		);

		$result = $this->decode( $response );
		$id     = $result['result']['id'] ?? '';
		if ( ! is_string( $id ) || '' === $id ) {
			throw new \RuntimeException( esc_html( 'Cloudflare upload returned no image id.' ) );
		}

		$variant = $result['result']['variants'][0] ?? '';
		if ( is_string( $variant ) && preg_match( '#imagedelivery\.net/([^/]+)/#', $variant, $m ) ) {
			$this->last_hash = $m[1];
		}

		return $id;
	}

	/**
	 * Delete an offloaded image.
	 *
	 * @param string $image_id CF image id.
	 * @return void
	 * @throws \RuntimeException On transport or API failure.
	 */
	public function delete( string $image_id ): void {
		$response = wp_remote_request(
			'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode( $this->config->account_id ) . '/images/v1/' . rawurlencode( $image_id ),
			array(
				'method'  => 'DELETE',
				'timeout' => 30,
				'headers' => array( 'Authorization' => 'Bearer ' . $this->config->api_token ),
			)
		);

		$this->decode( $response );
	}

	/**
	 * Whether an image still exists in Cloudflare (GET images/v1/{id}).
	 *
	 * Used to detect images deleted out-of-band so a stale local id can be cleared
	 * and the attachment re-uploaded. Returns true only on an explicit success
	 * response; transport errors return false (treat as "not present" so the
	 * offloader re-uploads rather than silently skipping a missing remote).
	 *
	 * @param string $image_id CF image id.
	 * @return bool
	 */
	public function exists( string $image_id ): bool {
		if ( '' === $image_id ) {
			return false;
		}

		$response = wp_remote_get(
			'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode( $this->config->account_id ) . '/images/v1/' . rawurlencode( $image_id ),
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $this->config->api_token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) && ! empty( $body['success'] );
	}

	/**
	 * Account hash parsed from the last successful upload's variant URL.
	 *
	 * @return string
	 */
	public function account_hash_from_last_upload(): string {
		return $this->last_hash;
	}

	/**
	 * Verify credentials by listing one image — validates the token, the account
	 * ID, and that Images is enabled, without uploading anything.
	 *
	 * @return VerificationResult
	 */
	public function verify(): VerificationResult {
		if ( '' === $this->config->account_id || '' === $this->config->api_token ) {
			return VerificationResult::unconfigured(
				__( 'Enter your Account ID and API token to connect Cloudflare Images.', 'media-on-autopilot' )
			);
		}

		$response = wp_remote_get(
			'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode( $this->config->account_id ) . '/images/v1?per_page=1',
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $this->config->api_token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			// Suppress the raw transport error; keep the user-facing message generic.
			return VerificationResult::error(
				__( 'Could not reach the Cloudflare API.', 'media-on-autopilot' ),
				__( 'The request to Cloudflare could not be completed.', 'media-on-autopilot' )
			);
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( is_array( $body ) && ! empty( $body['success'] ) ) {
			return VerificationResult::ok( __( 'Connected to Cloudflare Images.', 'media-on-autopilot' ) );
		}

		$message = is_array( $body ) ? ( $body['errors'][0]['message'] ?? '' ) : '';

		return VerificationResult::error(
			__( 'Cloudflare rejected the credentials.', 'media-on-autopilot' ),
			is_string( $message ) ? $message : ''
		);
	}

	/**
	 * Decode + validate a Cloudflare API response.
	 *
	 * @param array|\WP_Error $response Raw HTTP response.
	 * @return array<string, mixed>
	 * @throws \RuntimeException On transport or API failure.
	 */
	private function decode( array|\WP_Error $response ): array {
		if ( is_wp_error( $response ) ) {
			// Generic transport message — the raw WP_Error can leak internal network detail.
			throw new \RuntimeException( esc_html__( 'Could not reach the Cloudflare API.', 'media-on-autopilot' ) );
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['success'] ) ) {
			$message = is_array( $body ) ? ( $body['errors'][0]['message'] ?? 'unknown error' ) : 'invalid response';
			throw new \RuntimeException( esc_html( 'Cloudflare API error: ' . $message ) );
		}

		return $body;
	}
}
