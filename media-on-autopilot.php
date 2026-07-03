<?php
/**
 * Plugin Name:       Media on Autopilot
 * Description:       Improves the WordPress media library without being invasive: focal point, AI alt text, AI tagging, CDN.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            Storm Rockwell
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       media-on-autopilot
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.0';

define( 'MOAP_PLUGIN_FILE', __FILE__ );
define( 'MOAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$moap_autoload = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $moap_autoload ) ) {
	require $moap_autoload;
}

add_action(
	'plugins_loaded',
	static function (): void {
		( new Plugin(
			new Features\FocalPoint\FocalPointModule(),
			new Features\AiTagging\AiTaggingModule(),
			new Features\Cdn\CdnModule()
		) )->boot();
	}
);
