<?php

namespace WP\CriticalCSS\Testing\Unit\CriticalCSS\Settings;

use WP\CriticalCSS\Settings\Manager;

class ManagerMock extends Manager {
	protected $settings = [];

	public function get_settings() {

		return $this->settings;
	}

	public function update_settings( array $settings ) {
		$this->settings = $settings;

		return true;
	}
}