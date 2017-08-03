<?php


namespace WP\CriticalCSS\Integration;


use WP\CriticalCSS\ComponentAbstract;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Integration
 */
class Manager extends ComponentAbstract {
	/**
	 * @var bool
	 */
	protected $enabled = false;
	/**
	 * @var array
	 */
	protected $integrations = [
		'RocketAsyncCSS',
		'RootRelativeURLS',
		'WPRocket',
		'WPEngine',
	];
	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * Manager constructor.
	 */
	public function __construct() {
		$this->namespace = ( new \ReflectionClass( get_called_class() ) )->getNamespaceName();
	}

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
		$reflect   = new \ReflectionClass( $this );
		$class     = strtolower( $reflect->getShortName() );
		$namespace = $reflect->getNamespaceName();
		$namespace = str_replace( '\\', '/', $namespace );
		$component = strtolower( basename( $namespace ) );
		$filter    = "rocket_async_css_{$component}_{$class}_modules";

		$integrations_list = apply_filters( $filter, $this->integrations );

		foreach ( $integrations_list as $module ) {
			$modules[ $module ] = wpccss_container()->create( $this->namespace . '\\' . $module );
		}
		foreach ( $integrations_list as $module ) {
			$modules[ $module ]->init();
		}
		$this->integrations = $integrations_list;
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
