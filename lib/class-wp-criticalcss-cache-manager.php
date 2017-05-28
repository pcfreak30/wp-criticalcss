<?php


class WP_CriticalCSS_Cache_Manager {
	protected $settings;

	public function __construct() {
		$this->settings = WPCCSS()->get_settings();
	}

	public function delete_cache_branch( $path = array() ) {
		if ( is_array( $path ) ) {
			if ( ! empty( $path ) ) {
				$path = WP_CriticalCSS::TRANSIENT_PREFIX . implode( '_', $path ) . '_';
			} else {
				$path = WP_CriticalCSS::TRANSIENT_PREFIX;
			}
		}
		$counter_transient = "{$path}cache_count";
		$counter           = get_transient( $counter_transient );

		if ( is_null( $counter ) || false === $counter ) {
			$this->delete_transient( rtrim( $path, '_' ) );

			return;
		}
		for ( $i = 1; $i <= $counter; $i ++ ) {
			$transient_name = "{$path}cache_{$i}";
			$cache          = get_transient( "{$path}cache_{$i}" );
			if ( ! empty( $cache ) ) {
				foreach ( $cache as $sub_branch ) {
					$this->delete_cache_branch( "{$sub_branch}_" );
				}
				$this->delete_transient( $transient_name );
			}
		}
		$this->delete_transient( $counter_transient );
	}

	/**
	 * @return mixed
	 */
	protected function delete_transient() {
		if ( is_multisite() ) {
			return call_user_func_array( 'delete_site_transient', func_get_args() );
		} else {
			return call_user_func_array( 'delete_transient', func_get_args() );
		}
	}

	/**
	 * @param $path
	 *
	 * @return mixed
	 */
	public function get_cache_fragment( $path ) {
		if ( ! in_array( 'cache', $path ) ) {
			array_unshift( $path, 'cache' );
		}

		return $this->get_transient( WP_CriticalCSS::TRANSIENT_PREFIX . implode( '_', $path ) );
	}

	/**
	 * @return mixed
	 */
	protected function get_transient() {
		if ( is_multisite() ) {
			return call_user_func_array( 'get_site_transient', func_get_args() );
		} else {
			return call_user_func_array( 'get_transient', func_get_args() );
		}
	}

	/**
	 * @param $path
	 * @param $value
	 */
	public function update_cache_fragment( $path, $value ) {
		if ( ! in_array( 'cache', $path ) ) {
			array_unshift( $path, 'cache' );
		}
		$this->build_cache_tree( array_slice( $path, 0, count( $path ) - 1 ) );
		$this->update_tree_branch( $path, $value );
	}

	/**
	 * @param $path
	 */
	protected function build_cache_tree( $path ) {
		$levels = count( $path );
		$expire = $this->get_expire_period();
		for ( $i = 0; $i < $levels; $i ++ ) {
			$transient_id       = WP_CriticalCSS::TRANSIENT_PREFIX . implode( '_', array_slice( $path, 0, $i + 1 ) );
			$transient_cache_id = $transient_id;
			if ( 'cache' != $path[ $i ] ) {
				$transient_cache_id .= '_cache';
			}
			$transient_cache_id .= '_1';
			$cache              = $this->get_transient( $transient_cache_id );
			$transient_value    = array();
			if ( $i + 1 < $levels ) {
				$transient_value[] = WP_CriticalCSS::TRANSIENT_PREFIX . implode( '_', array_slice( $path, 0, $i + 2 ) );
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
			call_user_func_array( 'set_site_transient', func_get_args() );
		} else {
			call_user_func_array( 'set_transient', func_get_args() );
		}
	}

	/**
	 * @param $path
	 * @param $value
	 */
	protected function update_tree_branch( $path, $value ) {
		$branch            = WP_CriticalCSS::TRANSIENT_PREFIX . implode( '_', $path );
		$parent_path       = array_slice( $path, 0, count( $path ) - 1 );
		$parent            = WP_CriticalCSS::TRANSIENT_PREFIX . implode( '_', $parent_path );
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
		if ( ! isset( $cache_keys[ $branch ] ) ) {
			if ( $count >= apply_filters( 'rocket_async_css_max_branch_length', 50 ) ) {
				$counter ++;
				$this->set_transient( $counter_transient, $counter, $expire );
				$cache_transient = $parent;
				if ( 'cache' != end( $parent_path ) ) {
					$cache_transient .= '_cache';
				}
				$cache_transient .= "_{$counter}";
				$cache           = array();
			}
			$cache[] = $branch;
			$this->set_transient( $cache_transient, $cache, $expire );
		}
		$this->set_transient( $branch, $value, $expire );
	}
}