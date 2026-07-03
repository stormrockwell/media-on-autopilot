<?php
/**
 * BunnyCDN settings section + fields.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Bunny;

use MediaOnAutopilot\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the BunnyCDN settings section and resolves the config.
 */
final class BunnySettings {

	public const SECTION = 'moap_bunnycdn';

	public const OPTION_HOST    = 'moap_bunnycdn_hostname';
	public const OPTION_QUALITY = 'moap_bunnycdn_quality';
	public const OPTION_FORMAT  = 'moap_bunnycdn_format';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_fields' ) );
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		register_setting(
			Settings::MENU_SLUG,
			self::OPTION_HOST,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( BunnyConfig::class, 'sanitize_host' ),
			)
		);
		register_setting(
			Settings::MENU_SLUG,
			self::OPTION_QUALITY,
			array(
				'type'              => 'integer',
				'default'           => 85,
				'sanitize_callback' => array( BunnyConfig::class, 'sanitize_quality' ),
			)
		);
		register_setting(
			Settings::MENU_SLUG,
			self::OPTION_FORMAT,
			array(
				'type'              => 'string',
				'default'           => 'auto',
				'sanitize_callback' => array( BunnyConfig::class, 'sanitize_format' ),
			)
		);
	}

	/**
	 * Resolve the current configuration from stored options.
	 *
	 * @return BunnyConfig
	 */
	public function to_config(): BunnyConfig {
		return new BunnyConfig(
			BunnyConfig::sanitize_host( (string) get_option( self::OPTION_HOST, '' ) ),
			(int) get_option( self::OPTION_QUALITY, 85 ),
			(string) get_option( self::OPTION_FORMAT, 'auto' )
		);
	}
}
