<?php

namespace WP\CriticalCSS\Testing\Unit\CriticalCSS\Settings;

use WP\CriticalCSS\Settings\Manager;

class ManagerMock extends Manager {
	public function get_settings() {
		$defaults = [];
		foreach ( $this->settings as $setting ) {
			$defaults[ $setting ] = null;
		}

		$settings = array_merge( $this->get_defaults(), WPCCSS()->get_settings() );

		return $settings;
	}

	public function update_settings( array $settings ) {
		WPCCSS()->set_settings( $settings );

		return true;
	}
}