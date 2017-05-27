<?php


class WP_CriticalCSS_Integration_Root_Relative_URLS extends WP_CriticalCSS_Integration_Base {

	public function __construct() {
		if ( class_exists( 'MP_WP_Root_Relative_URLS' ) ) {
			parent::__construct();
		}
	}

	/**
	 * @return void
	 */
	public function enable() {
		remove_filter( 'post_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
		remove_filter( 'page_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
		remove_filter( 'attachment_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
		remove_filter( 'post_type_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
		remove_filter( 'get_the_author_url', array( 'MP_WP_Root_Relative_URLS', 'dynamic_rss_absolute_url' ), 1 );
	}

	/**
	 * @return void
	 */
	public function disable() {
		add_filter( 'post_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
		add_filter( 'page_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
		add_filter( 'attachment_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
		add_filter( 'post_type_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
		add_filter( 'get_the_author_url', array( 'MP_WP_Root_Relative_URLS', 'dynamic_rss_absolute_url' ), 1, 2 );
	}
}