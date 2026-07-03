<?php
/**
 * Re-runs WordPress's native attachment metadata generation so crop sizes
 * are rebuilt with the current focal point applied (via CropHandler).
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Not `final`: RegenerationListener tests substitute a subclassed double.
 */
class Regenerator {

	/**
	 * Re-generate attachment metadata so crop sizes reflect the current focal point.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return bool True when metadata was rebuilt, false for missing/nonexistent file.
	 */
	public function regenerate( int $attachment_id ): bool {
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
		if ( ! is_array( $metadata ) || empty( $metadata ) ) {
			return false;
		}

		wp_update_attachment_metadata( $attachment_id, $metadata );

		return true;
	}
}
