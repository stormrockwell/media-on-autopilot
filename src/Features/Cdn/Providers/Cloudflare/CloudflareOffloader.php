<?php
/**
 * Background offloader: uploads originals to Cloudflare and syncs deletes.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\Cdn\Providers\Cloudflare;

use MediaOnAutopilot\Support\Batch\ProgressProcess;
use MediaOnAutopilot\Support\Batch\ProgressState;
use MediaOnAutopilot\Support\Batch\ProgressStore;

defined( 'ABSPATH' ) || exit;

/**
 * Queues attachment uploads to Cloudflare Images and deletes on removal.
 */
final class CloudflareOffloader extends ProgressProcess {

	public const SLUG = 'cloudflare_offload';

	/**
	 * Queue action name (WP_Background_Process).
	 *
	 * @var string
	 */
	protected $action = 'cloudflare_offload';

	/**
	 * Sets up the offloader with Cloudflare credentials, API client, id store, and progress store.
	 *
	 * @param CloudflareConfig $config Settings (credentials + delivery options).
	 * @param ImagesApiClient  $api    Upload/delete client.
	 * @param ImageIdStore     $ids    Attachment id map.
	 * @param ProgressStore    $store  Progress persistence.
	 */
	public function __construct(
		private CloudflareConfig $config,
		private ImagesApiClient $api,
		private ImageIdStore $ids,
		ProgressStore $store
	) {
		parent::__construct( $store );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function slug(): string {
		return self::SLUG;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Offloading images to Cloudflare', 'media-on-autopilot' );
	}

	/**
	 * Hook attachment lifecycle.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_attachment', array( $this, 'on_add' ) );
		add_action( 'delete_attachment', array( $this, 'on_delete' ) );
	}

	/**
	 * Enqueue one new image on upload.
	 *
	 * @param int $attachment_id New attachment ID.
	 * @return void
	 */
	public function on_add( int $attachment_id ): void {
		if ( wp_attachment_is_image( $attachment_id ) ) {
			$this->start( array( array( 'id' => $attachment_id ) ) );
		}
	}

	/**
	 * Delete the remote copy + mapping when an attachment is removed.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function on_delete( int $attachment_id ): void {
		$image_id = $this->ids->get( $attachment_id );
		if ( '' === $image_id ) {
			return;
		}
		try {
			$this->api->delete( $image_id );
		} catch ( \RuntimeException $e ) {
			return; // Leave the mapping so a later retry/tool can reconcile.
		}
		$this->ids->clear( $attachment_id );
	}

	/**
	 * Whether Cloudflare credentials are present (offload can run).
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return $this->config->is_active();
	}

	/**
	 * Queue every image attachment for a full re-sync. Images that already have a
	 * stored Cloudflare id are re-verified in handle_item (skipped if the remote
	 * still exists, re-uploaded if it was deleted out-of-band), so running the
	 * tool repairs a library where images were removed from Cloudflare directly.
	 *
	 * @return int Number queued.
	 */
	public function enqueue_all(): int {
		$ids   = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'nopaging'       => true,
			)
		);
		$items = array_map( static fn( $id ) => array( 'id' => (int) $id ), $ids );
		$this->start( $items );

		return count( $items );
	}

	/**
	 * Test/seam wrapper to process one item synchronously.
	 *
	 * @param array<string, mixed> $item Queue item.
	 * @return string
	 */
	public function process_item( array $item ): string {
		return $this->handle_item( $item );
	}

	/**
	 * Upload one attachment to Cloudflare Images and store its CF id. Local
	 * originals are always kept — the offload is purely additive.
	 *
	 * @param array<string, mixed> $item Queue item with 'id'.
	 * @return string One of ProgressState::OUTCOME_*.
	 */
	protected function handle_item( array $item ): string {
		$id = (int) ( $item['id'] ?? 0 );
		if ( $id <= 0 || ! wp_attachment_is_image( $id ) ) {
			return ProgressState::OUTCOME_FAILED;
		}

		$existing_id = $this->ids->get( $id );
		if ( '' !== $existing_id ) {
			// A stored id can be stale if the image was deleted from Cloudflare
			// out-of-band. Verify the remote still exists before skipping; if it is
			// gone, clear the id and fall through to re-upload.
			if ( $this->api->exists( $existing_id ) ) {
				return ProgressState::OUTCOME_SKIPPED;
			}
			$this->ids->clear( $id );
		}

		$file = get_attached_file( $id );
		if ( ! is_string( $file ) || '' === $file || ! file_exists( $file ) ) {
			return ProgressState::OUTCOME_FAILED;
		}

		try {
			$image_id = $this->api->upload( $file );
		} catch ( \RuntimeException $e ) {
			return ProgressState::OUTCOME_FAILED;
		}

		$this->ids->set( $id, $image_id );

		if ( '' === $this->config->account_hash ) {
			$hash = $this->api->account_hash_from_last_upload();
			if ( '' !== $hash ) {
				update_option( CloudflareSettings::OPTION_HASH, $hash );
			}
		}

		return ProgressState::OUTCOME_WRITTEN;
	}
}
