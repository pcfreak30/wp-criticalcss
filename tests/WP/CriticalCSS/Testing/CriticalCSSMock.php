<?php


namespace WP\CriticalCSS\Testing;


use WP\CriticalCSS;

class CriticalCSSMock extends CriticalCSS {
	public function get_settings() {
		return $this->settings;
	}

	public function init_components() {

	}

	/**
	 * @return \WP\CriticalCSS\Testing\Admin\UIMock
	 */
	public function get_admin_ui() {
		return $this->admin_ui;
	}

}