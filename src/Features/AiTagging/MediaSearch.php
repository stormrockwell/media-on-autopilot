<?php
/**
 * Makes media tags searchable in the media library.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Extends attachment search so a query also matches `moap_media_tag` terms.
 */
final class MediaSearch {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'posts_search', array( $this, 'add_tag_matches' ), 10, 2 );
	}

	/**
	 * Splice matching attachment IDs into the search WHERE clause.
	 *
	 * @param string    $search The search SQL fragment.
	 * @param \WP_Query $query  The query.
	 * @return string
	 */
	public function add_tag_matches( string $search, \WP_Query $query ): string {
		$term = (string) $query->get( 's' );
		if ( '' === trim( $term ) ) {
			return $search;
		}
		if ( 'attachment' !== $query->get( 'post_type' ) ) {
			return $search;
		}

		$ids = $this->matching_ids( $term );
		if ( empty( $ids ) ) {
			return $search;
		}

		global $wpdb;
		$in = implode( ',', array_map( 'absint', $ids ) );

		if ( '' === $search ) {
			return " AND {$wpdb->posts}.ID IN ($in) ";
		}

		// $search reliably starts with " AND (" — open an OR group inside it.
		return (string) preg_replace(
			'/^ AND \(/',
			" AND ({$wpdb->posts}.ID IN ($in) OR ",
			$search,
			1
		);
	}

	/**
	 * Attachment IDs whose media tags match the search term.
	 *
	 * @param string $term Search term.
	 * @return int[]
	 */
	private function matching_ids( string $term ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => MediaTaxonomy::TAXONOMY,
				'name__like' => $term,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$query = new \WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Bounded admin search.
					array(
						'taxonomy' => MediaTaxonomy::TAXONOMY,
						'terms'    => $terms,
					),
				),
			)
		);

		return array_map( 'intval', $query->posts );
	}
}
