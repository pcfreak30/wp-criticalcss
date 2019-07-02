<?php


namespace WP\CriticalCSS\Integration;


class WebP extends IntegrationAbstract {

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

	public function force_webp_off() {
		$_SERVER['HTTP_ACCEPT'] = preg_replace( '~image/webp,?~', '', $_SERVER['HTTP_ACCEPT'] );
	}
}
