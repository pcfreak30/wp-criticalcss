<?php

namespace WP\CriticalCSS\Integration;
/**
 * Class IntegrationAbstract
 */
abstract class IntegrationAbstract {
	/**
	 * WP_CriticalCSS_Integration_Base constructor.
	 */
	public function __construct() {
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
