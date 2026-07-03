<?php
/**
 * Cheap, no-API status signals for the settings dashboard.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Support\Settings;

use MediaOnAutopilot\Features\AiTagging\AutoTagSetting;
use MediaOnAutopilot\Features\AiTagging\Connector;
use MediaOnAutopilot\Features\Cdn\CdnSettings;
use MediaOnAutopilot\Features\Cdn\CdnTester;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImageIdStore;
use MediaOnAutopilot\Features\FocalPoint\FocalPointSetting;

defined( 'ABSPATH' ) || exit;

/**
 * Reads stored options + one COUNT query to describe current configuration.
 */
final class SettingsStatus {

	/**
	 * Build the status array. No network calls.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$cdn      = new CdnSettings();
		$bunny    = new BunnySettings();
		$cf       = new CloudflareSettings();
		$provider = $cdn->current();

		$configured = ( 'bunny' === $provider && $bunny->to_config()->is_active() )
			|| ( 'cloudflare' === $provider && $cf->to_config()->is_active() );

		$last_test = get_option( CdnTester::LAST_TEST_OPTION, null );

		return array(
			'provider'       => $provider,
			'cdnConfigured'  => $configured,
			'serving'        => $cdn->should_serve(),
			'cdnLastTest'    => is_array( $last_test ) ? $last_test : null,
			'offloaded'      => 'cloudflare' === $provider ? $this->offload_counts() : array(
				'done'  => 0,
				'total' => 0,
			),
			'aiAvailable'    => ( new Connector() )->is_available(),
			'autoTag'        => (bool) get_option( AutoTagSetting::OPTION, false ),
			'targetTagCount' => (int) get_option( AutoTagSetting::TARGET_TAG_COUNT_OPTION, 20 ),
			'focalEnabled'   => (bool) get_option( FocalPointSetting::OPTION, true ),
		);
	}

	/**
	 * Count total image attachments and how many carry a Cloudflare id.
	 *
	 * @return array{done:int,total:int}
	 */
	private function offload_counts(): array {
		$total = (int) ( new \WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'nopaging'       => true,
			)
		) )->found_posts;

		$done = (int) ( new \WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'nopaging'       => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => ImageIdStore::META_KEY,
						'compare' => 'EXISTS',
					),
				),
			)
		) )->found_posts;

		return array(
			'done'  => $done,
			'total' => max( $total, $done ),
		);
	}
}
