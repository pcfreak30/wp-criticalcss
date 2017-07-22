<?php

namespace WP\CriticalCSS\Cache;

use pcfreak30\WordPress\Cache\Store;
use WP\CriticalCSS;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Cache
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Manager extends CriticalCSS\ComponentAbstract {

	/**
	 * @var \pcfreak30\WordPress\Cache\Store
	 */
	private $store;

	/**
	 *
	 */
	public function init() {
		parent::init();
		add_action(
			'after_switch_theme', [
				$this,
				'reset_web_check_transients',
			]
		);
		add_action(
			'upgrader_process_complete', [
				$this,
				'reset_web_check_transients',
			]
		);
		if ( ! ( ! empty( $this->settings['template_cache'] ) && 'on' == $this->settings['template_cache'] ) ) {
			add_action(
				'post_updated', [
					$this,
					'reset_web_check_post_transient',
				]
			);
			add_action(
				'edited_term', [
					$this,
					'reset_web_check_term_transient',
				]
			);
		}
		$this->store = new Store( CriticalCSS::TRANSIENT_PREFIX, apply_filters( 'wp_criticalcss_cache_expire_period', absint( $this->settings['web_check_interval'] ) ), apply_filters( 'rocket_footer_js_max_branch_length', 50 ) );
	}

	public function delete_cache_branch( $path = [] ) {
		return $this->store->delete_cache_branch( $path );
	}

	public function delete_cache_leaf( $path = [] ) {
		return $this->store->delete_cache_leaf( $path );
	}

	/**
	 * @param $path
	 *
	 * @return mixed
	 */
	public function get_cache_fragment( $path ) {
		return $this->store->get_cache_fragment( $path );
	}

	/**
	 * @param $path
	 * @param $value
	 */
	public function update_cache_fragment( $path, $value ) {
		return $this->store->update_cache_fragment( $path, $value );
	}

	/**
	 * @param $type
	 * @param $object_id
	 * @param $url
	 */
	public function purge_page_cache( $type = null, $object_id = null, $url = null ) {
		$url = preg_replace( '#nocache/$#', '', $url );

		do_action( 'wp_criticalcss_purge_cache', $type, $object_id, $url );
	}

	/**
	 *
	 */
	public function reset_web_check_transients() {
		$this->store->delete_cache_branch();
	}

	/**
	 * @param array $path
	 */

	/**
	 * @param $post
	 */
	public function reset_web_check_post_transient( $post ) {
		$post = get_post( $post );
		$hash = $this->app->get_data_manager()->get_item_hash(
			[
				'object_id' => $post->ID,
				'type'      => 'post',
			]
		);
		$this->store->delete_cache_branch( [ $hash ] );
	}

	/**
	 * @param $term
	 *
	 * @internal param \WP_Term $post
	 */
	public function reset_web_check_term_transient( $term ) {
		$term = get_term( $term );
		$hash = $this->app->get_data_manager()->get_item_hash(
			[
				'object_id' => $term->term_id,
				'type'      => 'term',
			]
		);
		$this->store->delete_cache_branch( [ $hash ] );
	}

	/**
	 *
	 */

	/**
	 * @internal param \WP_Term $post
	 */
	public function reset_web_check_home_transient() {
		$page_for_posts = get_option( 'page_for_posts' );
		if ( ! empty( $page_for_posts ) ) {
			$post_id = $page_for_posts;
		}
		if ( empty( $post_id ) || ( ! empty( $post_id ) && get_permalink( $post_id ) != site_url() ) ) {
			$page_on_front = get_option( 'page_on_front' );
			if ( ! empty( $page_on_front ) ) {
				$post_id = $page_on_front;
			} else {
				$post_id = false;
			}
		}
		if ( ! empty( $post_id ) && get_permalink( $post_id ) == site_url() ) {
			$hash = $this->app->get_data_manager()->get_item_hash(
				[
					'object_id' => $post_id,
					'type'      => 'post',
				]
			);
		} else {
			$hash = $this->app->get_data_manager()->get_item_hash(
				[
					'type' => 'url',
					'url'  => site_url(),
				]
			);
		}
		$this->store->delete_cache_branch( [ $hash ] );
	}

	/**
	 * @return \pcfreak30\WordPress\Cache\Store
	 */
	public function get_store() {
		return $this->store;
	}
}
