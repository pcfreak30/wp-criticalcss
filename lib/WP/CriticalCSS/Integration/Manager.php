<?php


namespace WP\CriticalCSS\Integration;


use pcfreak30\WordPress\Plugin\Framework\ManagerAbstract;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Integration
 */
class Manager extends ManagerAbstract {
	/**
	 * @var bool
	 */
	protected $enabled = false;
	/**
	 * @var array
	 */
	protected $modules = [
		'RocketAsyncCSS',
		'RootRelativeURLS',
		'WPRocket',
		'WPEngine',
		'A3LazyLoad',
	];

	/**
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 *
	 */
	public function init() {
		parent::init();
		$this->enable_integrations();
	}

	/**
	 *
	 */
	public function enable_integrations() {
		do_action( 'wp_criticalcss_enable_integrations' );
		$this->enabled = true;

	}

	/**
	 *
	 */
	public function disable_integrations() {
		do_action( 'wp_criticalcss_disable_integrations' );
		$this->enabled = false;
	}
}
