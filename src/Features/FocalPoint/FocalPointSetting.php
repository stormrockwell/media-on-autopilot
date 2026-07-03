<?php
/**
 * Setting: master on/off for focal-point cropping.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

use MediaOnAutopilot\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the focal-point on/off toggle.
 */
final class FocalPointSetting {

	public const OPTION = 'moap_focal_point_enabled';

	/**
	 * Register the setting + field.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_fields' ) );
	}

	/**
	 * Declare the option.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		register_setting(
			Settings::MENU_SLUG,
			self::OPTION,
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => array( self::class, 'sanitize_enabled' ),
			)
		);
	}

	/**
	 * Normalize the toggle to a stored '1'/'0' string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_enabled( $value ): string {
		return $value ? '1' : '0';
	}

	/**
	 * Whether focal-point cropping is enabled (default on).
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION, true );
	}
}
