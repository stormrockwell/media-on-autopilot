<?php
/**
 * Vision client backed by the native WP 7.0 AI Client.
 *
 * @package MediaOnAutopilot
 */

declare( strict_types=1 );

namespace MediaOnAutopilot\Features\AiTagging;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps `wp_ai_client_prompt()` behind the VisionClient contract.
 */
final class NativeVisionClient implements VisionClient {

	/**
	 * Model id + token usage from the most recent describe() call.
	 *
	 * @var array{model:string,prompt_tokens:int,completion_tokens:int,total_tokens:int}|null
	 */
	private ?array $last_meta = null;

	/**
	 * Model and token usage from the last successful call, or null if none yet.
	 *
	 * @return array{model:string,prompt_tokens:int,completion_tokens:int,total_tokens:int}|null
	 */
	public function last_meta(): ?array {
		return $this->last_meta;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string               $file_path Absolute path to a resized image.
	 * @param string               $prompt    Instruction text.
	 * @param array<string, mixed> $schema    JSON response schema.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function describe( string $file_path, string $prompt, array $schema ) {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return new \WP_Error(
				'moap_ai_unavailable',
				__( 'No AI provider is available.', 'media-on-autopilot' )
			);
		}

		/**
		 * Filters the preferred vision models, in priority order. The first model
		 * a configured provider exposes is used.
		 *
		 * @param list<string> $models Model IDs in preference order.
		 */
		$models = (array) apply_filters(
			'moap_ai_tagging_model_preference',
			array( 'gpt-5.4-mini', 'gemini-3.1-flash-lite', 'claude-haiku-4-5', 'gpt-4o-mini' )
		);

		try {
			$result = wp_ai_client_prompt( $prompt )
				->using_model_preference( ...array_values( $models ) )
				->with_file( $file_path )
				->as_json_response( $schema )
				->generate_text_result();
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Detail kept server-side; the client gets a generic message.
				error_log( 'Media on Autopilot AI request failed: ' . $e->getMessage() );
			}
			return new \WP_Error(
				'moap_ai_request_failed',
				__( 'The AI request failed. Check your AI provider configuration.', 'media-on-autopilot' )
			);
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$usage           = $result->getTokenUsage();
		$this->last_meta = array(
			'model'             => $result->getModelMetadata()->getId(),
			'prompt_tokens'     => $usage->getPromptTokens(),
			'completion_tokens' => $usage->getCompletionTokens(),
			'total_tokens'      => $usage->getTotalTokens(),
		);

		$decoded = json_decode( $result->toText(), true );
		if ( ! is_array( $decoded ) ) {
			return new \WP_Error(
				'moap_ai_bad_json',
				__( 'The AI response could not be parsed.', 'media-on-autopilot' )
			);
		}

		return $decoded;
	}
}
