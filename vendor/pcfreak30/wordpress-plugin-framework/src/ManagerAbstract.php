<?php


namespace pcfreak30\WordPress\Plugin\Framework;

class ManagerAbstract extends ComponentAbstract {
	protected $modules = [

	];

	/**
	 *
	 */
	public function init() {
		if ( 0 < count( array_filter( $this->modules, 'is_object' ) ) ) {
			return;
		}
		$modules = [];

		$reflect   = new \ReflectionClass( get_called_class() );
		$class     = strtolower( $reflect->getShortName() );
		$namespace = $reflect->getNamespaceName();
		$component = strtolower( basename( $namespace ) );

		$slug         = $this->plugin->get_safe_slug();
		$filter       = "{$slug}_{$component}_{$class}_modules";
		$modules_list = apply_filters( $filter, $this->modules );

		foreach ( $modules_list as $module ) {
			$class = trim( $module, '\\' );
			if ( false === strpos( $module, '\\' ) ) {
				$class = $namespace . '\\' . $module;
			}
			$modules[ $module ] = $this->plugin->container->create( $class );
		}
		foreach ( $modules_list as $module ) {
			$modules[ $module ]->parent = $this;
			$modules[ $module ]->init();
		}

		$this->modules = $modules;
	}

	/**
	 * @return array
	 */
	public function get_modules() {
		return $this->modules;
	}

	/**
	 * @param $name
	 *
	 * @return bool|mixed
	 */
	public function get_module( $name ) {
		if ( null === $name ) {
			return false;
		}
		if ( isset( $this->modules[ $name ] ) ) {
			return $this->modules[ $name ];
		}
		$name = "\\{$name}";
		if ( isset( $this->modules[ $name ] ) ) {
			return $this->modules[ $name ];
		}

		return false;
	}

}