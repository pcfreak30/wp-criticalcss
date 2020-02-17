<?php


namespace WP_CriticalCSS\Integration;


use WP_CriticalCSS\Abstracts\Integration;

/**
 * Class A3LazyLoad
 *
 * @package WP_CriticalCSS\Integration
 */
class A3LazyLoad extends Integration {

	/**
	 * @return void
	 */
	public function enable() {
		add_action( 'wp_criticalcss_nocache', [ $this, 'disable_lazyload' ] );
	}

	/**
	 * @return void
	 */
	public function disable() {
		remove_action( 'wp_criticalcss_nocache', [ $this, 'disable_lazyload' ] );
	}

	/**
	 *
	 */
	public function disable_lazyload() {
		add_filter( 'a3_lazy_load_run_filter', '__return_false' );
	}
}
