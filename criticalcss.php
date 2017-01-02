<?php

/*
Plugin Name: Critical CSS
Plugin URI: https://github.com/pcfreak30/wp-criticalcss
Description: Use CriticalCSS.com web service to automatically create the required CSS for above the fold
Version: 0.1.1
Author: Derrick Hammer
Author URI: https://www.derrickhammer.com
License: GPL3
*/

/**
 * Activation hooks
 */
register_activation_hook( __FILE__, array( 'CriticalCSS', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CriticalCSS', 'deactivate' ) );

/**
 * Autoloader function
 *
 * Will search both plugin root and lib folder for class
 *
 * @param $class_name
 */
if ( ! function_exists( 'criticalcss_autoloader' ) ):
	function criticalcss_autoloader( $class_name ) {
		$file      = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
		$base_path = plugin_dir_path( __FILE__ );

		$paths = array(
			$base_path . $file,
			$base_path . 'lib/' . $file,
		);
		foreach ( $paths as $path ) {

			if ( is_readable( $path ) ) {
				include_once( $path );

				return;
			}
		}
	}

	spl_autoload_register( 'criticalcss_autoloader' );
endif;


add_action( 'plugins_loaded', array( 'CriticalCSS', 'init' ) );