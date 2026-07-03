<?php
/**
 * Frontend image markup decoration: object-position and cache-busting URLs.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Applies focal-point-aware object-position to front-end attachment images.
 */
final class Frontend {

	/**
	 * Constructor.
	 *
	 * @param FocalPointMeta $meta Focal point meta storage.
	 */
	public function __construct( private FocalPointMeta $meta ) {}

	/**
	 * Registers the wp_get_attachment_image_attributes filter.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_object_position' ), 10, 3 );
		add_filter( 'wp_content_img_tag', array( $this, 'add_object_position_to_content_image' ), 10, 3 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'add_cache_bust_to_src' ), 10, 4 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'add_cache_bust_to_srcset' ), 10, 5 );
	}

	/**
	 * Adds an object-position style when the focal point is off-center.
	 *
	 * @param array<string, string> $attr       Image element attributes.
	 * @param \WP_Post              $attachment The attachment post object.
	 * @param mixed                 $size       Requested image size.
	 * @return array<string, string>
	 */
	public function add_object_position( array $attr, \WP_Post $attachment, $size ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by wp_get_attachment_image_attributes filter signature.
		$focal = $this->meta->get( $attachment->ID );
		if ( $focal->is_center() ) {
			return $attr;
		}

		$position      = $this->position_style( $focal );
		$attr['style'] = isset( $attr['style'] )
			? rtrim( $attr['style'], '; ' ) . '; ' . $position
			: $position;

		return $attr;
	}

	/**
	 * Adds an object-position style to a content/block image tag.
	 *
	 * Block images (core/image, core/gallery) are static HTML in post content and
	 * never pass through wp_get_attachment_image_attributes, so they are decorated
	 * here via the wp_content_img_tag filter instead.
	 *
	 * @param string $filtered_image The full img tag.
	 * @param string $context        The context the image is being filtered for.
	 * @param int    $attachment_id  The attachment ID, or 0 if not an attachment.
	 * @return string
	 */
	public function add_object_position_to_content_image( string $filtered_image, string $context, int $attachment_id ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- $context is required by the wp_content_img_tag filter signature.
		if ( $attachment_id <= 0 ) {
			return $filtered_image;
		}

		$focal = $this->meta->get( $attachment_id );
		if ( $focal->is_center() ) {
			return $filtered_image;
		}

		// Block images keep the same filenames after a focal point change, so bust
		// the src URL the same way wp_get_attachment_image_src is busted. srcset is
		// handled separately via wp_calculate_image_srcset. add_query_arg is
		// idempotent, so filtering the same tag twice is harmless.
		$filtered_image = $this->bust_img_src( $filtered_image, $attachment_id );

		// Idempotent: core filters the_content's images more than once, and the
		// author may have set their own object-position. Either way, never add a
		// second one.
		if ( str_contains( $filtered_image, 'object-position' ) ) {
			return $filtered_image;
		}

		return $this->merge_inline_style( $filtered_image, $this->position_style( $focal ) );
	}

	/**
	 * Cache-busts the src attribute of an img tag.
	 *
	 * @param string $img           The img tag.
	 * @param int    $attachment_id The attachment ID.
	 * @return string
	 */
	private function bust_img_src( string $img, int $attachment_id ): string {
		return (string) preg_replace_callback(
			'/(\ssrc\s*=\s*)("|\')(.*?)\2/i',
			fn( array $matches ): string => $matches[1] . $matches[2] . $this->bust_url( $matches[3], $attachment_id ) . $matches[2],
			$img,
			1
		);
	}

	/**
	 * Builds the object-position declaration for a focal point.
	 *
	 * @param FocalPoint $focal The focal point.
	 * @return string
	 */
	private function position_style( FocalPoint $focal ): string {
		return sprintf( 'object-position: %d%% %d%%;', $focal->x_percent(), $focal->y_percent() );
	}

	/**
	 * Merges a style declaration into an img tag, preserving any existing style.
	 *
	 * @param string $img   The img tag.
	 * @param string $style The style declaration to add (e.g. "object-position: 25% 75%;").
	 * @return string
	 */
	private function merge_inline_style( string $img, string $style ): string {
		if ( preg_match( '/\sstyle\s*=\s*("|\')(.*?)\1/i', $img, $matches ) ) {
			$existing    = rtrim( trim( $matches[2] ), ';' );
			$combined    = '' === $existing ? $style : $existing . '; ' . $style;
			$replacement = ' style=' . $matches[1] . $combined . $matches[1];
			return str_replace( $matches[0], $replacement, $img );
		}

		return preg_replace( '/<img\s/i', '<img style="' . $style . '" ', $img, 1 );
	}

	/**
	 * Appends a cache-busting query parameter to attachment image src URLs.
	 *
	 * @param array|false $image         Array of image data, or false.
	 * @param int         $attachment_id The attachment ID.
	 * @param mixed       $size          Requested image size.
	 * @param bool        $icon          Whether the image should be treated as an icon.
	 * @return array|false
	 */
	public function add_cache_bust_to_src( $image, int $attachment_id, $size, bool $icon ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by wp_get_attachment_image_src filter signature.
		if ( ! is_array( $image ) || empty( $image[0] ) ) {
			return $image;
		}
		$image[0] = $this->bust_url( $image[0], $attachment_id );
		return $image;
	}

	/**
	 * Appends a cache-busting query parameter to srcset source URLs.
	 *
	 * @param array<int, array<string, mixed>> $sources       Array of image sources.
	 * @param array<int, int>                  $size_array    Width and height of the image.
	 * @param string                           $image_src     The 'src' of the image.
	 * @param array<string, mixed>             $image_meta    The image meta data.
	 * @param int                              $attachment_id The attachment ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function add_cache_bust_to_srcset( array $sources, array $size_array, string $image_src, array $image_meta, int $attachment_id ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed -- Required by wp_calculate_image_srcset filter signature.
		foreach ( $sources as $key => $source ) {
			if ( ! empty( $source['url'] ) ) {
				$sources[ $key ]['url'] = $this->bust_url( $source['url'], $attachment_id );
			}
		}
		return $sources;
	}

	/**
	 * Appends ?moap=xPercent,yPercent to a URL when the focal point is off-center.
	 *
	 * @param string $url           The image URL.
	 * @param int    $attachment_id The attachment ID.
	 * @return string
	 */
	private function bust_url( string $url, int $attachment_id ): string {
		$focal = $this->meta->get( $attachment_id );
		if ( $focal->is_center() ) {
			return $url;
		}
		/**
		 * Filters whether to append the focal-point cache-busting query param.
		 *
		 * A CDN that encodes the focal point in its own params (e.g. BunnyCDN)
		 * returns false here to suppress the redundant param.
		 *
		 * @param bool $bust          Whether to append the ?moap cache-bust param.
		 * @param int  $attachment_id Attachment ID.
		 */
		if ( ! apply_filters( 'moap_focal_point_cache_bust', true, $attachment_id ) ) {
			return $url;
		}
		return add_query_arg( 'moap', $focal->x_percent() . ',' . $focal->y_percent(), $url );
	}
}
