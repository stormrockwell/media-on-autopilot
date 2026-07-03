<?php
/**
 * Plugin bootstrap.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin orchestrator.
 */
final class Plugin {

	/**
	 * Registered feature modules.
	 *
	 * @var Module[]
	 */
	private array $modules;

	/**
	 * Constructor.
	 *
	 * @param Module ...$modules Feature modules to register.
	 */
	public function __construct( Module ...$modules ) {
		$this->modules = $modules;
	}

	/**
	 * Boot all registered modules.
	 */
	public function boot(): void {
		foreach ( $this->modules as $module ) {
			$module->register();
		}
	}
}
