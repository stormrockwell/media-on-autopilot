<?php
/**
 * Adds the focal point picker container to attachment edit fields.
 * JS (picker.js) finds the container and renders the interactive UI.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the focal point picker field to the attachment edit form.
 */
final class AttachmentField {

	/**
	 * Constructor.
	 *
	 * @param FocalPointMeta $meta Focal point meta store.
	 */
	public function __construct( private FocalPointMeta $meta ) {}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_field' ), 10, 2 );
	}

	/**
	 * Add the focal point picker field to the attachment form.
	 *
	 * @param array<string, array<string, string>> $form_fields Existing form fields.
	 * @param \WP_Post                             $post        Attachment post object.
	 * @return array<string, array<string, string>>
	 */
	public function add_field( array $form_fields, \WP_Post $post ): array {
		if ( ! wp_attachment_is_image( $post->ID ) ) {
			return $form_fields;
		}

		$focal = $this->meta->get( $post->ID );

		$image = wp_get_attachment_image(
			$post->ID,
			'medium',
			false,
			array( 'class' => 'moap-focal-point__image' )
		);

		$form_fields['moap_focal_point'] = array(
			'label' => __( 'Focal Point', 'media-on-autopilot' ),
			'input' => 'html',
			'html'  => sprintf(
				'<div class="moap-focal-point" data-attachment-id="%1$d" data-x="%2$s" data-y="%3$s">'
					. '<div class="moap-focal-point__stage">%4$s'
					. '<span class="moap-focal-point__marker" style="left:%5$d%%;top:%6$d%%;" aria-hidden="true"></span>'
					. '</div>'
					. '<div class="moap-focal-point__controls">'
					. '<button type="button" class="button moap-focal-point__save" hidden>%7$s</button>'
					. '<span class="moap-focal-point__status" role="status" aria-live="polite"></span>'
					. '</div>'
					. '</div>',
				(int) $post->ID,
				esc_attr( (string) $focal->x ),
				esc_attr( (string) $focal->y ),
				$image,
				$focal->x_percent(),
				$focal->y_percent(),
				esc_html__( 'Save focal point', 'media-on-autopilot' )
			),
		);

		return $form_fields;
	}
}
