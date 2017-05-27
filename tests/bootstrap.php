<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Wp_Criticalcss
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

// Initize composer
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/wp-criticalcss.php';
	remove_action( 'plugins_loaded', 'wp_criticalcss_init' );
}

function wp_criticalcss_test_autoloader( $class_name ) {
	$file      = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
	require_once ABSPATH . '/wp-includes/formatting.php';
	$base_path = trailingslashit( __DIR__ );

	$paths = array(
		$base_path . $file,
	);
	foreach ( $paths as $path ) {

		if ( is_readable( $path ) ) {
			include_once( $path );

			return;
		}
	}
}

spl_autoload_register( 'wp_criticalcss_test_autoloader' );

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
