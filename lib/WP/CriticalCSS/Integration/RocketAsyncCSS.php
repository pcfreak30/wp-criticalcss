<?php

namespace WP\CriticalCSS\Integration;

/**
 * Class RocketAsyncCSS
 */
class RocketAsyncCSS extends IntegrationAbstract {

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
		add_action( 'wp_criticalcss_before_print_styles', array( $this, 'purge_cache' ) );
	}

	/**
	 * @return void
	 */
	public function disable() {
		remove_action( 'wp_criticalcss_before_print_styles', array( $this, 'purge_cache' ) );
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 * @param $cache
	 */
	public function purge_cache( $cache ) {
		if ( ! empty( $cache ) ) {
			remove_action( 'wp_enqueue_scripts', array(
				'Rocket_Async_Css_The_Preloader',
				'add_window_resize_js',
			) );
			remove_action( 'rocket_buffer', array( 'Rocket_Async_Css_The_Preloader', 'inject_div' ) );
		}
	}
}