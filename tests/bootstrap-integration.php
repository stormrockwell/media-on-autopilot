<?php
/**
 * Bootstrap file for WordPress integration tests.
 *
 * Loads Composer's autoloader, tells wp-phpunit where to find the
 * test config (which in turn points at the SQLite-backed WP install),
 * then hands off to the WP test suite bootstrap.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

/*
 * 1. Composer autoloader — provides PSR-4 class loading for the
 *    plugin's own namespaces AND the Yoast PHPUnit Polyfills.
 */
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/*
 * 2. Point wp-phpunit at our test config.
 */
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

/*
 * 3. Ensure the temporary SQLite test database directory exists.
 */
$moap_test_db_dir = sys_get_temp_dir() . '/moap-wp-tests/';
if ( ! is_dir( $moap_test_db_dir ) ) {
	mkdir( $moap_test_db_dir, 0755, true );
}

/*
 * 4. Activate the plugin during the test install so its hooks fire.
 */
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'media-on-autopilot/media-on-autopilot.php' ),
);

/*
 * 5. Load the WP test suite bootstrap (installs WP, loads wp-settings.php).
 */
$moap_wp_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit/includes';
require_once $moap_wp_tests_dir . '/bootstrap.php';
