<?php

namespace WP\CriticalCSS\Testing\Unit\CriticalCSS\Settings;

use WP\CriticalCSS\Settings\Manager;

class ManagerMock extends Manager {
	public function get_settings() {
		return WPCCSS()->get_settings();
	}

	public function update_settings( array $settings ) {
		WPCCSS()->set_settings( $settings );

		return true;
	}
}