<?php


namespace WP\CriticalCSS;


/**
 * Class Component
 *
 * @package WP\CriticalCSS
 */
abstract class ComponentAbstract {

	/**
	 * @var \WP\CriticalCSS
	 */
	protected $app;

	protected $settings = [];

	/**
	 *
	 */
	public function init() {
		$this->settings = $this->app->get_settings();
	}

	/**
	 *
	 */
	public function __destruct() {
		$this->app = null;
	}

	/**
	 * @param \WP\CriticalCSS $app
	 */
	public function set_app( $app ) {
		$this->app = $app;
	}
}
