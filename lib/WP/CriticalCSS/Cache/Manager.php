<?php

namespace WP\CriticalCSS\Cache;

use WP\CriticalCSS;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Cache
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Manager extends CriticalCSS\ComponentAbstract {
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
	}

	public function delete_cache_branch( $path = [] ) {
		$result = false;
		if ( is_array( $path ) ) {
			if ( ! empty( $path ) ) {
				$path = $this->set_path_defaults( $path );
				$path = CriticalCSS::TRANSIENT_PREFIX . implode( '_', $path ) . '_';
			} else {
				$path = CriticalCSS::TRANSIENT_PREFIX;
			}
		}
		$counter_transient = "{$path}cache_count";
		$counter           = $this->get_transient( $counter_transient );

		if ( is_null( $counter ) || false === $counter ) {
			return $this->delete_cache_leaf( rtrim( $path, '_' ) );
		}
		for ( $i = 1; $i <= $counter; $i ++ ) {
			$transient_name = "{$path}cache_{$i}";
			$cache          = $this->get_transient( "{$path}cache_{$i}" );
			if ( ! empty( $cache ) ) {
				$branch_result = false;
				foreach ( $cache as $sub_branch ) {
					$branch_result = $this->delete_cache_branch( "{$sub_branch}_" );
				}
				$result = $branch_result && $this->delete_cache_leaf( $transient_name );
			}
		}
		$this->delete_transient( $counter_transient );

		return $result;
	}

	protected function set_path_defaults( $path ) {
		$defaults = [ 'cache' ];
		if ( is_multisite() ) {
			$defaults[] = 'blog-' . get_current_blog_id();
		}
		$path = array_merge( $defaults, $path );
		$path = array_unique( $path );

		return $path;
	}

	/**
	 * @return mixed
	 */
	protected function get_transient() {
		if ( is_multisite() ) {
			return call_user_func_array( 'get_site_transient', func_get_args() );
		}

		return call_user_func_array( 'get_transient', func_get_args() );

	}

	/**
	 * @return mixed
	 */
	protected function delete_transient() {
		if ( is_multisite() ) {
			return call_user_func_array( 'delete_site_transient', func_get_args() );
		}

		return call_user_func_array( 'delete_transient', func_get_args() );

	}

	public function delete_cache_leaf( $path = [] ) {
		if ( is_array( $path ) ) {
			if ( ! empty( $path ) ) {
				$path = $this->set_path_defaults( $path );
				$path = CriticalCSS::TRANSIENT_PREFIX . implode( '_', $path );

				return $this->delete_transient( $path );
			}

			return false;
		}

		return $this->delete_transient( $path );
	}

	/**
	 * @param $path
	 *
	 * @return mixed
	 */
	public function get_cache_fragment( $path ) {
		$path = $this->set_path_defaults( $path );

		return $this->get_transient( CriticalCSS::TRANSIENT_PREFIX . implode( '_', $path ) );
	}

	/**
	 * @param $path
	 * @param $value
	 */
	public function update_cache_fragment( $path, $value ) {
		$path = $this->set_path_defaults( $path );
		$this->build_cache_tree( array_slice( $path, 0, - 1 ) );

		return $this->update_tree_leaf( $path, $value );
	}

	/**
	 * @param $path
	 */
	protected function build_cache_tree( $path ) {
		$levels = count( $path );
		$expire = $this->get_expire_period();
		for ( $i = 0; $i < $levels; $i ++ ) {
			$transient_id       = CriticalCSS::TRANSIENT_PREFIX . implode( '_', array_slice( $path, 0, $i + 1 ) );
			$transient_cache_id = $transient_id;
			if ( 'cache' != $path[ $i ] ) {
				$transient_cache_id .= '_cache';
			}
			$transient_cache_id .= '_1';
			$cache              = $this->get_transient( $transient_cache_id );
			$transient_value    = [];
			if ( $i + 1 < $levels ) {
				$transient_value[] = CriticalCSS::TRANSIENT_PREFIX . implode( '_', array_slice( $path, 0, $i + 2 ) );
			}
			if ( ! is_null( $cache ) && false !== $cache ) {
				$transient_value = array_unique( array_merge( $cache, $transient_value ) );
			}
			$this->set_transient( $transient_cache_id, $transient_value, $expire );
			$transient_counter_id = $transient_id;
			if ( 'cache' != $path[ $i ] ) {
				$transient_counter_id .= '_cache';
			}
			$transient_counter_id .= '_count';
			$transient_counter    = $this->get_transient( $transient_counter_id );
			if ( is_null( $transient_counter ) || false === $transient_counter ) {
				$this->set_transient( $transient_counter_id, 1, $expire );
			}
		}
	}

	/**
	 * @return int
	 */
	public function get_expire_period() {
		return apply_filters( 'wp_criticalcss_cache_expire_period', absint( $this->settings['web_check_interval'] ) );
	}

	/**
	 *
	 */
	protected function set_transient() {
		if ( is_multisite() ) {
			return call_user_func_array( 'set_site_transient', func_get_args() );
		}

		return call_user_func_array( 'set_transient', func_get_args() );

	}

	/**
	 * @param $path
	 * @param $value
	 */
	protected function update_tree_leaf( $path, $value ) {

		$leaf              = CriticalCSS::TRANSIENT_PREFIX . implode( '_', $path );
		$parent_path       = array_slice( $path, 0, is_multisite() ? - 2 : - 1 );
		$parent            = CriticalCSS::TRANSIENT_PREFIX . implode( '_', $parent_path );
		$counter_transient = $parent;
		$cache_transient   = $parent;
		if ( 'cache' != end( $parent_path ) ) {
			$counter_transient .= '_cache';
			$cache_transient   .= '_cache';
		}
		$counter_transient .= '_count';
		$counter           = (int) $this->get_transient( $counter_transient );
		$cache_transient   .= "_{$counter}";
		$cache             = $this->get_transient( $cache_transient );
		$count             = count( $cache );
		$cache_keys        = array_flip( $cache );
		$expire            = $this->get_expire_period();
		if ( ! isset( $cache_keys[ $leaf ] ) ) {
			if ( $count >= apply_filters( 'rocket_async_css_max_branch_length', 50 ) ) {
				$counter ++;
				$this->set_transient( $counter_transient, $counter, $expire );
				$cache_transient = $parent;
				if ( 'cache' != end( $parent_path ) ) {
					$cache_transient .= '_cache';
				}
				$cache_transient .= "_{$counter}";
				$cache           = [];
			}
			$cache[] = $leaf;
			$this->set_transient( $cache_transient, $cache, $expire );
		}

		return $this->set_transient( $leaf, $value, $expire );
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
		$this->delete_cache_branch();
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
		$this->delete_cache_branch( [ $hash ] );
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
		$this->delete_cache_branch( [ $hash ] );
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
		$this->delete_cache_branch( [ $hash ] );
	}
}
