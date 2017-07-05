<?php

/*
Plugin Name: WP Critical CSS
Plugin URI: https://github.com/pcfreak30/wp-criticalcss
Description: Use CriticalCSS.com web service to automatically create the required CSS for above the fold
Version: 0.6.3
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
function wpccss() {
	return wpccss_container()->create( 'WP\CriticalCSS' );
}

function wpccss_container( $env = 'prod' ) {
	static $container;
	if ( empty( $container ) ) {
		$container = new Dice();
		include __DIR__ . "/config_{$env}.php";
	}

	return $container;
}

/**
 *
 */
function wp_criticalcss_init() {
	WPCCSS()->init();
}

function wp_criticalcss_activate() {
	WPCCSS()->init();
	WPCCSS()->activate();
}

function wp_criticalcss_deactivate() {
	WPCCSS()->deactivate();
}

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

if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
	add_action( 'admin_notices', 'wp_criticalcss_php_upgrade_notice' );
} else {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		include_once __DIR__ . '/vendor/autoload.php';
		add_action( 'plugins_loaded', 'wp_criticalcss_init' );
		register_activation_hook( __FILE__, 'wp_criticalcss_activate' );
		register_deactivation_hook( __FILE__, 'wp_criticalcss_deactivate' );
	} else {
		include_once __DIR__ . '/wordpress-web-composer/class-wordpress-web-composer.php';
		$web_composer = new WordPress_Web_Composer( 'wp_criticalcss' );
		$web_composer->set_install_target( __DIR__ );
		if ( $web_composer->run() ) {
			include_once __DIR__ . '/vendor/autoload.php';
			register_activation_hook( __FILE__, 'wp_criticalcss_activate' );
			register_deactivation_hook( __FILE__, 'wp_criticalcss_deactivate' );
			define( 'WP_CRITICALCSS_COMPOSER_RAN', true );
		}
	}
}
