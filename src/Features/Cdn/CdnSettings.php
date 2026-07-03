<?php
/**
 * CDN provider selector (none | bunny | cloudflare).
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn;

use MediaOnAutopilot\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and resolves the single active CDN provider.
 */
final class CdnSettings {

	public const SECTION         = 'moap_cdn';
	public const OPTION_PROVIDER = 'moap_cdn_provider';
	public const OPTION_SERVE    = 'moap_cdn_serve';

	private const PROVIDERS = array( 'none', 'bunny', 'cloudflare' );

	/**
	 * Register the selector field.
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
			self::OPTION_PROVIDER,
			array(
				'type'              => 'string',
				'default'           => 'none',
				'sanitize_callback' => array( self::class, 'sanitize_provider' ),
			)
		);

		register_setting(
			Settings::MENU_SLUG,
			self::OPTION_SERVE,
			array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => array( self::class, 'sanitize_serve' ),
			)
		);
	}

	/**
	 * Restrict the provider to a known key, defaulting to 'none'.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_provider( $value ): string {
		return in_array( $value, self::PROVIDERS, true ) ? (string) $value : 'none';
	}

	/**
	 * Normalize the serve toggle to a stored '1'/'0' string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_serve( $value ): string {
		return $value ? '1' : '0';
	}

	/**
	 * The active provider key.
	 *
	 * @return string
	 */
	public function current(): string {
		$value = (string) get_option( self::OPTION_PROVIDER, 'none' );
		return in_array( $value, self::PROVIDERS, true ) ? $value : 'none';
	}

	/**
	 * Whether images should be served through the CDN right now.
	 *
	 * @return bool
	 */
	public function should_serve(): bool {
		return (bool) get_option( self::OPTION_SERVE, false );
	}
}
