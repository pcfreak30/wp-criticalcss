<?php


namespace WP\CriticalCSS\Integration;


use WP\CriticalCSS\ComponentAbstract;

class Manager extends ComponentAbstract {
	/**
	 * @var bool
	 */
	protected $enabled = false;
	protected $integrations = [
		'\\WP\\CriticalCSS\\Integration\\RocketAsyncCSS',
		'\\WP\\CriticalCSS\\Integration\\RootRelativeURLS',
		'\\WP\\CriticalCSS\\Integration\\WPRocket',
		'\\WP\\CriticalCSS\\Integration\\WPEngine',
	];

	/**
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	public function init() {
		$integrations = [];
		foreach ( $this->integrations as $integration ) {
			$integrations[ $integration ] = wpccss_container()->create( $integration );
		}
		$this->integrations = $integrations;
		$this->enable_integrations();
	}

	public function enable_integrations() {
		do_action( 'wp_criticalcss_enable_integrations' );
		$this->enabled = true;

	}

	/**
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}

	/**
	 *
	 */
	public function disable_integrations() {

		do_action( 'wp_criticalcss_disable_integrations' );
		$this->enabled = false;

	}
}
