<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Support;

use MediaOnAutopilot\Features\AiTagging\AutoTagSetting;
use MediaOnAutopilot\Features\Cdn\CdnSettings;
use MediaOnAutopilot\Features\Cdn\Providers\Bunny\BunnySettings;
use MediaOnAutopilot\Features\Cdn\Providers\Cloudflare\CloudflareSettings;
use MediaOnAutopilot\Support\Settings\SettingsRestController;
use WP_REST_Request;
use WP_UnitTestCase;

final class SettingsRestControllerTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		( new SettingsRestController() )->register();
		do_action( 'rest_api_init' );
	}

	private function save( string $section, array $body, int $user ): \WP_REST_Response {
		wp_set_current_user( $user );
		$req = new WP_REST_Request( 'POST', '/moap/v1/settings/' . $section );
		foreach ( $body as $k => $v ) {
			$req->set_param( $k, $v );
		}
		// Nonce (X-WP-Nonce) is enforced by WP's REST cookie-auth middleware for browser clients; internal dispatch here exercises capability only.
		return rest_get_server()->dispatch( $req );
	}

	public function test_cdn_save_persists_and_clamps_quality(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$res   = $this->save( 'cdn', array(
			CdnSettings::OPTION_PROVIDER  => 'bunny',
			BunnySettings::OPTION_HOST    => 'mysite.b-cdn.net',
			BunnySettings::OPTION_QUALITY => 999,
		), $admin );

		$this->assertSame( 200, $res->get_status() );
		$this->assertSame( 'bunny', get_option( CdnSettings::OPTION_PROVIDER ) );
		$this->assertSame( 100, (int) get_option( BunnySettings::OPTION_QUALITY ) );
	}

	public function test_cdn_save_does_not_touch_ai_options(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		update_option( AutoTagSetting::OPTION, '1' );

		$this->save( 'cdn', array( CdnSettings::OPTION_PROVIDER => 'none' ), $admin );

		$this->assertSame( '1', get_option( AutoTagSetting::OPTION ) );
	}

	public function test_editor_is_forbidden(): void {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$res    = $this->save( 'cdn', array( CdnSettings::OPTION_PROVIDER => 'bunny' ), $editor );
		$this->assertSame( 403, $res->get_status() );
	}

	public function test_new_api_token_is_persisted(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->save( 'cdn', array( CloudflareSettings::OPTION_TOKEN => 'fresh-token' ), $admin );

		$this->assertSame( 'fresh-token', get_option( CloudflareSettings::OPTION_TOKEN ) );
	}

	public function test_blank_api_token_keeps_stored_value(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		update_option( CloudflareSettings::OPTION_TOKEN, 'existing-token' );

		// A blank token field means "keep the saved secret", not "clear it".
		$this->save( 'cdn', array( CloudflareSettings::OPTION_TOKEN => '' ), $admin );

		$this->assertSame( 'existing-token', get_option( CloudflareSettings::OPTION_TOKEN ) );
	}

	public function test_non_scalar_host_param_does_not_error(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );

		// An array where a string is expected must be skipped, not crash the strict-typed sanitizer.
		$res = $this->save( 'cdn', array( BunnySettings::OPTION_HOST => array( 'evil' ) ), $admin );

		$this->assertSame( 200, $res->get_status() );
	}

	/**
	 * is_scalar() passes bool/int/float, which would still throw a TypeError against the
	 * strict-typed host sanitizer; the controller must coerce to string before dispatch.
	 *
	 * @dataProvider non_string_scalars
	 * @param mixed $value Non-string scalar value.
	 */
	public function test_non_string_scalar_host_param_does_not_error( $value ): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$res = $this->save( 'cdn', array( BunnySettings::OPTION_HOST => $value ), $admin );

		$this->assertSame( 200, $res->get_status() );
	}

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public function non_string_scalars(): array {
		return array(
			'int'   => array( 5 ),
			'float' => array( 1.5 ),
			'bool'  => array( true ),
		);
	}
}
