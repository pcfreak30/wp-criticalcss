<?php

/**
 * @group rocket-async-css
 */
class Test_WP_CriticalCSS_Integration_Rocket_Async_CSS extends WP_CriticalCSS_TestCase {

	public function test_cache_exists() {
		global $wp;
		WPCCSS()->init();
		WPCCSS()->setup_integrations( true );
		do_action_ref_array( 'wp', array( &$wp ) );
		$this->assertEquals( 10, has_action( 'wp_enqueue_scripts', array(
			'Rocket_Async_Css_The_Preloader',
			'add_window_resize_js',
		) ) );
		$this->assertEquals( 10, has_action( 'rocket_buffer', array(
			'Rocket_Async_Css_The_Preloader',
			'inject_div',
		) ) );
		do_action( 'wp_criticalcss_before_print_styles', 'not empty' );

		$this->assertFalse( has_action( 'wp_enqueue_scripts', array(
			'Rocket_Async_Css_The_Preloader',
			'add_window_resize_js',
		) ) );
		$this->assertFalse( has_action( 'rocket_buffer', array( 'Rocket_Async_Css_The_Preloader', 'inject_div' ) ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_nocache_page() {
		WPCCSS()->init();
		do_action( 'init' );
		WPCCSS()->setup_integrations( true );
		flush_rewrite_rules();
		$this->go_to( home_url( '/nocache' ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', array(
			'Rocket_Async_Css_The_Preloader',
			'add_window_resize_js',
		) ) );
		$this->assertFalse( has_action( 'rocket_buffer', array( 'Rocket_Async_Css_The_Preloader', 'inject_div' ) ) );
		$this->assertTrue( defined( 'DONOTCACHEPAGE' ) );
	}
}