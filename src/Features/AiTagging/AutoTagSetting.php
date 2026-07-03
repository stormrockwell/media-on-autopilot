<?php
/**
 * Settings: auto-tag-on-upload toggle and target tag count.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

use MediaOnAutopilot\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI tagging settings section + fields.
 */
final class AutoTagSetting {

	public const OPTION                  = 'moap_ai_tagging_auto';
	public const TARGET_TAG_COUNT_OPTION = 'moap_ai_tagging_target_tag_count_option';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_fields' ) );
		add_filter( 'moap_ai_tagging_target_tag_count', array( $this, 'filter_target_tag_count' ) );
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		register_setting(
			Settings::MENU_SLUG,
			self::OPTION,
			array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => array( self::class, 'sanitize_enabled' ),
			)
		);

		register_setting(
			Settings::MENU_SLUG,
			self::TARGET_TAG_COUNT_OPTION,
			array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => 'absint',
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
	 * Whether auto-tagging is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION, false );
	}

	/**
	 * Feed the stored target-tag-count option into the filter.
	 *
	 * @param mixed $value Incoming filter value (ignored).
	 * @return int
	 */
	public function filter_target_tag_count( $value ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Filter signature.
		return (int) get_option( self::TARGET_TAG_COUNT_OPTION, 20 );
	}
}
