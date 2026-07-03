<?php
/**
 * Contract for something that can verify a provider connection.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Verification;

defined( 'ABSPATH' ) || exit;

/**
 * A live provider connection check.
 */
interface Verifiable {

	/**
	 * Perform the check.
	 *
	 * @return VerificationResult
	 */
	public function verify(): VerificationResult;
}
