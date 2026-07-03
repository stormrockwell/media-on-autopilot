<?php
/**
 * Integration harness smoke test.
 *
 * Verifies that WordPress is loaded and the plugin's classes are
 * available under WP_UnitTestCase.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration;

use WP_UnitTestCase;

final class HarnessTest extends WP_UnitTestCase {

	/**
	 * WordPress core should be loaded and functional.
	 */
	public function test_wordpress_loaded(): void {
		$this->assertTrue( function_exists( 'do_action' ) );
	}

	/**
	 * The plugin's main class should be autoloadable.
	 */
	public function test_plugin_class_exists(): void {
		$this->assertTrue( class_exists( \MediaOnAutopilot\Plugin::class ) );
	}
}
