<?php
/**
 * Shared "Media on Autopilot" options page. Features register their own
 * sections/fields against MENU_SLUG via the Settings API.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Shared options page for the Media on Autopilot plugin.
 */
final class Settings {

	public const MENU_SLUG = 'media-on-autopilot';

	/**
	 * Registers the options page and its page assets.
	 *
	 * @return void
	 */
	public static function register_page(): void {
		add_action(
			'admin_menu',
			static function (): void {
				add_options_page(
					__( 'Media on Autopilot', 'media-on-autopilot' ),
					__( 'Media on Autopilot', 'media-on-autopilot' ),
					'manage_options',
					self::MENU_SLUG,
					array( self::class, 'render' )
				);
			}
		);

		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		( new \MediaOnAutopilot\Support\Settings\SettingsRestController() )->register();
	}

	/**
	 * Enqueues the admin React bundle on this options page only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		$asset_file = MOAP_PLUGIN_DIR . 'build/admin.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file;

		wp_enqueue_script( 'moap-admin', MOAP_PLUGIN_URL . 'build/admin.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( 'moap-admin', MOAP_PLUGIN_URL . 'build/style-admin.css', array(), $asset['version'] );

		wp_localize_script(
			'moap-admin',
			'moapAdmin',
			array(
				'restBase'      => esc_url_raw( rest_url( 'moap/v1' ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'sampleImage'   => MOAP_PLUGIN_URL . 'assets/sample-image.jpg',
				'pluginUrl'     => MOAP_PLUGIN_URL,
				'connectorsUrl' => admin_url( 'admin.php?page=options-connectors-wp-admin' ),
				'homeUrl'       => home_url( '/' ),
				'state'         => ( new \MediaOnAutopilot\Support\Settings\SettingsState() )->to_array(),
				'labels'        => array(
					'saved'    => __( 'Saved', 'media-on-autopilot' ),
					'saving'   => __( 'Saving…', 'media-on-autopilot' ),
					'testing'  => __( 'Testing…', 'media-on-autopilot' ),
					'testPass' => __( 'Passed', 'media-on-autopilot' ),
					'testFail' => __( 'Failed', 'media-on-autopilot' ),
				),
			)
		);
	}

	/**
	 * Renders the options page markup.
	 *
	 * @return void
	 */
	public static function render(): void {
		echo '<div class="wrap moap-admin"><div id="moap-settings-root"></div></div>';
	}
}
