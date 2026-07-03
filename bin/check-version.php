#!/usr/bin/env php
<?php
/**
 * Fails (exit 1) unless the plugin header Version, the VERSION const, the
 * readme.txt Stable tag, and the newest changelog entry are all identical.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

$root        = dirname( __DIR__ );
$plugin_file = file_get_contents( "$root/media-on-autopilot.php" );
$readme      = file_get_contents( "$root/readme.txt" );

preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $plugin_file, $m_header );
preg_match( "/const VERSION\s*=\s*'([^']+)'/", $plugin_file, $m_const );
preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $m_tag );
preg_match( '/^=\s*([0-9][^=\s]*)\s*=$/m', $readme, $m_changelog );

$found = array(
	'header Version'   => $m_header[1] ?? '(missing)',
	'VERSION const'    => $m_const[1] ?? '(missing)',
	'readme Stable tag' => $m_tag[1] ?? '(missing)',
	'newest changelog' => $m_changelog[1] ?? '(missing)',
);

$unique = array_unique( array_values( $found ) );

if ( count( $unique ) === 1 && '(missing)' !== $unique[0] ) {
	echo "Version OK: {$unique[0]}\n";
	exit( 0 );
}

echo "Version mismatch:\n";
foreach ( $found as $label => $value ) {
	echo "  - $label: $value\n";
}
exit( 1 );
