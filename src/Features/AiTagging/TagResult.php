<?php
/**
 * Outcome of one Tagger run.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result reported by Tagger::tag().
 */
final class TagResult {

	/**
	 * Constructs an immutable TagResult.
	 *
	 * @param bool            $ok      Whether tagging succeeded.
	 * @param string|null     $alt     Alt text written (null if not written).
	 * @param string[]        $tags    Tags applied.
	 * @param \WP_Error|null  $error   Error on failure.
	 * @param FocalPoint|null $focal   Focal point written, or null if not written.
	 * @param bool            $skipped Whether tagging was skipped.
	 */
	private function __construct(
		public readonly bool $ok,
		public readonly ?string $alt,
		public readonly array $tags,
		public readonly ?\WP_Error $error,
		public readonly ?FocalPoint $focal = null,
		public readonly bool $skipped = false
	) {}

	/**
	 * Creates a successful TagResult.
	 *
	 * @param string|null     $alt   Alt text written, or null.
	 * @param string[]        $tags  Tags applied.
	 * @param FocalPoint|null $focal Focal point written, or null.
	 * @return self
	 */
	public static function success( ?string $alt, array $tags, ?FocalPoint $focal = null ): self {
		return new self( true, $alt, $tags, null, $focal, false );
	}

	/**
	 * Creates a failed TagResult.
	 *
	 * @param \WP_Error $error Failure detail.
	 * @return self
	 */
	public static function error( \WP_Error $error ): self {
		return new self( false, null, array(), $error, null, false );
	}

	/**
	 * Creates a skipped TagResult.
	 *
	 * @return self
	 */
	public static function skipped(): self {
		return new self( true, null, array(), null, null, true );
	}
}
