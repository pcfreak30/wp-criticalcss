<?php

namespace pcfreak30\WordPress\Plugin\Framework;

use pcfreak30\WordPress\Plugin\Framework\Exception\InexistentProperty;
use pcfreak30\WordPress\Plugin\Framework\Exception\ReadOnly;

/**
 * Class BaseObjectAbstract
 *
 * @package pcfreak30\WordPress\Plugin\Framework
 * @property \wpdb       $wpdb
 * @property \WP_Post    $post
 * @property \WP_Rewrite $wp_rewrite
 * @property \WP         $wp
 * @property \WP_Query   $wp_query
 * @property \WP_Query   $wp_the_query
 * @property string      $pagenow
 * @property int         $page
 */
abstract class BaseObjectAbstract implements ComponentInterface {
	public function __get( $name ) {
		$func = "get_{$name}";
		if ( method_exists( $this, $func ) ) {
			return $this->$func();
		}

		if ( isset( $GLOBALS[ $name ] ) ) {
			return $GLOBALS[ $name ];
		}

		return false;
	}

	public function __set( $name, $value ) {
		$func = "set_{$name}";
		if ( method_exists( $this, $func ) ) {
			$this->$func( $value );

			return;
		}
		$func = "get_{$name}";
		if ( method_exists( $this, $func ) ) {
			throw new ReadOnly( sprintf( 'Property %s is read-only', $name ) );
		}
		if ( isset( $GLOBALS[ $name ] ) ) {
			$GLOBALS[ $name ] = $value;

			return;
		}
		throw new InexistentProperty( sprintf( 'Inexistent property: %s', $name ) );
	}

	public function __isset( $name ) {
		$func = "get_{$name}";
		if ( method_exists( $this, $func ) ) {
			return true;
		}

		return isset( $GLOBALS[ $name ] );
	}
}