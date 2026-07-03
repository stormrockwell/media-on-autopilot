<?php
/**
 * Contract for sending an image to a vision model.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

interface VisionClient {

	/**
	 * Describe an image, returning a decoded `{ alt, tags }` array or an error.
	 *
	 * @param string               $file_path Absolute path to a resized image.
	 * @param string               $prompt    Instruction text.
	 * @param array<string, mixed> $schema    JSON response schema.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function describe( string $file_path, string $prompt, array $schema );
}
