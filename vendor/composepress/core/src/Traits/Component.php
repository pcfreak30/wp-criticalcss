<?php

namespace ComposePress\Core\Traits;

use ComposePress\Core\Abstracts\Plugin;

trait Component {
	use BaseObject;
	/**
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * @var Component
	 */
	private $parent;

	/**
	 *
	 */
	public function __destruct() {
		$this->plugin = null;
		$this->parent = null;
	}

	/**
	 * @return Component
	 */
	public function get_parent() {
		return $this->parent;
	}

	/**
	 * @param Component $parent
	 */
	public function set_parent( $parent ) {
		$this->parent = $parent;
	}

	/**
	 * @return Plugin
	 */
	public function get_plugin() {
		if ( null === $this->plugin ) {
			$parent = $this;
			while ( $parent->has_parent() ) {
				$parent = $parent->parent;
			}
			$this->plugin = $parent;
		}

		if ( $this->plugin === $this && ! ( $this instanceof Plugin ) ) {
			throw new \Exception( 'Plugin property is equal to self. Did you forget to set the parent or create a getter?' );
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
	 * Setup components and run init
	 */
	protected function setup_components() {
		$components = $this->get_components();
		$this->set_component_parents( $components );
		/** @var \ComposePress\Core\Abstracts\Component[] $components */
		foreach ( $components as $component ) {
			if ( method_exists( $component, 'init' ) ) {
				$component->init();
			}
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

				if ( ! ( method_exists( $this, $getter ) && ( new \ReflectionMethod( $this, $getter ) )->isPublic() ) ) {
					return false;
				}

				$property = $this->$getter();

				if ( ! is_object( $property ) ) {
					return false;
				}

				$trait = __TRAIT__;
				$used  = class_uses( $property );
				if ( ! isset( $used[ $trait ] ) ) {
					$parents = class_parents( $property );
					while ( ! isset( $used[ $trait ] ) && $parents ) {
						//get trait used by parents
						$used = class_uses( array_pop( $parents ) );
					}
				}

				return isset( array_flip( $used )[ $trait ] );
			} );
		$components = array_map(
		/**
		 * @param \ReflectionProperty $component
		 *
		 * @return Component
		 */
			function ( $component ) {
				$getter = 'get_' . $component->name;

				return $this->$getter();
			}, $components );

		return $components;
	}

	/**
	 * @param $components
	 */
	protected function set_component_parents( $components ) {
		/** @var Component $component */
		foreach ( $components as $component ) {
			$component->parent = $this;
		}
	}

	/**
	 *
	 */
	abstract public function init();

}