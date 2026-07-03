<?php
/**
 * Builds the prompt and JSON schema for the AI vision call.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Filter-aware prompt + response schema for tagging.
 */
final class ResponseSchema {

	/**
	 * The instruction sent with the image.
	 *
	 * @return string
	 */
	public function prompt(): string {
		$target_tag_count = $this->target_tag_count();
		$default          = 'You are tagging an image for a website media library. '
			. 'Return concise alt text describing the visible content (no "image of" preamble, '
			. 'one sentence, at most ~125 characters), a list of lowercase single- or '
			. 'two-word tags, and a focal point '
			. '(x and y between 0 and 1, where 0,0 is the top-left) marking the center of the '
			. 'main subject for smart cropping. '
			. sprintf(
				'Provide exactly %d tags, ordered from most to least relevant, covering subjects, '
					. 'objects, setting, colors, mood, and style so the list is comprehensive.',
				$target_tag_count
			);

		return (string) apply_filters( 'moap_ai_tagging_prompt', $default, $target_tag_count );
	}

	/**
	 * The JSON response schema.
	 *
	 * @return array<string, mixed>
	 */
	public function schema(): array {
		$default = array(
			'type'                 => 'object',
			'properties'           => array(
				'alt'   => array( 'type' => 'string' ),
				'tags'  => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'focal' => array(
					'type'                 => 'object',
					'properties'           => array(
						'x' => array( 'type' => 'number' ),
						'y' => array( 'type' => 'number' ),
					),
					'required'             => array( 'x', 'y' ),
					'additionalProperties' => false,
				),
			),
			'required'             => array( 'alt', 'tags', 'focal' ),
			// Providers' strict structured-output mode rejects schemas that
			// don't explicitly forbid extra keys.
			'additionalProperties' => false,
		);

		return (array) apply_filters( 'moap_ai_tagging_schema', $default );
	}

	/**
	 * Target number of tags to request per image.
	 *
	 * @return int
	 */
	public function target_tag_count(): int {
		return (int) apply_filters( 'moap_ai_tagging_target_tag_count', 20 );
	}
}
