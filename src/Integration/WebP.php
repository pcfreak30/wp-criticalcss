<?php


namespace WP_CriticalCSS\Integration;


use WP_CriticalCSS\Abstracts\Integration;

/**
 * Class WebP
 *
 * @package WP_CriticalCSS\Integration
 */
class WebP extends Integration {

	/**
	 * @return void
	 */
	public function enable() {
		add_action( 'wp_criticalcss_nocache', [ $this, 'force_webp_off' ] );
	}

	/**
	 * @return void
	 */
	public function disable() {
		remove_action( 'wp_criticalcss_nocache', [ $this, 'force_webp_off' ] );
	}

	/**
	 *
	 */
	public function force_webp_off() {
		$_SERVER['HTTP_ACCEPT'] = preg_replace( '~image/webp,?~', '', $_SERVER['HTTP_ACCEPT'] );
	}
}
