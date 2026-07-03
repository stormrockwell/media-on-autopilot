<?php
/**
 * Resizes an attachment to a temp JPEG for token-efficient vision calls.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Produces a downscaled temp JPEG from an attachment.
 */
final class ImageResizer {

	/**
	 * Resize the attachment's original to a temp JPEG.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string|\WP_Error Absolute temp-file path, or error.
	 */
	public function resize( int $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return new \WP_Error(
				'moap_ai_no_file',
				__( 'The attachment file could not be found.', 'media-on-autopilot' )
			);
		}

		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		$max = (int) apply_filters( 'moap_ai_tagging_resize_max_edge', 512 );
		$editor->resize( $max, $max, false );

		// wp_tempnam() lives in wp-admin/includes/file.php, which is not loaded
		// during REST (button) or cron (background queue) requests.
		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$tmp   = wp_tempnam( 'moap-ai-tagging-' . $attachment_id . '.jpg' );
		$saved = $editor->save( $tmp, 'image/jpeg' );
		if ( is_wp_error( $saved ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return $saved;
		}

		// wp_tempnam created a placeholder; the editor wrote to $saved['path'].
		if ( $saved['path'] !== $tmp && file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}

		return $saved['path'];
	}
}
