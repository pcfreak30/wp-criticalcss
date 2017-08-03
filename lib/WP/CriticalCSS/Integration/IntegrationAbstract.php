<?php

namespace WP\CriticalCSS\Integration;
/**
 * Class IntegrationAbstract
 */
/**
 * Class IntegrationAbstract
 *
 * @package WP\CriticalCSS\Integration
 */
abstract class IntegrationAbstract {

	/**
	 *
	 */
	public function init() {
		add_action( 'wp_criticalcss_enable_integrations', [
			$this,
			'enable',
		] );
		add_action( 'wp_criticalcss_disable_integrations', [
			$this,
			'disable',
		] );
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
