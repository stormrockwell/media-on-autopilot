<?php
/**
 * Cloudflare Images settings section + fields.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Cloudflare;

use MediaOnAutopilot\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Cloudflare Images settings section and resolves the config.
 */
final class CloudflareSettings {

	public const SECTION = 'moap_cloudflare';

	public const OPTION_ACCOUNT = 'moap_cloudflare_account_id';
	public const OPTION_TOKEN   = 'moap_cloudflare_api_token';
	public const OPTION_HASH    = 'moap_cloudflare_account_hash';
	public const OPTION_QUALITY = 'moap_cloudflare_quality';
	public const OPTION_FORMAT  = 'moap_cloudflare_format';

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
			self::OPTION_ACCOUNT,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			Settings::MENU_SLUG,
			self::OPTION_TOKEN,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			Settings::MENU_SLUG,
			self::OPTION_HASH,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			Settings::MENU_SLUG,
			self::OPTION_QUALITY,
			array(
				'type'              => 'integer',
				'default'           => 85,
				'sanitize_callback' => array( CloudflareConfig::class, 'sanitize_quality' ),
			)
		);
		register_setting(
			Settings::MENU_SLUG,
			self::OPTION_FORMAT,
			array(
				'type'              => 'string',
				'default'           => 'auto',
				'sanitize_callback' => array( CloudflareConfig::class, 'sanitize_format' ),
			)
		);
	}

	/**
	 * Resolve config from stored options.
	 *
	 * @return CloudflareConfig
	 */
	public function to_config(): CloudflareConfig {
		return new CloudflareConfig(
			(string) get_option( self::OPTION_ACCOUNT, '' ),
			(string) get_option( self::OPTION_TOKEN, '' ),
			(string) get_option( self::OPTION_HASH, '' ),
			CloudflareConfig::sanitize_quality( get_option( self::OPTION_QUALITY, 85 ) ),
			CloudflareConfig::sanitize_format( get_option( self::OPTION_FORMAT, 'auto' ) )
		);
	}
}
