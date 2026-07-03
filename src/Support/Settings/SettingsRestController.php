<?php
/**
 * Per-screen settings save: POST moap/v1/settings/{section}.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Support\Settings;

use MediaOnAutopilot\Features\AiTagging\AutoTagSetting;
use MediaOnAutopilot\Features\Cdn\CdnSettings;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareConfig;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Features\FocalPoint\FocalPointSetting;

defined( 'ABSPATH' ) || exit;

/**
 * Saves one settings screen's options, each via its own sanitizer.
 */
final class SettingsRestController {

	/**
	 * Section -> [ option name => sanitizer callable ]. The same callables the
	 * settings classes pass to register_setting(), kept here as the one place a
	 * screen's field set is enumerated.
	 *
	 * @return array<string, array<string, callable>>
	 */
	private function map(): array {
		$bool  = static fn( $v ): string => $v ? '1' : '0';
		$enum  = static fn( array $allowed, string $fallback ) => static fn( $v ): string => in_array( $v, $allowed, true ) ? (string) $v : $fallback;
		$clamp = static fn( $v ): int => min( 100, max( 1, absint( $v ) ) );

		return array(
			'focal' => array(
				FocalPointSetting::OPTION => $bool,
			),
			'ai'    => array(
				AutoTagSetting::OPTION                  => $bool,
				AutoTagSetting::TARGET_TAG_COUNT_OPTION => static fn( $v ): int => absint( $v ),
			),
			'cdn'   => array(
				CdnSettings::OPTION_PROVIDER       => $enum( array( 'none', 'bunny', 'cloudflare' ), 'none' ),
				CdnSettings::OPTION_SERVE          => $bool,
				BunnySettings::OPTION_HOST         => array( BunnyConfig::class, 'sanitize_host' ),
				BunnySettings::OPTION_QUALITY      => $clamp,
				BunnySettings::OPTION_FORMAT       => $enum( array( 'auto', 'webp', 'avif', 'off' ), 'auto' ),
				CloudflareSettings::OPTION_ACCOUNT => 'sanitize_text_field',
				CloudflareSettings::OPTION_TOKEN   => 'sanitize_text_field',
				CloudflareSettings::OPTION_HASH    => 'sanitize_text_field',
				CloudflareSettings::OPTION_QUALITY => array( CloudflareConfig::class, 'sanitize_quality' ),
				CloudflareSettings::OPTION_FORMAT  => array( CloudflareConfig::class, 'sanitize_format' ),
			),
		);
	}

	/**
	 * Register the route.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Define the save route. Nonce verification for browser clients is handled by WordPress's
	 * REST cookie-auth middleware via the X-WP-Nonce header, consistent with CdnVerifyController.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'moap/v1',
			'/settings/(?P<section>focal|ai|cdn)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
			)
		);
	}

	/**
	 * Sanitize + persist the posted fields for one section.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$section = (string) $request->get_param( 'section' );
		$fields  = $this->map()[ $section ] ?? array();

		foreach ( $fields as $option => $sanitizer ) {
			$value = $request->get_param( $option );

			// Skip params that were not submitted, or that arrive as a non-scalar
			// (array/object) an attacker could pass to crash a sanitizer.
			if ( null === $value || ! is_scalar( $value ) ) {
				continue;
			}

			// Normalize to a string before dispatch. is_scalar() also passes bool/int/float,
			// which would still throw a TypeError against a strict-typed sanitizer such as
			// BunnyConfig::sanitize_host( string $raw ); every sanitizer here accepts a string.
			$value = (string) $value;

			// Write-only secret left blank means "keep the stored value".
			if ( '' === $value && $this->is_secret( $option ) ) {
				continue;
			}

			$clean = call_user_func( $sanitizer, $value );
			update_option( $option, $clean );
		}

		return rest_ensure_response( ( new SettingsState() )->to_array() );
	}

	/**
	 * Whether an option holds a bearer secret that must be write-only.
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	private function is_secret( string $option ): bool {
		return CloudflareSettings::OPTION_TOKEN === $option;
	}
}
