<?php
/**
 * Stores the Cloudflare Images ID for an attachment.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Cloudflare;

defined( 'ABSPATH' ) || exit;

/**
 * Attachment ID to Cloudflare image ID mapping via attachment meta.
 */
final class ImageIdStore {

	public const META_KEY = '_moap_cloudflare_image_id';

	/**
	 * Retrieve the Cloudflare image ID for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string CF image id, or '' when not offloaded.
	 */
	public function get( int $attachment_id ): string {
		$value = get_post_meta( $attachment_id, self::META_KEY, true );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Store a Cloudflare image ID for an attachment.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $image_id      CF image id.
	 * @return void
	 */
	public function set( int $attachment_id, string $image_id ): void {
		update_post_meta( $attachment_id, self::META_KEY, $image_id );
	}

	/**
	 * Remove the stored Cloudflare image ID for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function clear( int $attachment_id ): void {
		delete_post_meta( $attachment_id, self::META_KEY );
	}
}
