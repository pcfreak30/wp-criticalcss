<?php

namespace pcfreak30\WordPress\Cache;

/**
 * Class Store
 *
 * @package pcfreak30\WordPress\Cache
 */
class Store {
	private $prefix = '';
	private $expire = 3600;
	private $max_branch_length = 50;

	/**
	 * @param array $path
	 *
	 * @return bool|mixed
	 */
	public function delete_cache_branch( $path = [] ) {
		$result = false;
		if ( is_array( $path ) ) {
			if ( ! empty( $path ) ) {
				$path = $this->set_path_defaults( $path );
				$path = $this->prefix . implode( '_', $path ) . '_';
			} else {
				$path = $this->prefix;
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

	/**
	 * @return string
	 */
	public function get_prefix() {
		return $this->prefix;
	}

	/**
	 * @param string $prefix
	 */
	public function set_prefix( $prefix ) {
		$this->prefix = $prefix;
	}

	/**
	 * @return int
	 */
	public function get_expire() {
		return $this->expire;
	}

	/**
	 * @param int $expire
	 */
	public function set_expire( $expire ) {
		$this->expire = $expire;
	}

	/**
	 * @return int
	 */
	public function get_max_branch_length() {
		return $this->max_branch_length;
	}

	/**
	 * @param int $max_branch_length
	 */
	public function set_max_branch_length( $max_branch_length ) {
		$this->max_branch_length = $max_branch_length;
	}

	/**
	 * @param $path
	 *
	 * @return array
	 */
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
	public function get_transient() {
		if ( is_multisite() ) {
			return call_user_func_array( 'get_site_transient', func_get_args() );
		}

		return call_user_func_array( 'get_transient', func_get_args() );

	}

	/**
	 * @param array $path
	 *
	 * @return bool
	 */
	public function delete_cache_leaf( $path = [] ) {
		if ( is_array( $path ) ) {
			if ( ! empty( $path ) ) {
				$path = $this->set_path_defaults( $path );
				$path = $this->prefix . implode( '_', $path );

				return $this->delete_transient( $path );
			}

			return false;
		}

		return $this->delete_transient( $path );
	}

	/**
	 * @return bool
	 */
	public function delete_transient() {
		if ( is_multisite() ) {
			return call_user_func_array( 'delete_site_transient', func_get_args() );
		}

		return call_user_func_array( 'delete_transient', func_get_args() );

	}

	/**
	 * @param $path
	 *
	 * @return mixed
	 */
	public function get_cache_fragment( $path ) {
		$path = $this->set_path_defaults( $path );

		return $this->get_transient( $this->prefix . implode( '_', $path ) );
	}

	/**
	 * @param $path
	 * @param $value
	 *
	 * @return bool
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
		$expire = $this->expire;
		for ( $i = 0; $i < $levels; $i ++ ) {
			$transient_id       = $this->prefix . implode( '_', array_slice( $path, 0, $i + 1 ) );
			$transient_cache_id = $transient_id;
			if ( 'cache' !== $path[ $i ] ) {
				$transient_cache_id .= '_cache';
			}
			$transient_cache_id .= '_1';
			$cache              = $this->get_transient( $transient_cache_id );
			$transient_value    = [];
			if ( $i + 1 < $levels ) {
				$transient_value[] = $this->prefix . implode( '_', array_slice( $path, 0, $i + 2 ) );
			}
			if ( ! is_null( $cache ) && false !== $cache ) {
				$transient_value = array_unique( array_merge( $cache, $transient_value ) );
			}
			$this->set_transient( $transient_cache_id, $transient_value, $expire );
			$transient_counter_id = $transient_id;
			if ( 'cache' !== $path[ $i ] ) {
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
	 *
	 */
	public function set_transient() {
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
		$leaf              = $this->prefix . implode( '_', $path );
		$parent_path       = array_slice( $path, 0, is_multisite() ? - 2 : - 1 );
		$parent            = $this->prefix . implode( '_', $parent_path );
		$counter_transient = $parent;
		$cache_transient   = $parent;
		if ( 'cache' !== end( $parent_path ) ) {
			$counter_transient .= '_cache';
			$cache_transient   .= '_cache';
		}
		$counter_transient .= '_count';
		$counter           = (int) $this->get_transient( $counter_transient );
		$cache_transient   .= "_{$counter}";
		$cache             = $this->get_transient( $cache_transient );
		$count             = count( $cache );
		$cache_keys        = array_flip( $cache );
		$expire            = $this->expire;
		if ( ! isset( $cache_keys[ $leaf ] ) ) {
			if ( $count >= $this->max_branch_length ) {
				$counter ++;
				$this->set_transient( $counter_transient, $counter, $expire );
				$cache_transient = $parent;
				if ( 'cache' !== end( $parent_path ) ) {
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
}
