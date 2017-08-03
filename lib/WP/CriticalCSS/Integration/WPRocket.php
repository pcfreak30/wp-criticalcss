<?php

namespace WP\CriticalCSS\Integration;

class WPRocket extends IntegrationAbstract {

	public function __construct() {
		if ( function_exists( 'get_rocket_option' ) ) {
			parent::__construct();
		}
	}

	/**
	 * @return void
	 */
	public function enable() {
		add_action( 'after_rocket_clean_domain', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_transients',
		] );
		add_action( 'after_rocket_clean_post', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_post_transient',
		] );
		add_action( 'after_rocket_clean_term', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_term_transient',
		] );
		add_action( 'after_rocket_clean_home', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_home_transient',
		] );
		add_action( 'wp_criticalcss_nocache', [
			$this,
			'disable_cache',
		] );
		if ( function_exists( 'rocket_clean_wpengine' ) && ! has_action( 'after_rocket_clean_domain', 'rocket_clean_wpengine' ) ) {
			add_action( 'after_rocket_clean_domain', 'rocket_clean_wpengine' );
		}
		if ( function_exists( 'rocket_clean_wpengine' ) && ! has_action( 'after_rocket_clean_domain', 'rocket_clean_supercacher' ) ) {
			add_action( 'after_rocket_clean_domain', 'rocket_clean_supercacher' );
		}
		add_action( 'wp_criticalcss_purge_cache', [
			$this,
			'purge_cache',
		], 10, 3 );
		add_filter( 'wp_criticalcss_print_styles_cache', [
			$this,
			'print_styles',
		] );
		add_filter( 'wp_criticalcss_cache_integration', '__return_true' );
		add_filter( 'wp_criticalcss_cache_expire_period', [
			$this,
			'get_cache_expire_period',
		] );
	}

	/**
	 * @return void
	 */
	public function disable() {
		remove_action( 'after_rocket_clean_domain', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_transients',
		] );
		remove_action( 'after_rocket_clean_post', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_post_transient',
		] );
		remove_action( 'after_rocket_clean_term', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_term_transient',
		] );
		remove_action( 'after_rocket_clean_home', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_home_transient',
		] );
		remove_action( 'after_rocket_clean_domain', 'rocket_clean_wpengine' );
		remove_action( 'after_rocket_clean_domain', 'rocket_clean_supercacher' );
		remove_filter( 'wp_criticalcss_print_styles_cache', [
			$this,
			'print_styles',
		] );
		remove_filter( 'wp_criticalcss_cache_integration', '_return_true' );
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 * @param null $type
	 * @param null $object_id
	 * @param null $url
	 */
	public function purge_cache( $type = null, $object_id = null, $url = null ) {
		if ( 'post' === $type ) {
			rocket_clean_post( $object_id );
		}
		if ( 'term' === $type ) {
			rocket_clean_term( $object_id, get_term( $object_id )->taxonomy );
		}
		if ( 'url' === $type ) {
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
	public function print_styles( $cache ) {
		$cache = rocket_cdn_css_properties( $cache );

		return $cache;
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 * @return int
	 */
	public function get_cache_expire_period() {
		return get_rocket_purge_cron_interval();
	}

	public function disable_cache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
}
