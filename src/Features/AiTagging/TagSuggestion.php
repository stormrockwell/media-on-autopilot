<?php
/**
 * Normalized alt + tags parsed from an AI vision response.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

use MediaOnAutopilot\Features\FocalPoint\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable, normalized result of one vision call.
 */
final class TagSuggestion {

	/**
	 * Constructs a normalized suggestion.
	 *
	 * @param string          $alt   Normalized alt text.
	 * @param string[]        $tags  Normalized, deduped, capped tags.
	 * @param FocalPoint|null $focal Suggested focal point, or null if none.
	 */
	public function __construct(
		public readonly string $alt,
		public readonly array $tags,
		public readonly ?FocalPoint $focal = null
	) {}

	/**
	 * Build a suggestion from a raw decoded AI response.
	 *
	 * @param array<string, mixed> $data             Decoded `{ alt, tags, focal }` response.
	 * @param int                  $target_tag_count Target number of tags to keep.
	 * @return self
	 */
	public static function from_response( array $data, int $target_tag_count ): self {
		return new self(
			self::normalize_alt( is_string( $data['alt'] ?? null ) ? $data['alt'] : '' ),
			self::normalize_tags( $data['tags'] ?? array(), $target_tag_count ),
			self::normalize_focal( $data['focal'] ?? null )
		);
	}

	/**
	 * Parse a focal point from a `{ x, y }` payload; null if absent/invalid.
	 *
	 * @param mixed $focal Raw focal value.
	 * @return FocalPoint|null
	 */
	private static function normalize_focal( mixed $focal ): ?FocalPoint {
		if ( ! is_array( $focal ) || ! isset( $focal['x'], $focal['y'] ) ) {
			return null;
		}
		return FocalPoint::from_array( $focal );
	}

	/**
	 * Trim, strip "photo of" preamble, sentence-case, clamp length.
	 *
	 * @param string $alt Raw alt text.
	 * @return string
	 */
	private static function normalize_alt( string $alt ): string {
		$alt = trim( $alt );
		$alt = (string) preg_replace( '/^\s*(an?\s+)?(image|photo|picture|photograph)\s+of\s+(an?\s+)?/i', '', $alt );
		$alt = (string) preg_replace( '/^an?\s+/i', '', $alt );
		$alt = trim( $alt );
		if ( '' === $alt ) {
			return '';
		}
		$alt = mb_strtoupper( mb_substr( $alt, 0, 1 ) ) . mb_substr( $alt, 1 );
		if ( mb_strlen( $alt ) > 125 ) {
			$alt = rtrim( mb_substr( $alt, 0, 125 ) );
		}
		return $alt;
	}

	/**
	 * Lowercase, trim, drop empties/overlong/non-strings, dedupe, cap.
	 *
	 * @param mixed $tags             Raw tags value.
	 * @param int   $target_tag_count Target number of tags to keep.
	 * @return string[]
	 */
	private static function normalize_tags( mixed $tags, int $target_tag_count ): array {
		if ( ! is_array( $tags ) ) {
			return array();
		}
		$clean = array();
		foreach ( $tags as $tag ) {
			if ( ! is_string( $tag ) ) {
				continue;
			}
			$tag = mb_strtolower( trim( $tag ) );
			if ( '' === $tag || mb_strlen( $tag ) > 50 ) {
				continue;
			}
			$clean[ $tag ] = true;
		}
		return array_slice( array_keys( $clean ), 0, max( 0, $target_tag_count ) );
	}
}
