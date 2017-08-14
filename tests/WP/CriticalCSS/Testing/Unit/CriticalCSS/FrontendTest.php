<?php


namespace WP\CriticalCSS\Testing\Unit\CriticalCSS;


use WP\CriticalCSS\Testing\Unit\TestCase;

class FrontendTest extends TestCase {

	public function test_init_print_styles_hook() {
		\WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );
		\WP_Mock::expectActionAdded( 'wp_print_styles', [
			wp_criticalcss()->get_frontend(),
			'print_styles',
		], 7 );
		wp_criticalcss()->get_frontend()->init();
	}

}