<?php


class WP_CriticalCSS_Test extends WP_CriticalCSS {
	public static function get_instance() {
		if ( ! ( self::$instance instanceof WP_CriticalCSS_Test ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public static function reset() {
		self::$instance = new parent();
	}

	public function get_current_page_type() {
		return parent::get_current_page_type();
	}

	public function update_cache_fragment( $path, $value ) {
		return parent::update_cache_fragment( $path, $value );
	}

	public function get_cache_fragment( $path ) {
		return parent::get_cache_fragment( $path );
	}

}