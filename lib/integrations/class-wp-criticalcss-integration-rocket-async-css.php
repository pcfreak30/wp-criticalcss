<?php


/**
 * Class WP_CriticalCSS_Integration_Rocket_Async_CSS
 */
class WP_CriticalCSS_Integration_Rocket_Async_CSS extends WP_CriticalCSS_Integration_Base {

	/**
	 * WP_CriticalCSS_Integration_Rocket_Async_CSS constructor.
	 */
	public function __construct() {
		if ( class_exists( 'Rocket_Async_Css' ) ) {
			parent::__construct();
		}
	}

	/**
	 * @return void
	 */
	public function enable() {
		if ( get_query_var( 'nocache' ) ) {
			remove_action( 'wp_enqueue_scripts', array(
				'Rocket_Async_Css_The_Preloader',
				'add_window_resize_js',
			) );
			remove_action( 'rocket_buffer', array( 'Rocket_Async_Css_The_Preloader', 'inject_div' ) );
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
		}
		add_action( 'wp_criticalcss_print_styles_cache', array( $this, '_purge_cache' ) );
	}

	/**
	 * @return void
	 */
	public function disable() {
		remove_action( 'wp_criticalcss_print_styles_cache', array( $this, '_purge_cache' ) );
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 * @param $cache
	 */
	private function _purge_cache( $cache ) {
		if ( ! empty( $cache ) ) {
			remove_action( 'wp_enqueue_scripts', array(
				'Rocket_Async_Css_The_Preloader',
				'add_window_resize_js',
			) );
			remove_action( 'rocket_buffer', array( 'Rocket_Async_Css_The_Preloader', 'inject_div' ) );
		}
	}
}