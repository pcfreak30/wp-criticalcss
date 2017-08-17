<?php

namespace WP\CriticalCSS\Integration;
/**
 * Class IntegrationAbstract
 */

use pcfreak30\WordPress\Plugin\Framework\ComponentAbstract;

/**
 * Class IntegrationAbstract
 *
 * @package WP\CriticalCSS\Integration
 * @property \WP\CriticalCSS $plugin
 */
abstract class IntegrationAbstract extends ComponentAbstract {

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
