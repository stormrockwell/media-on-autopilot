<?php
/**
 * Runs a single sample vision call for the AI test button (no persistence).
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

use MediaOnAutopilot\Support\SampleImage;

defined( 'ABSPATH' ) || exit;

/**
 * Sends the bundled sample image to the vision client and returns its reading.
 */
final class AiTester {

	/**
	 * Sets up the tester with the AI pipeline collaborators.
	 *
	 * @param VisionClient   $client    Vision backend.
	 * @param ResponseSchema $schema    Prompt + schema + max tags.
	 * @param Connector      $connector Availability gate.
	 */
	public function __construct(
		private VisionClient $client,
		private ResponseSchema $schema,
		private Connector $connector
	) {}

	/**
	 * Run the sample call.
	 *
	 * @return array{state:string,alt:string,tags:string[],ms:int,message:string}
	 */
	public function run(): array {
		if ( ! $this->connector->is_available() ) {
			return $this->result( 'unconfigured', '', array(), 0, __( 'Connect an AI provider in core AI settings first.', 'media-on-autopilot' ) );
		}

		$start    = microtime( true );
		$response = $this->client->describe( SampleImage::path(), $this->schema->prompt(), $this->schema->schema() );
		$ms       = (int) round( ( microtime( true ) - $start ) * 1000 );

		if ( is_wp_error( $response ) ) {
			return $this->result( 'error', '', array(), $ms, $response->get_error_message() );
		}

		$suggestion = TagSuggestion::from_response( $response, $this->schema->target_tag_count() );

		$meta = $this->client instanceof NativeVisionClient ? $this->client->last_meta() : null;

		return $this->result(
			'ok',
			$suggestion->alt,
			$suggestion->tags,
			$ms,
			__( 'The model responded.', 'media-on-autopilot' ),
			$meta['model'] ?? '',
			$meta['total_tokens'] ?? 0
		);
	}

	/**
	 * Shape a result array.
	 *
	 * @param string   $state   ok|error|unconfigured.
	 * @param string   $alt     Alt text.
	 * @param string[] $tags    Tags.
	 * @param int      $ms      Elapsed ms.
	 * @param string   $message Human message.
	 * @param string   $model   Model id that answered, when known.
	 * @param int      $tokens  Total tokens used, when known.
	 * @return array{state:string,alt:string,tags:string[],ms:int,message:string,model:string,tokens:int}
	 */
	private function result( string $state, string $alt, array $tags, int $ms, string $message, string $model = '', int $tokens = 0 ): array {
		return array(
			'state'   => $state,
			'alt'     => $alt,
			'tags'    => $tags,
			'ms'      => $ms,
			'message' => $message,
			'model'   => $model,
			'tokens'  => $tokens,
		);
	}
}
