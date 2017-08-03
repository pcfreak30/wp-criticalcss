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
		add_action( 'wp_criticalcss_nocache', [
			$this,
			'disable_preloader',
		] );
		add_action( 'wp_criticalcss_before_print_styles', [
			$this,
			'maybe_disable_preloader',
		] );
	}

	/**
	 * @return void
	 */
	public function disable() {
		remove_action( 'wp', [
			$this,
			'wp_action',
		] );
		remove_action( 'wp_criticalcss_before_print_styles', [
			$this,
			'purge_cache',
		] );
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 * @param $cache
	 */
	public function maybe_disable_preloader( $cache ) {
		if ( ! empty( $cache ) ) {
			$this->disable_preloader();
		}
	}

	public function disable_preloader() {
		remove_action( 'wp_enqueue_scripts', [
			'Rocket_Async_Css_The_Preloader',
			'add_window_resize_js',
		] );
		remove_action( 'rocket_buffer', [
			'Rocket_Async_Css_The_Preloader',
			'inject_div',
		] );
	}
}
