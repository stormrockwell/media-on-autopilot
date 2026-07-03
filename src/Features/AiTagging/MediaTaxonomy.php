<?php
/**
 * Registers the media tag taxonomy used by AI tagging.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `moap_media_tag` taxonomy on the attachment post type.
 */
final class MediaTaxonomy {

	public const TAXONOMY = 'moap_media_tag';

	/**
	 * Hook taxonomy registration onto init.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		$args = array(
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => false,
			'rewrite'           => false,
			'labels'            => array(
				'name'          => __( 'Media Tags', 'media-on-autopilot' ),
				'singular_name' => __( 'Media Tag', 'media-on-autopilot' ),
			),
		);

		register_taxonomy(
			self::TAXONOMY,
			'attachment',
			apply_filters( 'moap_ai_tagging_taxonomy_args', $args )
		);
	}
}
