<?php
/**
 * Front-end image rewriting through the active CDN provider.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn;

use MediaOnAutopilot\Features\FocalPoint\FocalPointMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Rewrites front-end attachment image URLs to the active CDN provider and lets
 * it resize on the fly.
 */
final class ImageFrontend {

	/**
	 * Constructor.
	 *
	 * @param ImageProvider  $provider Active CDN provider.
	 * @param FocalPointMeta $meta     Focal point storage (for crop gravity).
	 */
	public function __construct(
		private ImageProvider $provider,
		private FocalPointMeta $meta
	) {}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'image_downsize', array( $this, 'downsize' ), 10, 3 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'synthesize_srcset' ), 9, 5 );
		add_filter( 'wp_get_attachment_url', array( $this, 'rewrite_attachment_url' ), 10, 2 );
		add_filter( 'wp_content_img_tag', array( $this, 'rewrite_content_image' ), 10, 3 );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_attachment_image_srcset' ), 10, 3 );
	}

	/**
	 * Whether the current request should have its image URLs rewritten.
	 *
	 * @return bool
	 */
	public function should_rewrite(): bool {
		if ( is_admin() ) {
			return false;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		if ( wp_is_serving_rest_request() ) {
			return false;
		}
		if ( is_customize_preview() || is_feed() ) {
			return false;
		}

		return true;
	}

	/**
	 * Short-circuit core sizing and return a CDN URL.
	 *
	 * @param mixed        $value         Pre-empt value (false to proceed).
	 * @param int          $attachment_id Attachment ID.
	 * @param string|int[] $size          Requested size name or [w, h].
	 * @return array{0:string,1:int,2:int,3:bool}|false
	 */
	public function downsize( $value, int $attachment_id, $size ) {
		if ( ! $this->should_rewrite() || ! wp_attachment_is_image( $attachment_id ) ) {
			return false;
		}

		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! is_array( $meta ) || empty( $meta['width'] ) ) {
			return false;
		}
		$orig_w = (int) $meta['width'];
		$orig_h = (int) $meta['height'];

		$url = wp_get_attachment_url( $attachment_id );
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}

		[ $req_w, $req_h, $crop, $is_full ] = $this->resolve_dimensions( $size, $orig_w, $orig_h );

		[ $display_w, $display_h ] = $crop
			? array( $req_w, $req_h )
			: wp_constrain_dimensions( $orig_w, $orig_h, $req_w, $req_h );

		$target = WidthLadder::target_width( $display_w, $orig_w, $this->max_width() );
		$focal  = $this->meta->get( $attachment_id );

		$transform = new ImageTransform(
			$attachment_id,
			$target,
			$crop ? (int) round( $target * ( $display_h / max( 1, $display_w ) ) ) : 0,
			$crop,
			$focal,
			$orig_w,
			$orig_h
		);

		/**
		 * Filters the transform spec before the active CDN provider builds the URL.
		 *
		 * @param ImageTransform $transform     Requested transform + attachment context.
		 * @param int            $attachment_id Attachment ID.
		 * @param string|int[]   $size          Requested size name or [ width, height ].
		 */
		$transform = apply_filters( 'moap_cdn_image_transform', $transform, $attachment_id, $size );

		$cdn = $this->provider->build_url( $url, $transform );

		return array( $cdn, $display_w, $display_h, ! $is_full );
	}

	/**
	 * Resolve requested width/height/crop for a size request.
	 *
	 * @param string|int[] $size   Size name or [w, h].
	 * @param int          $orig_w Original width.
	 * @param int          $orig_h Original height.
	 * @return array{0:int,1:int,2:bool,3:bool} [ width, height, crop, is_full ]
	 */
	private function resolve_dimensions( $size, int $orig_w, int $orig_h ): array {
		if ( is_array( $size ) ) {
			return array( (int) $size[0], (int) ( $size[1] ?? 0 ), false, false );
		}
		if ( 'full' === $size ) {
			return array( $orig_w, $orig_h, false, true );
		}

		$sizes = wp_get_registered_image_subsizes();
		if ( isset( $sizes[ $size ] ) ) {
			return array(
				(int) $sizes[ $size ]['width'],
				(int) $sizes[ $size ]['height'],
				(bool) $sizes[ $size ]['crop'],
				false,
			);
		}

		return array( $orig_w, $orig_h, false, true );
	}

	/**
	 * Replace srcset candidates with a CDN-served width ladder built from
	 * the original, regardless of any local sub-sizes.
	 *
	 * @param array<int, array<string, mixed>> $sources       Existing sources.
	 * @param int[]                            $size_array    [ width, height ].
	 * @param string                           $image_src     Source URL.
	 * @param array<string, mixed>             $image_meta    Attachment meta.
	 * @param int                              $attachment_id Attachment ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function synthesize_srcset( $sources, $size_array, $image_src, $image_meta, int $attachment_id ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- $image_src is required by the filter signature.
		if ( ! $this->should_rewrite() || ! wp_attachment_is_image( $attachment_id ) ) {
			return is_array( $sources ) ? $sources : array();
		}

		$built = $this->build_sources(
			$attachment_id,
			(int) ( $image_meta['width'] ?? 0 ),
			(int) ( $image_meta['height'] ?? 0 ),
			(int) ( $size_array[0] ?? 0 ),
			(int) ( $size_array[1] ?? 0 )
		);

		if ( ! empty( $built ) ) {
			return $built;
		}

		return is_array( $sources ) ? $sources : array();
	}

	/**
	 * Build a CDN-served srcset source list from the original image, capped so
	 * variants are never upscaled. Independent of any local sub-sizes.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $orig_w        Original upload width.
	 * @param int $orig_h        Original upload height.
	 * @param int $box_w         Rendered box width (for crop detection).
	 * @param int $box_h         Rendered box height (for crop detection).
	 * @return array<int, array<string, mixed>> Empty when no ladder can be built.
	 */
	private function build_sources( int $attachment_id, int $orig_w, int $orig_h, int $box_w, int $box_h ): array {
		$url = wp_get_attachment_url( $attachment_id );
		if ( $orig_w <= 0 || ! is_string( $url ) || '' === $url ) {
			return array();
		}

		$crop  = $this->box_is_crop( $box_w, $box_h, $orig_w, $orig_h );
		$focal = $this->meta->get( $attachment_id );

		if ( $crop ) {
			// Cap candidates at the crop region width so variants are not upscaled.
			[ $region_w ] = $this->crop_region( $orig_w, $orig_h, $box_w, $box_h );
			$widths       = $this->provider->srcset_widths( $region_w, $this->max_width() );
		} else {
			$widths = $this->provider->srcset_widths( $orig_w, $this->max_width() );
		}

		/**
		 * Filters the synthesized srcset candidate widths.
		 *
		 * @param int[] $widths        Candidate widths in pixels, ascending.
		 * @param int   $attachment_id Attachment ID.
		 */
		$widths = (array) apply_filters( 'moap_cdn_srcset_widths', $widths, $attachment_id );

		$built = array();
		foreach ( $widths as $w ) {
			$w      = (int) $w;
			$crop_h = $crop ? (int) round( $w * ( $box_h / max( 1, $box_w ) ) ) : 0;
			$t      = new ImageTransform( $attachment_id, $w, $crop_h, $crop, $focal, $orig_w, $orig_h );

			$built[ $w ] = array(
				'url'        => $this->provider->build_url( $url, $t ),
				'descriptor' => 'w',
				'value'      => $w,
			);
		}

		return $built;
	}

	/**
	 * Rewrite a full-size image attachment URL to the CDN provider.
	 *
	 * @param string $url           Attachment URL.
	 * @param int    $attachment_id Attachment ID.
	 * @return string
	 */
	public function rewrite_attachment_url( string $url, int $attachment_id ): string {
		if ( ! $this->should_rewrite() || ! wp_attachment_is_image( $attachment_id ) ) {
			return $url;
		}
		if ( $this->provider->is_already_rewritten( $url ) ) {
			return $url;
		}

		$meta   = wp_get_attachment_metadata( $attachment_id );
		$orig_w = is_array( $meta ) ? (int) ( $meta['width'] ?? 0 ) : 0;
		$target = WidthLadder::target_width( $orig_w > 0 ? $orig_w : $this->max_width(), $orig_w, $this->max_width() );

		$transform = new ImageTransform( $attachment_id, $target, 0, false, null, $orig_w, 0 );

		return $this->provider->build_url( $url, $transform );
	}

	/**
	 * Rewrite the src of a content/block image tag to the CDN provider.
	 *
	 * @param string $filtered_image The img tag HTML.
	 * @param string $context        Filter context.
	 * @param int    $attachment_id  Attachment ID, or 0.
	 * @return string
	 */
	public function rewrite_content_image( string $filtered_image, string $context, int $attachment_id ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- $context is required by the wp_content_img_tag filter signature.
		if ( ! $this->should_rewrite() ) {
			return $filtered_image;
		}

		$box_w = $this->attr_int( $filtered_image, 'width' );
		$box_h = $this->attr_int( $filtered_image, 'height' );

		$filtered_image = (string) preg_replace_callback(
			'/(\ssrc\s*=\s*)("|\')(.*?)\2/i',
			function ( array $matches ) use ( $box_w, $box_h, $attachment_id ): string {
				return $matches[1] . $matches[2] . $this->rewrite_loose_url( $matches[3], $box_w, $box_h, $attachment_id ) . $matches[2];
			},
			$filtered_image,
			1
		);

		return $this->add_content_srcset( $filtered_image, $box_w, $box_h, $attachment_id );
	}

	/**
	 * Add a CDN srcset (and a default sizes) to a content image tag that has
	 * none. WordPress only builds a srcset when the attachment has local
	 * sub-sizes; this fills the gap so the responsive ladder works regardless.
	 *
	 * @param string $img           The img tag HTML (src already rewritten).
	 * @param int    $box_w         Rendered width attribute, or 0.
	 * @param int    $box_h         Rendered height attribute, or 0.
	 * @param int    $attachment_id Attachment ID, or 0.
	 * @return string
	 */
	private function add_content_srcset( string $img, int $box_w, int $box_h, int $attachment_id ): string {
		if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
			return $img;
		}
		if ( preg_match( '/\ssrcset\s*=/i', $img ) ) {
			return $img;
		}

		$meta    = wp_get_attachment_metadata( $attachment_id );
		$orig_w  = is_array( $meta ) ? (int) ( $meta['width'] ?? 0 ) : 0;
		$orig_h  = is_array( $meta ) ? (int) ( $meta['height'] ?? 0 ) : 0;
		$sources = $this->build_sources( $attachment_id, $orig_w, $orig_h, $box_w, $box_h );
		if ( ! $sources ) {
			return $img;
		}

		$add = sprintf( ' srcset="%s"', esc_attr( $this->srcset_attr( $sources ) ) );
		if ( ! preg_match( '/\ssizes\s*=/i', $img ) ) {
			$add .= sprintf( ' sizes="%s"', esc_attr( $this->default_sizes_attr( $box_w > 0 ? $box_w : $orig_w ) ) );
		}

		return (string) preg_replace( '/\s*(\/?>)\s*$/', $add . '$1', $img, 1 );
	}

	/**
	 * Add a CDN srcset/sizes to a template image (wp_get_attachment_image)
	 * that WordPress left without one for lack of local sub-sizes.
	 *
	 * @param array<string, string> $attr       Image tag attributes.
	 * @param \WP_Post              $attachment Attachment post.
	 * @param string|int[]          $size       Requested size.
	 * @return array<string, string>
	 */
	public function add_attachment_image_srcset( $attr, $attachment, $size ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- $size is required by the filter signature.
		if ( ! is_array( $attr ) ) {
			return array();
		}
		$id = $attachment instanceof \WP_Post ? (int) $attachment->ID : 0;
		if ( ! $this->should_rewrite() || ! empty( $attr['srcset'] ) || $id <= 0 || ! wp_attachment_is_image( $id ) ) {
			return $attr;
		}

		$box_w   = (int) ( $attr['width'] ?? 0 );
		$box_h   = (int) ( $attr['height'] ?? 0 );
		$meta    = wp_get_attachment_metadata( $id );
		$orig_w  = is_array( $meta ) ? (int) ( $meta['width'] ?? 0 ) : 0;
		$orig_h  = is_array( $meta ) ? (int) ( $meta['height'] ?? 0 ) : 0;
		$sources = $this->build_sources( $id, $orig_w, $orig_h, $box_w, $box_h );
		if ( ! $sources ) {
			return $attr;
		}

		$attr['srcset'] = $this->srcset_attr( $sources );
		if ( empty( $attr['sizes'] ) ) {
			$attr['sizes'] = $this->default_sizes_attr( $box_w > 0 ? $box_w : $orig_w );
		}

		return $attr;
	}

	/**
	 * Render a srcset attribute value from a source list.
	 *
	 * @param array<int, array<string, mixed>> $sources Source list from build_sources().
	 * @return string
	 */
	private function srcset_attr( array $sources ): string {
		$parts = array();
		foreach ( $sources as $source ) {
			$parts[] = $source['url'] . ' ' . $source['value'] . $source['descriptor'];
		}

		return implode( ', ', $parts );
	}

	/**
	 * Default sizes attribute mirroring WordPress core for a given display width.
	 *
	 * @param int $display_w Rendered width in pixels.
	 * @return string
	 */
	private function default_sizes_attr( int $display_w ): string {
		if ( $display_w <= 0 ) {
			return '100vw';
		}

		return sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $display_w );
	}

	/**
	 * Rewrite a bare URL found in content HTML to a CDN URL.
	 *
	 * For a known attachment the original (full) file is served — never an
	 * embedded intermediate — so the provider can resize and crop from full resolution.
	 *
	 * @param string $url           The image URL.
	 * @param int    $box_w         Rendered width attribute, or 0.
	 * @param int    $box_h         Rendered height attribute, or 0.
	 * @param int    $attachment_id Attachment ID, or 0.
	 * @return string
	 */
	private function rewrite_loose_url( string $url, int $box_w, int $box_h, int $attachment_id ): string {
		if ( '' === $url || $this->provider->is_already_rewritten( $url ) ) {
			return $url;
		}

		$orig_w = 0;
		$orig_h = 0;
		$base   = $url;
		if ( $attachment_id > 0 ) {
			$meta = wp_get_attachment_metadata( $attachment_id );
			if ( is_array( $meta ) && ! empty( $meta['width'] ) ) {
				$orig_w   = (int) $meta['width'];
				$orig_h   = (int) ( $meta['height'] ?? 0 );
				$original = $this->original_url( $meta );
				if ( '' !== $original ) {
					$base = $original;
				}
			}
		}

		if ( $base === $url ) {
			// No attachment original to map from: drop a WordPress size suffix
			// (e.g. -300x200) so the provider pulls the original, never a cropped sub-size.
			$base = (string) preg_replace( '/-\d+x\d+(?=\.[a-z]{3,4}$)/i', '', $url );
		}

		$crop      = $this->box_is_crop( $box_w, $box_h, $orig_w, $orig_h );
		$display_w = $box_w > 0 ? $box_w : ( $orig_w > 0 ? $orig_w : $this->max_width() );
		$target    = WidthLadder::target_width( $display_w, $orig_w, $this->max_width() );

		$transform = new ImageTransform(
			$attachment_id,
			$target,
			$crop ? (int) round( $target * ( $box_h / max( 1, $box_w ) ) ) : 0,
			$crop,
			$this->meta->get( $attachment_id ),
			$orig_w,
			$orig_h
		);

		return $this->provider->build_url( $base, $transform );
	}

	/**
	 * Whether a rendered box crops the source (its aspect ratio differs).
	 *
	 * @param int $box_w  Rendered width.
	 * @param int $box_h  Rendered height.
	 * @param int $orig_w Original width.
	 * @param int $orig_h Original height.
	 * @return bool
	 */
	private function box_is_crop( int $box_w, int $box_h, int $orig_w, int $orig_h ): bool {
		if ( $box_w <= 0 || $box_h <= 0 || $orig_w <= 0 || $orig_h <= 0 ) {
			return false;
		}

		return abs( ( $box_w / $box_h ) - ( $orig_w / $orig_h ) ) > 0.01;
	}

	/**
	 * Read an integer HTML attribute value from a tag.
	 *
	 * @param string $html The img tag HTML.
	 * @param string $name Attribute name.
	 * @return int
	 */
	private function attr_int( string $html, string $name ): int {
		if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '\s*=\s*["\'](\d+)["\']/i', $html, $m ) ) {
			return (int) $m[1];
		}

		return 0;
	}

	/**
	 * Build the canonical full-size attachment URL from its metadata, bypassing
	 * this class's own wp_get_attachment_url rewrite.
	 *
	 * @param array<string, mixed> $meta Attachment metadata.
	 * @return string
	 */
	private function original_url( array $meta ): string {
		if ( empty( $meta['file'] ) ) {
			return '';
		}
		$uploads = wp_get_upload_dir();
		if ( empty( $uploads['baseurl'] ) ) {
			return '';
		}

		return $uploads['baseurl'] . '/' . ltrim( (string) $meta['file'], '/' );
	}

	/**
	 * The hard width ceiling, filterable.
	 *
	 * @return int
	 */
	private function max_width(): int {
		/**
		 * Filters the hard maximum width (in pixels) any rewritten image may request.
		 *
		 * @param int $max_width Default ceiling (WidthLadder::DEFAULT_MAX_WIDTH).
		 */
		return (int) apply_filters( 'moap_cdn_max_width', WidthLadder::DEFAULT_MAX_WIDTH );
	}

	/**
	 * Largest source-pixel region matching the target aspect ratio.
	 *
	 * @param int $orig_w Original width.
	 * @param int $orig_h Original height.
	 * @param int $t_w    Target width.
	 * @param int $t_h    Target height.
	 * @return array{0:int,1:int}
	 */
	private function crop_region( int $orig_w, int $orig_h, int $t_w, int $t_h ): array {
		if ( $orig_w <= 0 || $orig_h <= 0 || $t_w <= 0 || $t_h <= 0 ) {
			return array( max( 0, $t_w ), max( 0, $t_h ) );
		}
		$ratio = $t_w / $t_h;
		if ( ( $orig_w / $orig_h ) >= $ratio ) {
			return array( (int) round( $orig_h * $ratio ), $orig_h );
		}
		return array( $orig_w, (int) round( $orig_w / $ratio ) );
	}
}
