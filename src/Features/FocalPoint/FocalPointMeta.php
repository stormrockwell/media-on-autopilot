<?php
/**
 * Focal point attachment meta storage + REST exposure.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Stores and retrieves the focal point as attachment post meta,
 * and registers it for exposure via the WordPress REST API.
 */
final class FocalPointMeta {

	public const META_KEY = '_moap_focal_point';

	/**
	 * Registers the focal point meta key for the attachment post type.
	 *
	 * @return void
	 */
	public function register(): void {
		register_post_meta(
			'attachment',
			self::META_KEY,
			array(
				'type'          => 'object',
				'single'        => true,
				'show_in_rest'  => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'x' => array(
								'type'    => 'number',
								'minimum' => 0,
								'maximum' => 1,
							),
							'y' => array(
								'type'    => 'number',
								'minimum' => 0,
								'maximum' => 1,
							),
						),
					),
				),
				'auth_callback' => static function ( $allowed, $meta_key, $object_id ): bool {
					// Object-scoped: only users who can edit this specific attachment
					// may write its focal point (not any user with upload_files).
					return current_user_can( 'edit_post', (int) $object_id );
				},
			)
		);
	}

	/**
	 * Gets the focal point for an attachment, defaulting to center.
	 *
	 * @param int $attachment_id The attachment post ID.
	 * @return FocalPoint
	 */
	public function get( int $attachment_id ): FocalPoint {
		$raw = get_post_meta( $attachment_id, self::META_KEY, true );

		return is_array( $raw ) ? FocalPoint::from_array( $raw ) : FocalPoint::center();
	}

	/**
	 * Sets the focal point for an attachment.
	 *
	 * @param int        $attachment_id The attachment post ID.
	 * @param FocalPoint $point         The focal point to store.
	 * @return void
	 */
	public function set( int $attachment_id, FocalPoint $point ): void {
		update_post_meta( $attachment_id, self::META_KEY, $point->to_array() );
	}

	/**
	 * Checks whether a focal point is stored for the given attachment.
	 *
	 * @param int $attachment_id The attachment post ID.
	 * @return bool
	 */
	public function has( int $attachment_id ): bool {
		return is_array( get_post_meta( $attachment_id, self::META_KEY, true ) );
	}
}
