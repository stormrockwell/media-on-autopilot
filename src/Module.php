<?php
/**
 * Feature module contract.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot;

defined( 'ABSPATH' ) || exit;

interface Module {
	/**
	 * Wire the module's hooks.
	 */
	public function register(): void;
}
