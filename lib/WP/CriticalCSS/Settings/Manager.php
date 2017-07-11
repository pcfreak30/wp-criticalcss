<?php


namespace WP\CriticalCSS\Settings;


use WP\CriticalCSS;

class Manager {
	protected $settings = [
		'version',
		'web_check_interval',
		'apikey',
		'force_web_check',
		'template_cache',
		'web_check_interval',
	];

	public function get_setting( $name ) {
		$settings = $this->get_settings();
		if ( empty( $settings ) || ! isset( $settings[ $name ] ) ) {
			return false;
		}

		return $settings[ $name ];
	}

	/**
	 * @return array
	 */
	public function get_settings() {
		if ( is_multisite() ) {
			return $this->get_settings_multisite();

		}
		$settings = get_option( CriticalCSS::OPTIONNAME, [] );
		if ( empty( $settings ) ) {
			$settings = [];
		}

		$settings = array_merge( $this->get_defaults(), $settings );

		return $settings;
	}

	private function get_settings_multisite() {
		$settings = get_site_option( CriticalCSS::OPTIONNAME, [] );
		if ( empty( $settings ) ) {
			$settings = get_option( CriticalCSS::OPTIONNAME, [] );
		}
		if ( empty( $settings ) ) {
			$settings = [];
		}

		return $settings;
	}

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	public function update_settings( array $settings ) {
		WPCCSS()->set_settings( $settings );
		if ( is_multisite() ) {
			return update_site_option( CriticalCSS::OPTIONNAME, $settings );
		}

		return update_option( CriticalCSS::OPTIONNAME, $settings );
	}

	protected function get_defaults() {

		$defaults = [];
		foreach ( $this->settings as $setting ) {
			$defaults[ $setting ] = null;
		}

		return $defaults;
	}
}