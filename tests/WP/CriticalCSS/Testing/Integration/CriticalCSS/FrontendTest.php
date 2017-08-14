<?php


namespace WP\CriticalCSS\Testing\Integration\CriticalCSS;


use WP\CriticalCSS\Testing\Integration\TestCase;

class FrontendTest extends TestCase {

	public function test_init_print_styles_hook() {
		wp_criticalcss()->init();
		$this->assertEquals( 7, has_action( 'wp_print_styles', [
			wp_criticalcss()->get_frontend(),
			'print_styles',
		] ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_init_print_styles_hook_admin() {
		define( 'WP_ADMIN', true );
		wp_criticalcss()->init();
		$this->assertFalse( has_action( 'wp_print_styles', [
			wp_criticalcss()->get_frontend(),
			'print_styles',
		] ) );
	}
}