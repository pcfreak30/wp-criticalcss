<?php


namespace ComposePress\Core\Traits;


use ComposePress\Core\Exception\InexistentProperty;
use ComposePress\Core\Exception\ReadOnly;

trait BaseObject {
	/**
	 * @param $name
	 *
	 * @return bool|mixed
	 */
	public function __get( $name ) {
		$func = "get_{$name}";
		if ( method_exists( $this, $func ) ) {
			return $this->$func();
		}
		$func = "is_{$name}";
		if ( method_exists( $this, $func ) ) {
			return $this->$func();
		}

		if ( isset( $GLOBALS[ $name ] ) ) {
			return $GLOBALS[ $name ];
		}

		return false;
	}

	/**
	 * @param $name
	 * @param $value
	 *
	 * @throws \ComposePress\Core\Exception\InexistentProperty
	 * @throws \ComposePress\Core\Exception\ReadOnly
	 */
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
		$func = "is_{$name}";
		if ( method_exists( $this, $func ) ) {
			throw new ReadOnly( sprintf( 'Property %s is read-only', $name ) );
		}
		if ( isset( $GLOBALS[ $name ] ) ) {
			$GLOBALS[ $name ] = $value;

			return;
		}
		throw new InexistentProperty( sprintf( 'Inexistent property: %s', $name ) );
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function __isset( $name ) {
		$func = "get_{$name}";
		if ( method_exists( $this, $func ) ) {
			return true;
		}
		$func = "is_{$name}";
		if ( method_exists( $this, $func ) ) {
			return true;
		}

		return isset( $GLOBALS[ $name ] );
	}
}