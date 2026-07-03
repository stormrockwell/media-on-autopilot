<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\Cdn\Verification;

use MediaOnAutopilot\Features\Cdn\Verification\VerificationResult;
use PHPUnit\Framework\TestCase;

final class VerificationResultTest extends TestCase {

	public function test_ok_round_trips_through_array(): void {
		$result = VerificationResult::ok( 'Connected.' );
		$this->assertSame( 'ok', $result->state );
		$this->assertSame( 'Connected.', $result->message );
		$this->assertSame( '', $result->detail );
		$this->assertSame(
			array( 'state' => 'ok', 'message' => 'Connected.', 'detail' => '' ),
			$result->to_array()
		);
	}

	public function test_error_carries_detail(): void {
		$result = VerificationResult::error( 'Failed.', 'HTTP 403' );
		$this->assertSame( 'error', $result->state );
		$this->assertSame( 'HTTP 403', $result->detail );
	}

	public function test_from_array_defaults_unknown_state_to_error(): void {
		$result = VerificationResult::from_array( array( 'state' => 'bogus', 'message' => 'x' ) );
		$this->assertSame( 'error', $result->state );
		$this->assertSame( 'x', $result->message );
		$this->assertSame( '', $result->detail );
	}

	public function test_unconfigured_factory(): void {
		$this->assertSame( 'unconfigured', VerificationResult::unconfigured( 'Set it up.' )->state );
	}
}
