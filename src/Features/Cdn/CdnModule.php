<?php
/**
 * Wires the CDN feature: one active provider.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn;

use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnyProvider;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Verification\CdnVerifier;
use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use MediaOnAutopilot\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Selects and wires the single active CDN provider.
 */
final class CdnModule implements Module {

	/**
	 * Register the feature.
	 *
	 * @return void
	 */
	public function register(): void {
		$selector = new CdnSettings();
		$selector->register();

		// All provider sections render; a small script on the settings page shows
		// only the selected provider's section (see CdnSettings::enqueue_admin).
		$bunny = new BunnySettings();
		$bunny->register();

		$cf = new \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings();
		$cf->register();

		( new CdnVerifyController( new CdnVerifier( $bunny, $cf ) ) )->register();

		( new CdnTestController(
			new CdnTester( $selector, $bunny, $cf, array( CdnTester::class, 'http_fetch' ) )
		) )->register();

		$this->maybe_migrate_serve( $selector, $bunny, $cf );

		// Always wire the Cloudflare offload tool (render + REST start route + batch
		// job) so its markup is present in the CDN → Tools panel and the start
		// endpoint exists regardless of the live provider selection. nav.js shows or
		// hides the tool based on the live provider value; the start endpoint checks
		// config at runtime. Delete-sync + provider serving stay gated on config.
		$this->wire_cloudflare_offload( $cf );

		$serve = $selector->should_serve();

		switch ( $selector->current() ) {
			case 'bunny':
				$config = $bunny->to_config();
				if ( $config->is_active() && $serve ) {
					$this->wire( new BunnyProvider( $config ) );
				}
				break;

			case 'cloudflare':
				$config = $cf->to_config();
				if ( $config->is_active() && $serve ) {
					$ids = new \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImageIdStore();
					$this->wire( new \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareProvider( $config, $ids ) );
				}
				break;
		}
	}

	/**
	 * Wire the Cloudflare offload tool, its REST start route, its batch job, and
	 * (when configured) the delete-sync hooks. The tool markup always renders in
	 * the CDN → Tools panel; nav.js reveals it only when Cloudflare is the live,
	 * configured provider. The start endpoint enforces config at runtime.
	 *
	 * @param \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings $cf Cloudflare settings resolver.
	 * @return void
	 */
	private function wire_cloudflare_offload( \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings $cf ): void {
		$config    = $cf->to_config();
		$store     = new \MediaOnAutopilot\Support\Batch\ProgressStore();
		$ids       = new \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImageIdStore();
		$api       = new \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\ImagesApiClient( $config );
		$offloader = new \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareOffloader( $config, $api, $ids, $store );

		// Delete-sync only matters once images have been offloaded (config active).
		if ( $config->is_active() ) {
			$offloader->register();
		}

		( new \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\OffloadTool( $offloader ) )->register();

		$batch = new \MediaOnAutopilot\Support\Batch\BatchController( $store );
		$batch->register_job( \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareOffloader::SLUG, $offloader, 'manage_options' );
		$batch->register();
	}

	/**
	 * One-time upgrade migration: enable CDN serving for sites that already had a
	 * provider configured before the serve toggle existed, so updating does not
	 * silently stop CDN delivery. Runs once, guarded by an option flag.
	 *
	 * @param CdnSettings                                                            $selector Provider selector.
	 * @param BunnySettings                                                          $bunny    Bunny settings resolver.
	 * @param \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings $cf       Cloudflare settings resolver.
	 * @return void
	 */
	private function maybe_migrate_serve( CdnSettings $selector, BunnySettings $bunny, \MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings $cf ): void {
		if ( '' !== (string) get_option( 'moap_cdn_serve_migrated', '' ) ) {
			return;
		}
		update_option( 'moap_cdn_serve_migrated', '1' );

		$provider = $selector->current();
		$active   = ( 'bunny' === $provider && $bunny->to_config()->is_active() )
			|| ( 'cloudflare' === $provider && $cf->to_config()->is_active() );
		if ( $active ) {
			update_option( CdnSettings::OPTION_SERVE, '1' );
		}
	}

	/**
	 * Wire the shared frontend to a provider + apply the focal contract.
	 *
	 * @param ImageProvider $provider Active provider.
	 * @return void
	 */
	private function wire( ImageProvider $provider ): void {
		( new ImageFrontend( $provider, new FocalPointMeta() ) )->register();

		if ( $provider->encodes_focal_in_url() ) {
			add_filter( 'moap_focal_point_cache_bust', '__return_false' );
		}
	}
}
