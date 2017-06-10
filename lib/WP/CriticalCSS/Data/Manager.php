<?php

namespace WP\CriticalCSS\Data;

use WP\CriticalCSS;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Data
 */
class Manager extends CriticalCSS\ComponentAbstract {

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
			$item = WPCCSS()->get_request()->get_current_page_type();
		}
		if ( 'on' == $this->settings['template_cache'] && ! empty( $item['template'] ) ) {
			$name  = "criticalcss_{$name}_" . md5( $item['template'] );
			$value = get_transient( $name );
		} else {
			if ( 'url' == $item['type'] ) {
				$name  = "criticalcss_url_{$name}_" . md5( $item['url'] );
				$value = get_transient( $name );
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
		if ( 'on' == $this->settings['template_cache'] && ! empty( $item['template'] ) ) {
			$name = "criticalcss_{$name}_" . md5( $item['template'] );
			set_transient( $name, $value, $expires );
		} else {
			if ( 'url' == $item['type'] ) {
				$name = "criticalcss_url_{$name}_" . md5( $item['url'] );
				set_transient( $name, $value, $expires );
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
		extract( $item );
		$parts = [
			'object_id',
			'type',
			'url',
		];
		if ( 'on' == $this->settings['template_cache'] ) {

			$template = $this->app->get_request()->get_template();
			$parts    = [ 'template' ];
		}
		$type = compact( $parts );

		return md5( serialize( $type ) );
	}
}
