<?php


namespace WP\CriticalCSS\Testing\Integration\CriticalCSS;


use WP\CriticalCSS\Testing\Integration\TestCase;

class FrontendTest extends TestCase {

	public function test_init_print_styles_hook() {
		WPCCSS()->init();
		$this->assertEquals( 7, has_action( 'wp_print_styles', [
			WPCCSS()->get_frontend(),
			'print_styles',
		] ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_init_print_styles_hook_admin() {
		define( 'WP_ADMIN', true );
		WPCCSS()->init();
		$this->assertFalse( has_action( 'wp_print_styles', [
			WPCCSS()->get_frontend(),
			'print_styles',
		] ) );
	}
}