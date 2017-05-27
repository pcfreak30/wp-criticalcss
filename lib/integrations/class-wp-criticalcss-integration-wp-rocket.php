<?php


class WP_CriticalCSS_Integration_WP_Rocket extends WP_CriticalCSS_Integration_Base {

	public function __construct() {
		if ( class_exists( 'WPECommon' ) ) {
			parent::__construct();
		}
	}

	/**
	 * @return void
	 */
	public function enable() {
		add_action( 'after_rocket_clean_domain', array( WPCCSS(), 'reset_web_check_transients' ) );
		add_action( 'after_rocket_clean_post', array( WPCCSS(), 'reset_web_check_post_transient' ) );
		add_action( 'after_rocket_clean_term', array( WPCCSS(), 'reset_web_check_term_transient' ) );
		add_action( 'after_rocket_clean_home', array( WPCCSS(), 'reset_web_check_home_transient' ) );
		if ( ! has_action( 'after_rocket_clean_domain', 'rocket_clean_wpengine' ) ) {
			add_action( 'after_rocket_clean_domain', 'rocket_clean_wpengine' );
		}
		if ( ! has_action( 'after_rocket_clean_domain', 'rocket_clean_supercacher' ) ) {
			add_action( 'after_rocket_clean_domain', 'rocket_clean_supercacher' );
		}
		add_action( 'wp_criticalcss_purge_cache', array( $this, '_purge_cache' ) );
		add_filter( 'wp_criticalcss_print_styles_cache', array( $this, '_print_styles' ) );
		add_filter( 'wp_criticalcss_cache_integration', '__return_true' );
	}

	/**
	 * @return void
	 */
	public function disable() {
		remove_action( 'after_rocket_clean_domain', array( WPCCSS(), 'reset_web_check_transients' ) );
		remove_action( 'after_rocket_clean_post', array( WPCCSS(), 'reset_web_check_post_transient' ) );
		remove_action( 'after_rocket_clean_term', array( WPCCSS(), 'reset_web_check_term_transient' ) );
		remove_action( 'after_rocket_clean_home', array( WPCCSS(), 'reset_web_check_home_transient' ) );
		remove_action( 'after_rocket_clean_domain', 'rocket_clean_wpengine' );
		remove_action( 'after_rocket_clean_domain', 'rocket_clean_supercacher' );
		remove_filter( 'wp_criticalcss_print_styles_cache', array( $this, '_purge_cache' ) );
		remove_filter( 'wp_criticalcss_cache_integration', '__return_true' );
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 * @param null $type
	 * @param null $object_id
	 * @param null $url
	 */
	private function _purge_cache( $type = null, $object_id = null, $url = null ) {
		if ( 'post' == $type ) {
			rocket_clean_post( $object_id );
		}
		if ( 'term' == $type ) {
			rocket_clean_term( $object_id, get_term( $object_id )->taxonomy );
		}
		if ( 'url' == $type ) {
			rocket_clean_files( $url );
		}
		if ( empty( $type ) ) {
			rocket_clean_domain();
		}
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 * @param $cache
	 *
	 * @return mixed
	 */
	private function _print_styles( $cache ) {
		$cache = rocket_cdn_css_properties( $cache );

		return $cache;
	}
}