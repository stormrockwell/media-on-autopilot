<?php
/**
 * Enqueues the AI tagging admin bundle.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Loads build/ai-tagging.js on attachment-editing admin screens.
 */
final class Assets {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue assets on relevant admin pages.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'upload.php' ), true ) ) {
			return;
		}

		$asset_file = MOAP_PLUGIN_DIR . 'build/ai-tagging.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file;

		wp_enqueue_script(
			'moap-ai-tagging',
			MOAP_PLUGIN_URL . 'build/ai-tagging.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'moap-ai-tagging',
			MOAP_PLUGIN_URL . 'build/style-ai-tagging.css',
			array(),
			$asset['version']
		);
	}
}
