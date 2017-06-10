<?php


namespace WP\CriticalCSS\Integration;


use WP\CriticalCSS\ComponentAbstract;

class Manager extends ComponentAbstract {
	protected $integrations = array(
		'\\WP\\CriticalCSS\\Integration\\RocketAsyncCSS',
		'\\WP\\CriticalCSS\\Integration\\RootRelativeURLS',
		'\\WP\\CriticalCSS\\Integration\\WPRocket',
		'\\WP\\CriticalCSS\\Integration\\WPEngine',
	);

	public function init() {
		$integrations = array();
		foreach ( $this->integrations as $integration ) {
			$integrations[ $integration ] = wpccss_container()->create( $integration );
		}
		$this->integrations = $integrations;
	}

	/**
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}

	public function enable_integrations() {
		do_action( 'wp_criticalcss_enable_integrations' );
	}

	/**
	 *
	 */
	public function disable_integrations() {
		do_action( 'wp_criticalcss_disable_integrations' );
	}
}
