<?php


namespace WP\CriticalCSS\Settings;


use ComposePress\Core\Abstracts\Component;
use WP\CriticalCSS;

/**
 * Class Manager
 *
 * @package WP\CriticalCSS\Settings
 * @property CriticalCSS $plugin
 */
class Manager extends Component {
	protected $settings = [
		'version',
		'web_check_interval',
		'apikey',
		'force_web_check',
		'template_cache',
		'web_check_interval',
		'prioritize_manual_css',
		'force_include_styles',
		'fallback_css',
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
		$settings = get_option( $this->plugin->get_option_name(), [] );
		if ( empty( $settings ) ) {
			$settings = [];
		}

		$settings = array_merge( $this->get_defaults(), $settings );

		return $settings;
	}

	private function get_settings_multisite() {
		$settings = get_site_option( $this->plugin->get_option_name(), [] );
		if ( empty( $settings ) ) {
			$settings = get_option( $this->plugin->get_option_name(), [] );
		}
		if ( empty( $settings ) ) {
			$settings = [];
		}

		$settings = array_merge( $this->get_defaults(), $settings );

		return $settings;
	}

	protected function get_defaults() {

		$defaults = [];
		foreach ( $this->settings as $setting ) {
			$defaults[ $setting ] = null;
		}

		return $defaults;
	}

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	public function update_settings( array $settings ) {
		if ( is_multisite() ) {
			return update_site_option( $this->plugin->get_option_name(), $settings );
		}

		return update_option( $this->plugin->get_option_name(), $settings );
	}

	/**
	 *
	 */
	public function init() {
		// TODO: Implement init() method.
	}
}
