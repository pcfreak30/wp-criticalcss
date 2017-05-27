<?php


/**
 * Class WP_CriticalCSS_Integration_Base
 */
abstract class WP_CriticalCSS_Integration_Base {
	/**
	 * WP_CriticalCSS_Integration_Base constructor.
	 */
	public function __construct() {
		add_action( 'wp_criticalcss_enable_integrations', array( $this, 'enable' ) );
		add_action( 'wp_criticalcss_disable_integrations', array( $this, 'disable' ) );
	}

	/**
	 * @return void
	 */
	abstract public function enable();

	/**
	 * @return void
	 */
	abstract public function disable();
}