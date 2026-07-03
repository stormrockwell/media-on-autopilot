<?php
/**
 * Single canonical settings-state builder: stored values + derived status +
 * tool descriptors. Used both to seed the React app (localized) and as the
 * return value of every settings save.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Support\Settings;

use MediaOnAutopilot\Features\AiTagging\AutoTagSetting;
use MediaOnAutopilot\Features\Cdn\CdnSettings;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Features\FocalPoint\FocalPointSetting;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the settings-state payload consumed by the React admin app.
 */
final class SettingsState {

	/**
	 * Options that hold a bearer secret. These are never sent to the browser; the
	 * client only learns whether one is set (via secretsSet) and writes a new value
	 * to change it. The account ID and account hash are identifiers (the hash even
	 * appears in public delivery URLs), so they are not secret.
	 */
	private const SECRET_OPTIONS = array(
		CloudflareSettings::OPTION_TOKEN,
	);

	/**
	 * Build the full state payload.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'values'     => $this->values(),
			'status'     => ( new SettingsStatus() )->to_array(),
			'secretsSet' => $this->secrets_set(),
			/**
			 * Filters the list of settings-page tool descriptors.
			 *
			 * @param array<int, array<string, mixed>> $tools Tool descriptors.
			 */
			'tools'      => (array) apply_filters( 'moap_settings_tools', array() ),
		);
	}

	/**
	 * Whether each secret option currently has a stored value, without exposing the value.
	 *
	 * @return array<string, bool>
	 */
	private function secrets_set(): array {
		$set = array();
		foreach ( self::SECRET_OPTIONS as $option ) {
			$set[ $option ] = '' !== (string) get_option( $option, '' );
		}

		return $set;
	}

	/**
	 * Current stored value for every option the settings screen edits.
	 *
	 * @return array<string, mixed>
	 */
	private function values(): array {
		return array(
			FocalPointSetting::OPTION               => get_option( FocalPointSetting::OPTION, '1' ),
			AutoTagSetting::OPTION                  => get_option( AutoTagSetting::OPTION, '0' ),
			AutoTagSetting::TARGET_TAG_COUNT_OPTION => (int) get_option( AutoTagSetting::TARGET_TAG_COUNT_OPTION, 20 ),
			CdnSettings::OPTION_PROVIDER            => get_option( CdnSettings::OPTION_PROVIDER, 'none' ),
			CdnSettings::OPTION_SERVE               => get_option( CdnSettings::OPTION_SERVE, '0' ),
			BunnySettings::OPTION_HOST              => get_option( BunnySettings::OPTION_HOST, '' ),
			BunnySettings::OPTION_QUALITY           => (int) get_option( BunnySettings::OPTION_QUALITY, 85 ),
			BunnySettings::OPTION_FORMAT            => get_option( BunnySettings::OPTION_FORMAT, 'auto' ),
			CloudflareSettings::OPTION_ACCOUNT      => get_option( CloudflareSettings::OPTION_ACCOUNT, '' ),
			// Write-only: the stored API token is never sent to the browser. See secretsSet().
			CloudflareSettings::OPTION_TOKEN        => '',
			CloudflareSettings::OPTION_HASH         => get_option( CloudflareSettings::OPTION_HASH, '' ),
			CloudflareSettings::OPTION_QUALITY      => (int) get_option( CloudflareSettings::OPTION_QUALITY, 85 ),
			CloudflareSettings::OPTION_FORMAT       => get_option( CloudflareSettings::OPTION_FORMAT, 'auto' ),
		);
	}
}
