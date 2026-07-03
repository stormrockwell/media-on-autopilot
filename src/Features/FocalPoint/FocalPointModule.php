<?php
/**
 * Wires the Focal Point feature.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

use MediaOnAutopilot\Module;
use MediaOnAutopilot\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Wires all Focal Point feature components.
 */
final class FocalPointModule implements Module {

	/**
	 * Register the focal point feature hooks and components.
	 *
	 * @return void
	 */
	public function register(): void {
		$meta = new FocalPointMeta();
		$meta->register();

		$setting = new FocalPointSetting();
		$setting->register();

		if ( $setting->is_enabled() ) {
			( new Assets() )->register();
			( new AttachmentField( $meta ) )->register();
			( new CropHandler( $meta ) )->register();
			( new RegenerationListener( new Regenerator() ) )->register();
			( new Frontend( $meta ) )->register();
		}

		Settings::register_page();
	}
}
