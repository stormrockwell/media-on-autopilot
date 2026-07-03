<?php
/**
 * Per-field write policy for one enrichment run.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Which fields a run writes, and whether it overwrites existing data.
 */
final class SavePlan {

	/**
	 * Which fields a run writes, and whether it overwrites existing data.
	 *
	 * @param bool $alt       Write alt text.
	 * @param bool $tags      Write tags (always additive).
	 * @param bool $focal     Write focal point.
	 * @param bool $overwrite Replace existing alt/focal (tags are always additive).
	 */
	public function __construct(
		public readonly bool $alt,
		public readonly bool $tags,
		public readonly bool $focal,
		public readonly bool $overwrite
	) {}

	/**
	 * Fill-missing plan (never replaces existing alt/focal).
	 *
	 * @param bool $alt   Write alt.
	 * @param bool $tags  Write tags.
	 * @param bool $focal Write focal.
	 * @return self
	 */
	public static function fill_missing( bool $alt, bool $tags, bool $focal ): self {
		return new self( $alt, $tags, $focal, false );
	}

	/**
	 * Overwrite plan (replaces existing alt/focal).
	 *
	 * @param bool $alt   Write alt.
	 * @param bool $tags  Write tags.
	 * @param bool $focal Write focal.
	 * @return self
	 */
	public static function overwrite( bool $alt, bool $tags, bool $focal ): self {
		return new self( $alt, $tags, $focal, true );
	}

	/**
	 * Whether the plan writes any field.
	 *
	 * @return bool
	 */
	public function wants_anything(): bool {
		return $this->alt || $this->tags || $this->focal;
	}

	/**
	 * Serialize to a queue item for an attachment.
	 *
	 * @param int $id Attachment ID.
	 * @return array{id: int, alt: bool, tags: bool, focal: bool, overwrite: bool}
	 */
	public function to_item( int $id ): array {
		return array(
			'id'        => $id,
			'alt'       => $this->alt,
			'tags'      => $this->tags,
			'focal'     => $this->focal,
			'overwrite' => $this->overwrite,
		);
	}

	/**
	 * Rebuild from a queue item.
	 *
	 * @param array<string, mixed> $item Queue item.
	 * @return self
	 */
	public static function from_item( array $item ): self {
		return new self(
			! empty( $item['alt'] ),
			! empty( $item['tags'] ),
			! empty( $item['focal'] ),
			! empty( $item['overwrite'] )
		);
	}
}
