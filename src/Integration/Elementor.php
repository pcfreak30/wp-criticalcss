<?php


namespace WP_CriticalCSS\Integration;


use Elementor\Plugin;
use WP_CriticalCSS\Abstracts\Integration;

/**
 * Class Elementor
 *
 * @package WP_CriticalCSS\Integration
 */
class Elementor extends Integration {

	/**
	 * @return bool|void
	 */
	public function setup() {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			parent::setup();
		}
	}


	/**
	 * @return void
	 */
	public function enable() {
		add_action( 'wp', [ $this, 'check_preview' ] );
	}

	/**
	 * @return void
	 */
	public function disable() {
		remove_action( 'init', [ $this, 'check_preview' ] );
	}

	/**
	 *
	 */
	public function check_preview() {
		if ( Plugin::$instance->preview->is_preview_mode() ) {
			add_filter( 'wp_criticalcss_print_styles_cache', '__return_empty_string', 11 );
		}
	}
}
