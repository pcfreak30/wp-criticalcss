<?php

namespace WP_CriticalCSS\Abstracts;

use WP_CriticalCSS\Core\Component;

/**
 * Class Integration
 */


/**
 * Class IntegrationAbstract
 *
 * @package WP_CriticalCSS\Abstracts
 * @property \WP_CriticalCSS\Plugin $plugin
 */
abstract class Integration extends Component {

	/**
	 *
	 */
	public function setup() {
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
