<?php
/**
 * Adds the "Generate alt text & tags" button to the attachment editor.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the AI tagging button field; JS (button.js) wires the click.
 */
final class AttachmentField {

	/**
	 * Constructor.
	 *
	 * @param Connector $connector Availability gate.
	 */
	public function __construct( private Connector $connector ) {}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_field' ), 10, 2 );
	}

	/**
	 * Add the button field.
	 *
	 * @param array<string, array<string, string>> $form_fields Existing fields.
	 * @param \WP_Post                             $post        Attachment.
	 * @return array<string, array<string, string>>
	 */
	public function add_field( array $form_fields, \WP_Post $post ): array {
		if ( ! wp_attachment_is_image( $post->ID ) ) {
			return $form_fields;
		}

		if ( ! $this->connector->is_available() ) {
			return $form_fields;
		}

		$button = sprintf(
			'<button type="button" class="button moap-ai-tagging__generate" data-attachment-id="%1$d">%2$s</button>',
			(int) $post->ID,
			esc_html__( 'Generate alt text & tags', 'media-on-autopilot' )
		);

		$note = '<span class="moap-ai-tagging__status" role="status" aria-live="polite"></span>';

		$tags_row = sprintf(
			'<div class="moap-ai-tagging__tags"><span class="moap-ai-tagging__tags-label">%1$s</span> <span class="moap-ai-tagging__tag-list">%2$s</span></div>',
			esc_html__( 'Tags:', 'media-on-autopilot' ),
			$this->render_chips( $this->current_tags( $post->ID ) )
		);

		$field = array(
			'label' => __( 'AI Tagging', 'media-on-autopilot' ),
			'input' => 'html',
			'html'  => '<div class="moap-ai-tagging">' . $button . ' ' . $note . $tags_row . '</div>',
		);

		return $this->insert_after_alt( $form_fields, $field );
	}

	/**
	 * Render tag names as chip spans.
	 *
	 * @param string[] $names Tag names.
	 * @return string
	 */
	private function render_chips( array $names ): string {
		return implode(
			'',
			array_map(
				static fn( string $name ): string => sprintf(
					'<span class="moap-ai-tagging__chip">%s</span>',
					esc_html( $name )
				),
				$names
			)
		);
	}

	/**
	 * Current media-tag names assigned to an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string[]
	 */
	private function current_tags( int $attachment_id ): array {
		$terms = get_the_terms( $attachment_id, MediaTaxonomy::TAXONOMY );

		return is_array( $terms ) ? array_values( wp_list_pluck( $terms, 'name' ) ) : array();
	}

	/**
	 * Insert the field directly after the Alternative Text field, falling
	 * back to appending when that field is absent.
	 *
	 * @param array<string, array<string, string>> $form_fields Existing fields.
	 * @param array<string, string>                $field       Field to insert.
	 * @return array<string, array<string, string>>
	 */
	private function insert_after_alt( array $form_fields, array $field ): array {
		$reordered = array();
		$inserted  = false;

		foreach ( $form_fields as $key => $value ) {
			$reordered[ $key ] = $value;
			if ( 'image_alt' === $key ) {
				$reordered['moap_ai_tagging'] = $field;
				$inserted                     = true;
			}
		}

		if ( ! $inserted ) {
			$reordered['moap_ai_tagging'] = $field;
		}

		return $reordered;
	}
}
