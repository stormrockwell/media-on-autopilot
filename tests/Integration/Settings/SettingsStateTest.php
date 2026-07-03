<?php
declare( strict_types=1 );

namespace MediaOnAutopilot\Tests\Integration\Settings;

use MediaOnAutopilot\Support\Settings\SettingsState;
use WP_UnitTestCase;

final class SettingsStateTest extends WP_UnitTestCase {

	public function test_to_array_has_values_status_and_tools(): void {
		$state = ( new SettingsState() )->to_array();

		$this->assertArrayHasKey( 'values', $state );
		$this->assertArrayHasKey( 'status', $state );
		$this->assertArrayHasKey( 'tools', $state );
		$this->assertArrayHasKey( 'moap_cdn_provider', $state['values'] );
		$this->assertArrayHasKey( 'cdnConfigured', $state['status'] );
		$this->assertIsArray( $state['tools'] );
	}

	public function test_values_reflect_stored_options(): void {
		update_option( 'moap_cdn_provider', 'bunny' );
		update_option( 'moap_bunnycdn_hostname', 'x.b-cdn.net' );

		$state = ( new SettingsState() )->to_array();

		$this->assertSame( 'bunny', $state['values']['moap_cdn_provider'] );
		$this->assertSame( 'x.b-cdn.net', $state['values']['moap_bunnycdn_hostname'] );
	}

	public function test_api_token_is_never_exposed_but_set_flag_is(): void {
		update_option( 'moap_cloudflare_api_token', 'secret-token-value' );

		$state = ( new SettingsState() )->to_array();

		$this->assertSame( '', $state['values']['moap_cloudflare_api_token'] );
		$this->assertArrayHasKey( 'secretsSet', $state );
		$this->assertTrue( $state['secretsSet']['moap_cloudflare_api_token'] );
	}

	public function test_secret_set_flag_is_false_when_token_blank(): void {
		delete_option( 'moap_cloudflare_api_token' );

		$state = ( new SettingsState() )->to_array();

		$this->assertFalse( $state['secretsSet']['moap_cloudflare_api_token'] );
	}

	public function test_tools_collects_filter_contributions(): void {
		add_filter(
			'moap_settings_tools',
			static function ( array $tools ): array {
				$tools[] = array( 'slug' => 'demo' );
				return $tools;
			}
		);

		$state = ( new SettingsState() )->to_array();
		$slugs = array_column( $state['tools'], 'slug' );

		$this->assertContains( 'demo', $slugs );
	}

	public function test_cdn_save_returns_recomputed_status(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$controller = new \MediaOnAutopilot\Support\Settings\SettingsRestController();
		do_action( 'rest_api_init' );
		$controller->register_routes();

		$request = new \WP_REST_Request( 'POST', '/moap/v1/settings/cdn' );
		$request->set_param( 'moap_cdn_provider', 'bunny' );
		$request->set_param( 'moap_bunnycdn_hostname', 'mysite.b-cdn.net' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'status', $data );
		$this->assertTrue( $data['status']['cdnConfigured'] );
		$this->assertSame( 'bunny', $data['values']['moap_cdn_provider'] );
	}
}
