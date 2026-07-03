<?php
/**
 * Immutable focal point value object.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable focal point value object.
 *
 * Represents a focal point as normalized x,y coordinates in the range [0, 1].
 * Provides factory methods and utility helpers for conversion and serialization.
 */
final class FocalPoint {

	/**
	 * Constructs a focal point with x,y coordinates.
	 *
	 * @param float $x The x coordinate (0.0 to 1.0).
	 * @param float $y The y coordinate (0.0 to 1.0).
	 */
	public function __construct(
		public readonly float $x,
		public readonly float $y
	) {}

	/**
	 * Creates a focal point at the center (0.5, 0.5).
	 *
	 * @return self
	 */
	public static function center(): self {
		return new self( 0.5, 0.5 );
	}

	/**
	 * Creates a focal point from an array, clamping values to [0, 1].
	 *
	 * @param array<string, mixed> $data Array with optional 'x' and 'y' keys.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			self::clamp( (float) ( $data['x'] ?? 0.5 ) ),
			self::clamp( (float) ( $data['y'] ?? 0.5 ) )
		);
	}

	/**
	 * Converts the focal point to an array.
	 *
	 * @return array{ x: float, y: float }
	 */
	public function to_array(): array {
		return array(
			'x' => $this->x,
			'y' => $this->y,
		);
	}

	/**
	 * Checks if this focal point is at the center.
	 *
	 * @return bool
	 */
	public function is_center(): bool {
		return 0.5 === $this->x && 0.5 === $this->y;
	}

	/**
	 * Gets the x coordinate as an integer percentage (0-100).
	 *
	 * @return int
	 */
	public function x_percent(): int {
		return (int) round( $this->x * 100 );
	}

	/**
	 * Gets the y coordinate as an integer percentage (0-100).
	 *
	 * @return int
	 */
	public function y_percent(): int {
		return (int) round( $this->y * 100 );
	}

	/**
	 * Clamps a value to the range [0, 1].
	 *
	 * @param float $value The value to clamp.
	 * @return float The clamped value.
	 */
	private static function clamp( float $value ): float {
		return max( 0.0, min( 1.0, $value ) );
	}
}
