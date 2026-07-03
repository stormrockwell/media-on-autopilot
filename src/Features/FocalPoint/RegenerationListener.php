<?php
/**
 * Retriggers native regeneration whenever the focal point meta changes.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\FocalPoint;

defined( 'ABSPATH' ) || exit;

/**
 * Listens for focal-point meta changes and retriggers thumbnail regeneration.
 */
final class RegenerationListener {

	/**
	 * Attachment IDs already regenerated this request, to avoid repeated
	 * synchronous regeneration of the same image within one request.
	 *
	 * @var array<int, true>
	 */
	private array $done = array();

	/**
	 * Constructor.
	 *
	 * @param Regenerator $regenerator Regenerator instance.
	 */
	public function __construct( private Regenerator $regenerator ) {}

	/**
	 * Register WordPress action hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'added_post_meta', array( $this, 'on_change' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'on_change' ), 10, 4 );
	}

	/**
	 * Handle a post meta change and retrigger regeneration if the focal point key changed.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Post (attachment) ID.
	 * @param string $meta_key   Meta key that was changed.
	 * @param mixed  $meta_value New meta value.
	 * @return void
	 */
	public function on_change( int $meta_id, int $object_id, string $meta_key, $meta_value ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by WordPress hook signature.
		if ( FocalPointMeta::META_KEY !== $meta_key ) {
			return;
		}
		if ( 'attachment' !== get_post_type( $object_id ) ) {
			return;
		}
		// Full thumbnail regeneration is expensive and synchronous; never run it more
		// than once per attachment per request, however many meta writes occur.
		if ( isset( $this->done[ $object_id ] ) ) {
			return;
		}
		$this->done[ $object_id ] = true;

		$this->regenerator->regenerate( $object_id );
	}
}
