<?php
/**
 * Detects AI provider availability and links to its settings.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * AI connector availability + guidance helper.
 */
final class Connector {

	/**
	 * Whether the AI Client is usable.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		$available = function_exists( 'wp_ai_client_prompt' );

		return (bool) apply_filters( 'moap_ai_tagging_connector_available', $available );
	}

	/**
	 * URL to core's AI credentials screen.
	 *
	 * @return string
	 */
	public function settings_url(): string {
		return (string) apply_filters(
			'moap_ai_tagging_settings_url',
			admin_url( 'options-general.php?page=ai' )
		);
	}
}
