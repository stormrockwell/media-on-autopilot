<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit;

use MediaOnAutopilot\Module;
use MediaOnAutopilot\Plugin;
use PHPUnit\Framework\TestCase;

final class PluginTest extends TestCase {

	public function test_boot_calls_register_on_every_module(): void {
		$module = new class() implements Module {
			public bool $registered = false;
			public function register(): void {
				$this->registered = true;
			}
		};

		( new Plugin( $module ) )->boot();

		$this->assertTrue( $module->registered );
	}
}
