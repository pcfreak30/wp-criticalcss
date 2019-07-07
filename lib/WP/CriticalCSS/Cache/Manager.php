<?php

namespace WP\CriticalCSS\Cache;

use ComposePress\Core\Abstracts\Component;
use pcfreak30\WordPress\Cache\Store;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Cache
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @property \WP\CriticalCSS $plugin
 */
class Manager extends Component {

	/**
	 * @var \pcfreak30\WordPress\Cache\Store
	 */
	private $store;


	/**
	 * Manager constructor.
	 *
	 * @param \pcfreak30\WordPress\Cache\Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}


	/**
	 *
	 */
	public function init() {
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
		if ( ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			add_action(
				'wp_criticalcss_purge_cache', [
					$this,
					'reset_web_check_transients',
				]
			);
		}
		if ( ! ( $this->plugin->settings_manager->get_setting( 'template_cache' ) && 'on' === $this->plugin->settings_manager->get_setting( 'template_cache' ) ) ) {
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
		$this->store->set_prefix( $this->plugin->get_transient_prefix() );
		$interval = 0;
		if ( function_exists( 'get_rocket_purge_cron_interval' ) ) {
			$interval = get_rocket_purge_cron_interval();
		}
		$this->store->set_expire( apply_filters( 'wp_criticalcss_cache_expire_period', $interval ) );
		$this->store->set_max_branch_length( apply_filters( 'wp_criticalcss_max_branch_length', 50 ) );
	}

	/**
	 * @param array $path
	 *
	 * @return bool|mixed
	 */
	public function delete_cache_branch( $path = [] ) {
		return $this->store->delete_cache_branch( $path );
	}

	/**
	 * @param array $path
	 *
	 * @return bool
	 */
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
		if ( '' !== $this->plugin->settings_manager->get_setting( 'apikey' ) ) {
			$url = preg_replace( '#nocache/$#', '', $url );
			do_action( 'wp_criticalcss_purge_cache', $type, $object_id, $url );
		}
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
		$hash = $this->plugin->data_manager->get_item_hash(
			[
				'object_id' => $post->ID,
				'type'      => 'post',
			]
		);
		$this->store->delete_cache_branch( [ 'webcheck', $hash ] );
	}

	/**
	 * @param $term
	 *
	 * @internal param \WP_Term $post
	 */
	public function reset_web_check_term_transient( $term ) {
		$term = get_term( $term );
		$hash = $this->plugin->data_manager->get_item_hash(
			[
				'object_id' => $term->term_id,
				'type'      => 'term',
			]
		);
		$this->store->delete_cache_branch( [ 'webcheck', $hash ] );
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
			$hash = $this->plugin->data_manager->get_item_hash(
				[
					'object_id' => $post_id,
					'type'      => 'post',
				]
			);
		} else {
			$hash = $this->plugin->data_manager->get_item_hash(
				[
					'type' => 'url',
					'url'  => site_url(),
				]
			);
		}
		$this->store->delete_cache_branch( [ 'webcheck', $hash ] );
	}

	/**
	 * @return \pcfreak30\WordPress\Cache\Store
	 */
	public function get_store() {
		return $this->store;
	}
}
