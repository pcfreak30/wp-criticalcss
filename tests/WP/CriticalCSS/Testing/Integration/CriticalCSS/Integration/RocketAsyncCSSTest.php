<?php

namespace WP\CriticalCSS\Testing\Integration\CriticalCSS\Integration;

use WP\CriticalCSS\Testing\Integration\TestCase;

/**
 * @group rocket-async-css
 */
class RocketAsyncCSSTest extends TestCase {

	public function test_cache_exists() {
		global $wp;
		WPCCSS()->init();
		do_action_ref_array( 'wp', [ &$wp ] );
		$this->assertEquals(
			10, has_action(
				'wp_enqueue_scripts', [
					'Rocket_Async_Css_The_Preloader',
					'add_window_resize_js',
				]
			)
		);
		$this->assertEquals(
			10, has_action(
				'rocket_buffer', [
					'Rocket_Async_Css_The_Preloader',
					'inject_div',
				]
			)
		);
		do_action( 'wp_criticalcss_before_print_styles', 'not empty' );

		$this->assertFalse(
			has_action(
				'wp_enqueue_scripts', [
					'Rocket_Async_Css_The_Preloader',
					'add_window_resize_js',
				]
			)
		);
		$this->assertFalse( has_action( 'rocket_buffer', [
			'Rocket_Async_Css_The_Preloader',
			'inject_div',
		] ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_nocache_page() {
		WPCCSS()->init();
		do_action( 'init' );
		flush_rewrite_rules();
		$this->go_to( home_url( '/nocache' ) );
		$this->assertFalse(
			has_action(
				'wp_enqueue_scripts', [
					'Rocket_Async_Css_The_Preloader',
					'add_window_resize_js',
				]
			)
		);
		$this->assertFalse( has_action( 'rocket_buffer', [
			'Rocket_Async_Css_The_Preloader',
			'inject_div',
		] ) );
		$this->assertTrue( defined( 'DONOTCACHEPAGE' ) );
	}
}