<?php


namespace WP\CriticalCSS\Integration;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Integration
 */
class Manager extends \ComposePress\Core\Abstracts\Manager {
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
		'Kinsta',
		'Elementor',
		'WebP',
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
