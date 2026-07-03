<?php
/**
 * Wires the AI Tagging feature.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;
use MediaOnAutopilot\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Wires all AI Tagging components.
 */
final class AiTaggingModule implements Module {

	/**
	 * Register the feature.
	 *
	 * @return void
	 */
	public function register(): void {
		( new MediaTaxonomy() )->register();

		$connector = new Connector();
		$schema    = new ResponseSchema();
		$tagger    = new Tagger( new NativeVisionClient(), new ImageResizer(), $schema, new FocalPointMeta() );

		( new RestController( $tagger, $connector ) )->register();
		( new AiTestController( new AiTester( new NativeVisionClient(), $schema, $connector ) ) )->register();
		( new AttachmentField( $connector ) )->register();
		( new Assets() )->register();

		$setting = new AutoTagSetting();
		$setting->register();
		( new AutoTagOnUpload( $tagger, $setting, $connector ) )->register();

		$store      = new \MediaOnAutopilot\Support\Batch\ProgressStore();
		$background = new BackgroundTagger( $tagger, $store );

		$batch = new \MediaOnAutopilot\Support\Batch\BatchController( $store );
		$batch->register_job( BackgroundTagger::SLUG, $background, 'manage_options' );
		$batch->register();

		if ( $connector->is_available() ) {
			( new RetagTool( $background ) )->register();
		}

		( new MediaSearch() )->register();
	}
}
