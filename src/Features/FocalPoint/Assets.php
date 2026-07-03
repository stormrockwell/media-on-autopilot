<?php
/**
 * Enqueues the built focal point assets and passes settings to JS.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Handles focal point asset enqueueing.
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
	 * Enqueue focal point assets on relevant admin pages.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'upload.php' ), true ) ) {
			return;
		}

		$asset_file = MOAP_PLUGIN_DIR . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file;

		wp_enqueue_script(
			'moap-focal-point',
			MOAP_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'moap-focal-point',
			MOAP_PLUGIN_URL . 'build/style-index.css',
			array(),
			$asset['version']
		);

		wp_localize_script(
			'moap-focal-point',
			'moapFocalPoint',
			array(
				'metaKey' => FocalPointMeta::META_KEY,
			)
		);
	}
}
