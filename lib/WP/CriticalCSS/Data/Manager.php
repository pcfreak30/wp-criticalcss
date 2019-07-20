<?php

namespace WP\CriticalCSS\Data;

use ComposePress\Core\Abstracts\Component;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Data
 * @property \WP\CriticalCSS $plugin
 */
class Manager extends Component {

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	public function get_html_hash( $item = [] ) {
		return $this->get_item_data( $item, 'html_hash' );
	}

	/**
	 * @param array $item
	 * @param       $name
	 *
	 * @return mixed|null
	 */
	public function get_item_data( $item = [], $name ) {
		$value = null;
		if ( empty( $item ) ) {
			$item = $this->plugin->request->get_current_page_type();
		}
		if ( 'on' === $this->plugin->settings_manager->get_setting( 'template_cache' ) && ! empty( $item['template'] ) ) {
			if ( 'cache' === $name ) {
				$name = 'ccss';
			}
			$name  = [ $name, md5( $item['template'] ) ];
			$value = $this->plugin->cache_manager->get_store()->get_cache_fragment( $name );
		} else {
			if ( 'url' === $item['type'] ) {
				if ( 'cache' === $name ) {
					$name = 'ccss';
				}
				$name  = [ $name, md5( $item['url'] ) ];
				$value = $this->plugin->get_cache_manager()->get_store()->get_cache_fragment( $name );
			} else {
				if ( is_multisite() && ! empty( $item['blog_id'] ) ) {
					switch_to_blog( $item['blog_id'] );
				}
				$name = "{$this->plugin->get_safe_slug()}_{$name}";
				switch ( $item['type'] ) {
					case 'post':
						$value = get_post_meta( $item['object_id'], $name, true );
						break;
					case 'term':
						$value = get_term_meta( $item['object_id'], $name, true );
						break;
					case 'author':
						$value = get_user_meta( $item['object_id'], $name, true );
						break;

				}
				$value = wp_unslash( $value );

				if ( is_multisite() ) {
					restore_current_blog();
				}
			}
		}

		return $value;
	}

	/**
	 * @param        $item
	 * @param string $css
	 *
	 * @return void
	 * @internal param array $type
	 */
	public function set_cache( $item, $css ) {
		$this->set_item_data( $item, 'cache', $css );
	}

	/**
	 * @param     $item
	 * @param     $name
	 * @param     $value
	 * @param int $expires
	 */
	public function set_item_data( $item, $name, $value, $expires = 0 ) {
		if ( 'on' === $this->plugin->settings_manager->get_setting( 'template_cache' ) && ! empty( $item['template'] ) ) {
			if ( 'cache' === $name ) {
				$name = 'ccss';
			}
			$name = [ $name, md5( $item['template'] ) ];
			$this->plugin->cache_manager->get_store()->update_cache_fragment( $name, $value );
		} else {
			if ( 'url' === $item['type'] ) {
				if ( 'cache' === $name ) {
					$name = 'ccss';
				}
				$name = [ $name, md5( $item['url'] ) ];
				$this->plugin->cache_manager->get_store()->update_cache_fragment( $name, $value );
			} else {
				if ( is_multisite() && ! empty( $item['blog_id'] ) ) {
					switch_to_blog( $item['blog_id'] );
				}
				$name  = "{$this->plugin->get_safe_slug()}_{$name}";
				$value = wp_slash( $value );
				switch ( $item['type'] ) {
					case 'post':
						update_post_meta( $item['object_id'], $name, $value );
						break;
					case 'term':
						update_term_meta( $item['object_id'], $name, $value );
						break;
					case 'author':
						update_user_meta( $item['object_id'], $name, $value );
						break;
				}
				if ( is_multisite() ) {
					restore_current_blog();
				}
			}
		}

	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	public function get_css_hash( $item = [] ) {
		return $this->get_item_data( $item, 'css_hash' );
	}

	/**
	 * @param        $item
	 * @param string $hash
	 *
	 * @return void
	 * @internal param array $type
	 */
	public function set_css_hash( $item, $hash ) {
		$this->set_item_data( $item, 'css_hash', $hash );
	}

	/**
	 * @param        $item
	 * @param string $hash
	 *
	 * @return void
	 * @internal param array $type
	 */
	public function set_html_hash( $item, $hash ) {
		$this->set_item_data( $item, 'html_hash', $hash );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	public function get_cache( $item = [] ) {
		if ( empty( $item ) ) {
			$item = $this->plugin->request->get_current_page_type();
		}
		$item = $this->get_item_css_override( $item );

		return $this->get_item_data( $item, 'cache' );
	}

	protected function get_item_css_override( $item ) {
		if ( 'on' === $this->plugin->settings_manager->get_setting( 'template_cache' ) ) {
			return $item;
		}
		$tree        = array_reverse( $this->get_item_parent_tree( $item ) );
		$parent_item = $item;
		foreach ( $tree as $leaf ) {
			$parent_item['object_id'] = $leaf;
			$override                 = (bool) $this->get_item_data( $parent_item, 'override_css' );
			if ( $override ) {
				return $parent_item;
			}
		}

		return $item;
	}

	protected function get_item_parent_tree( $item ) {
		$tree         = [];
		$object_id    = 0;
		$hierarchical = false;
		switch ( $item['type'] ) {
			case 'post':
				$hierarchical = get_post_type_object( get_post_type( $item['object_id'] ) )->hierarchical;
				break;
			case 'term':
				$hierarchical = get_taxonomy( get_term( $item['object_id'] )->taxonomy )->hierarchical;
				break;
		}
		if ( ! $hierarchical ) {
			return $tree;
		}
		do {
			switch ( $item['type'] ) {
				case 'post':
					$object_id = wp_get_post_parent_id( $item['object_id'] );
					break;
				case 'term':
					$object_id = get_term( $item['object_id'] )->parent;
					break;
			}
			$tree[]            = $object_id;
			$item['object_id'] = $object_id;
		} while ( ! empty( $object_id ) );


		return array_filter( $tree );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	public function get_manual_css( $item = [] ) {
		if ( empty( $item ) ) {
			$item = $this->plugin->request->get_current_page_type();
		}
		$item = $this->get_item_css_override( $item );

		return $this->get_item_data( $item, 'manual_css' );
	}

	/**
	 * @param $item
	 *
	 * @return string
	 * @SuppressWarnings("unused")
	 */
	public function get_item_hash( $item ) {
		extract( $item, EXTR_OVERWRITE );
		$parts = [
			'object_id',
			'type',
			'url',
		];
		if ( 'on' === $this->plugin->settings_manager->get_setting( 'template_cache' ) ) {

			$template = $this->plugin->request->template;
			$parts    = [ 'template' ];
		}

		$type = [];

		foreach ( [ 'object_id', 'type', 'url', 'template', 'blog_id' ] as $var ) {
			if ( isset( $$var ) ) {
				$type[ $var ] = $$var;
			}
		}

		return md5( serialize( $type ) );
	}

	/**
	 *
	 */
	public function init() {
		// noop
	}
}
