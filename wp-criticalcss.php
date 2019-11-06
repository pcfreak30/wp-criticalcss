<?php

/*
Plugin Name: WP Critical CSS
Plugin URI: https://github.com/pcfreak30/wp-criticalcss
Description: Use CriticalCSS.com web service to automatically create the required CSS for above the fold
Version: 0.7.7
Author: Derrick Hammer
Author URI: https://www.derrickhammer.com
License: GPL3
*/

/**
 * Activation hooks
 */

use Dice\Dice;


/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @return \WP\CriticalCSS
 * @alias WPCCSS()
 */
function wp_criticalcss() {
	return wp_criticalcss_container()->create( 'WP\CriticalCSS' );
}

function wp_criticalcss_container( $env = 'prod' ) {
	static $container;
	if ( empty( $container ) ) {
		$container = new Dice();
		include __DIR__ . "/config_{$env}.php";
	}

	return $container;
}

/**
 * Init function shortcut
 */
function wp_criticalcss_init() {
	wp_criticalcss()->init();
}

/**
 * Activate function shortcut
 */
function wp_criticalcss_activate() {
	wp_criticalcss()->init();
	wp_criticalcss()->activate();
}

/**
 * Deactivate function shortcut
 */
function wp_criticalcss_deactivate() {
	wp_criticalcss()->deactivate();
}

/**
 * Error for older php
 */
function wp_criticalcss_php_upgrade_notice() {
	$info = get_plugin_data( __FILE__ );
	_e(
		sprintf(
			'
	<div class="error notice">
		<p>Opps! %s requires a minimum PHP version of 5.4.0. Your current version is: %s. Please contact your host to upgrade.</p>
	</div>', $info['Name'], PHP_VERSION
		)
	);
}

/**
 * Error if vendors autoload is missing
 */
function wp_criticalcss_php_vendor_missing() {
	$info = get_plugin_data( __FILE__ );
	_e(
		sprintf(
			'
	<div class="error notice">
		<p>Opps! %s is corrupted it seems, please re-install the plugin.</p>
	</div>', $info['Name']
		)
	);
}

if ( version_compare( PHP_VERSION, '5.4.0' ) < 0 ) {
	add_action( 'admin_notices', 'wp_criticalcss_php_upgrade_notice' );
} else {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		include_once __DIR__ . '/vendor/autoload.php';
		add_action( 'plugins_loaded', 'wp_criticalcss_init', 11 );
		register_activation_hook( __FILE__, 'wp_criticalcss_activate' );
		register_deactivation_hook( __FILE__, 'wp_criticalcss_deactivate' );
	} else {
		add_action( 'admin_notices', 'wp_criticalcss_php_vendor_missing' );
	}
}
