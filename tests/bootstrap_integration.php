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

global $wp_tests_options;
$_test_plugin = getenv( 'TEST_PLUGIN' );
if ( $_test_plugin ) {
	$wp_tests_options = array( 'active_plugins' => array_map( 'trim', array_filter( explode( ',', $_test_plugin ) ) ) );
}
// Initialize composer
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';


/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/wp-criticalcss.php';
	remove_action( 'plugins_loaded', 'wp_criticalcss_init' );
	function wp_criticalcss_init_integration() {
		wpccss_container( 'integration_test' );
	}

	add_action( 'plugins_loaded', 'wp_criticalcss_init_integration' );

}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );;
// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';