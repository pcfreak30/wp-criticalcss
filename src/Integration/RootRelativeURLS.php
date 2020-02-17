<?php

namespace WP_CriticalCSS\Integration;

use WP_CriticalCSS\Abstracts\Integration;

/**
 * Class RootRelativeURLS
 *
 * @package WP_CriticalCSS\Integration
 */
class RootRelativeURLS extends Integration {

	/**
	 * @return bool|void
	 */
	public function setup() {
		if ( class_exists( 'MP_WP_Root_Relative_URLS' ) ) {
			parent::setup();
		}
	}

	/**
	 * @return void
	 */
	public function enable() {
		remove_filter( 'post_link', [
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		], 1 );
		remove_filter( 'page_link', [
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		], 1 );
		remove_filter( 'attachment_link', [
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		], 1 );
		remove_filter( 'post_type_link', [
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		], 1 );
		remove_filter( 'get_the_author_url', [
			'MP_WP_Root_Relative_URLS',
			'dynamic_rss_absolute_url',
		], 1 );
	}

	/**
	 * @return void
	 */
	public function disable() {
		add_filter( 'post_link', [
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		], 1 );
		add_filter( 'page_link', [
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		], 1 );
		add_filter( 'attachment_link', [
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		], 1 );
		add_filter( 'post_type_link', [
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		], 1 );
		add_filter( 'get_the_author_url', [
			'MP_WP_Root_Relative_URLS',
			'dynamic_rss_absolute_url',
		], 1, 2 );
	}
}
