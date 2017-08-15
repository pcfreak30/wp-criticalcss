<?php

namespace WP\CriticalCSS\Data;

use pcfreak30\WordPress\Plugin\Framework\ComponentAbstract;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Data
 * @property \WP\CriticalCSS $plugin
 */
class Manager extends ComponentAbstract {

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
		if ( 'on' === $this->settings['template_cache'] && ! empty( $item['template'] ) ) {
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
				$name = "criticalcss_{$name}";
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
		if ( 'on' === $this->settings['template_cache'] && ! empty( $item['template'] ) ) {
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
				$name  = "criticalcss_{$name}";
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
		return $this->get_item_data( $item, 'cache' );
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
		if ( 'on' === $this->settings['template_cache'] ) {

			$template = $this->plugin->request->template;
			$parts    = [ 'template' ];
		}
		$type = compact( $parts );

		return md5( serialize( $type ) );
	}

	/**
	 *
	 */
	public function init() {
		// noop
	}
}
