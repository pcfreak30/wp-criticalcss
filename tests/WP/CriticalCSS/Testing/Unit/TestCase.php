<?php

namespace WP\CriticalCSS\Testing\Unit;

class TestCase extends \PHPUnit_Framework_TestCase {
	protected $home_url;

	protected function setUp() {
		parent::setUp();
		\WP_Mock::userFunction( 'wp_next_scheduled', [ 'return' => true ] );
		\WP_Mock::userFunction( 'sanitize_key' );
		\WP_Mock::userFunction( 'convert_to_screen', [ 'return' => (object) [ 'id' => 'test' ] ] );
		\WP_Mock::userFunction( 'wp_slash', [
			'return' => function ( $value ) {
				return $value;
			},
		] );
		\WP_Mock::userFunction( 'wp_parse_args', [
			'return' => function ( $args, $defaults = '' ) {
				if ( is_object( $args ) ) {
					$r = get_object_vars( $args );
				} elseif ( is_array( $args ) ) {
					$r =& $args;
				} else {
					wp_parse_str( $args, $r );
				}

				if ( is_array( $defaults ) ) {
					return array_merge( $defaults, $r );
				}

				return $r;
			},
		] );
		\WP_Mock::userFunction( 'absint', [
			'return' => function ( $maybeint ) {
				return abs( intval( $maybeint ) );
			},
		] );
		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function ( $string ) {
				return untrailingslashit( $string ) . '/';
			},
		] );
		\WP_Mock::userFunction( 'untrailingslashit', [
			'return' => function ( $string ) {
				return rtrim( $string, '/\\' );
			},
		] );
	}

	protected function tearDown() {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

}