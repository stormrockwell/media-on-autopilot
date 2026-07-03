<?php
/**
 * WordPress integration test configuration.
 *
 * This config is loaded by wp-phpunit's bootstrap. It points ABSPATH at the
 * local WordPress install and uses the SQLite database integration so no
 * MySQL server is required.
 *
 * @package MediaOnAutopilot
 */

/*
 * ---------------------------------------------------------------
 * 1. WordPress core location.
 * ---------------------------------------------------------------
 * ABSPATH tells the WP test suite where wp-settings.php lives.
 * Trailing slash is required.
 */
define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );

/*
 * ---------------------------------------------------------------
 * 2. SQLite database engine.
 * ---------------------------------------------------------------
 * DB_ENGINE tells the SQLite integration to engage.
 * wp-content/db.php defines SQLITE_DB_DROPIN_VERSION itself
 * and loads the SQLite driver, so we only need DB_ENGINE here.
 *
 * DB_DIR / DB_FILE isolate the test database from the live site.
 */
define( 'DB_ENGINE', 'sqlite' );
define( 'DB_DIR', sys_get_temp_dir() . '/moap-wp-tests/' );
define( 'DB_FILE', '.ht.sqlite' );

/*
 * Dummy MySQL constants — the SQLite driver ignores these, but
 * wp-settings.php (or parts of core) may reference them as
 * defined(). Providing empty values avoids fatal errors.
 */
if ( ! defined( 'DB_NAME' ) ) {
	define( 'DB_NAME', 'moap_tests' );
}
if ( ! defined( 'DB_USER' ) ) {
	define( 'DB_USER', '' );
}
if ( ! defined( 'DB_PASSWORD' ) ) {
	define( 'DB_PASSWORD', '' );
}
if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', '' );
}
if ( ! defined( 'DB_CHARSET' ) ) {
	define( 'DB_CHARSET', 'utf8mb4' );
}
if ( ! defined( 'DB_COLLATE' ) ) {
	define( 'DB_COLLATE', '' );
}

/*
 * ---------------------------------------------------------------
 * 3. Required test constants.
 * ---------------------------------------------------------------
 */
define( 'WP_TESTS_DOMAIN', 'tests.local' );
define( 'WP_TESTS_EMAIL', 'admin@tests.local' );
define( 'WP_TESTS_TITLE', 'Media on Autopilot Tests' );
define( 'WP_PHP_BINARY', PHP_BINARY );
define( 'WP_DEBUG', true );

/*
 * Use a dedicated table prefix so test tables never collide with
 * the live site's wp_ tables (they are in a separate SQLite file
 * anyway, but belt-and-suspenders).
 */
$table_prefix = 'wptests_';
