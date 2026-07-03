<?php

declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Unit\AiTagging;

use MediaOnAutopilot\Features\AiTagging\TagSuggestion;
use PHPUnit\Framework\TestCase;

final class TagSuggestionTest extends TestCase {

	public function test_strips_preamble_and_ucfirsts_alt(): void {
		$s = TagSuggestion::from_response( array( 'alt' => 'a photo of a red bicycle' ), 10 );
		$this->assertSame( 'Red bicycle', $s->alt );
	}

	public function test_clamps_alt_to_125_chars(): void {
		$long = str_repeat( 'word ', 40 ); // 200 chars
		$s    = TagSuggestion::from_response( array( 'alt' => $long ), 10 );
		$this->assertLessThanOrEqual( 125, mb_strlen( $s->alt ) );
	}

	public function test_tags_lowercased_deduped_and_capped(): void {
		$s = TagSuggestion::from_response(
			array( 'tags' => array( 'Dog', 'dog', 'ANIMAL', 'pet', 'pet' ) ),
			2
		);
		$this->assertSame( array( 'dog', 'animal' ), $s->tags );
	}

	public function test_drops_non_string_and_empty_tags(): void {
		$s = TagSuggestion::from_response(
			array( 'tags' => array( 'cat', '', '   ', 42, null, 'tree' ) ),
			10
		);
		$this->assertSame( array( 'cat', 'tree' ), $s->tags );
	}

	public function test_strips_bare_leading_article(): void {
		$s = TagSuggestion::from_response( array( 'alt' => 'a red bicycle' ), 10 );
		$this->assertSame( 'Red bicycle', $s->alt );
	}

	public function test_missing_keys_yield_empty(): void {
		$s = TagSuggestion::from_response( array(), 10 );
		$this->assertSame( '', $s->alt );
		$this->assertSame( array(), $s->tags );
	}

	public function test_parses_focal_point_from_response(): void {
		$suggestion = \MediaOnAutopilot\Features\AiTagging\TagSuggestion::from_response(
			array( 'alt' => 'x', 'tags' => array(), 'focal' => array( 'x' => 0.25, 'y' => 0.75 ) ),
			20
		);
		$this->assertNotNull( $suggestion->focal );
		$this->assertSame( 0.25, $suggestion->focal->x );
		$this->assertSame( 0.75, $suggestion->focal->y );
	}

	public function test_focal_is_null_when_absent(): void {
		$suggestion = \MediaOnAutopilot\Features\AiTagging\TagSuggestion::from_response(
			array( 'alt' => 'x', 'tags' => array() ),
			20
		);
		$this->assertNull( $suggestion->focal );
	}
}
