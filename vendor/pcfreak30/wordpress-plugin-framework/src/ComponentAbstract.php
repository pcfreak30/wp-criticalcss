<?php


namespace pcfreak30\WordPress\Plugin\Framework;


/**
 * Class ComponentAbstract
 *
 * @package pcfreak30\WordPress\Plugin\Framework/*
 * @property PluginAbstract    $plugin
 * @property ComponentAbstract $parent
 */
abstract class ComponentAbstract extends BaseObjectAbstract {
	/**
	 * @var PluginAbstract
	 */
	private $plugin;

	/**
	 * @var ComponentAbstract
	 */
	private $parent;

	/**
	 *
	 */
	abstract public function init();

	/**
	 *
	 */
	public function __destruct() {
		$this->plugin = null;
		$this->parent = null;
	}

	/**
	 * @return ComponentAbstract
	 */
	public function get_parent() {
		return $this->parent;
	}

	/**
	 * @param ComponentAbstract $parent
	 */
	public function set_parent( $parent ) {
		$this->parent = $parent;
	}

	/**
	 * @return PluginAbstract
	 */
	public function get_plugin() {
		if ( null === $this->plugin ) {
			$parent = $this;
			while ( $parent->has_parent() ) {
				$parent = $parent->parent;
			}
			$this->plugin = $parent;
		}

		return $this->plugin;
	}

	/**
	 * @return bool
	 */
	public function has_parent() {
		return null !== $this->parent;
	}


	/**
	 * @param $components
	 */
	protected function set_component_parents( $components ) {
		/** @var ComponentAbstract $component */
		foreach ( $components as $component ) {
			$component->parent = $this;
		}
	}

	/**
	 * @return array|\ReflectionProperty[]
	 */
	protected function get_components() {
		$components = ( new \ReflectionClass( $this ) )->getProperties();
		$components = array_filter(
			$components,
			/**
			 * @param \ReflectionProperty $component
			 *
			 * @return bool
			 */
			function ( $component ) {
				$getter = 'get_' . $component->name;

				return method_exists( $this, $getter ) && ( new \ReflectionMethod( $this, $getter ) )->isPublic() && $this->$getter() instanceof ComponentAbstract;
			} );
		$components = array_map(
		/**
		 * @param \ReflectionProperty $component
		 *
		 * @return ComponentAbstract
		 */
			function ( $component ) {
				$getter = 'get_' . $component->name;

				return $this->$getter();
			}, $components );

		return $components;
	}

	/**
	 * Setup components and run init
	 */
	protected function setup_components() {
		$components = $this->get_components();
		$this->set_component_parents( $components );
		foreach ( $components as $component ) {
			$component->init();
		}
	}

}