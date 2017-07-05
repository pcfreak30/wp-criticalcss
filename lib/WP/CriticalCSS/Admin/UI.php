<?php

namespace WP\CriticalCSS\Admin;

use WP\CriticalCSS;

class UI extends CriticalCSS\ComponentAbstract {
	/**
	 * @var \WP\CriticalCSS\Settings\API
	 */
	protected $settings_ui;

	/**
	 * @var \WP\CriticalCSS\Queue\ListTable
	 */
	protected $queue_table;

	public function init() {
		parent::init();
		if ( is_admin() ) {
			add_action( 'network_admin_menu', [
				$this,
				'settings_init',
			] );
			add_action( 'admin_menu', [
				$this,
				'settings_init',
			] );
			add_action( 'pre_update_option_wp_criticalcss', [
				$this,
				'sync_options',
			], 10, 2 );
		}
	}


	/**
	 * Build settings page configuration
	 */
	public function settings_init() {
		if ( is_multisite() ) {
			$hook = add_submenu_page( 'settings.php', 'WP Critical CSS', 'WP Critical CSS', 'manage_network_options', 'wp_criticalcss', [
				$this,
				'settings_ui',
			] );
		} else {
			$hook = add_options_page( 'WP Critical CSS', 'WP Critical CSS', 'manage_options', 'wp_criticalcss', [
				$this,
				'settings_ui',
			] );
		}
		add_action( "load-$hook", [
			$this,
			'screen_option',
		] );
		$this->settings_ui->add_section( [
			'id'    => CriticalCSS::OPTIONNAME,
			'title' => 'WP Critical CSS Options',
		] );
		$this->settings_ui->add_field( CriticalCSS::OPTIONNAME, [
			'name'              => 'apikey',
			'label'             => 'API Key',
			'type'              => 'text',
			'sanitize_callback' => [
				$this,
				'validate_criticalcss_apikey',
			],
			'desc'              => __( 'API Key for CriticalCSS.com. Please view yours at <a href="https://www.criticalcss.com/account/api-keys?aff=3">CriticalCSS.com</a>', CriticalCSS::LANG_DOMAIN ),
		] );
		$this->settings_ui->add_field( CriticalCSS::OPTIONNAME, [
			'name'  => 'force_web_check',
			'label' => 'Force Web Check',
			'type'  => 'checkbox',
			'desc'  => __( 'Force a web check on all pages for css changes. This will run for new web requests.', CriticalCSS::LANG_DOMAIN ),
		] );
		$this->settings_ui->add_field( CriticalCSS::OPTIONNAME, [
			'name'  => 'template_cache',
			'label' => 'Template Cache',
			'type'  => 'checkbox',
			'desc'  => __( 'Cache Critical CSS based on WordPress templates and not the post, page, term, author page, or arbitrary url.', CriticalCSS::LANG_DOMAIN ),
		] );
		if ( ! apply_filters( 'wp_criticalcss_cache_integration', false ) ) {
			$this->settings_ui->add_field( CriticalCSS::OPTIONNAME, [
				'name'  => 'web_check_interval',
				'label' => 'Web Check Interval',
				'type'  => 'number',
				'desc'  => __( 'How often in seconds web pages should be checked for changes to re-generate CSS', CriticalCSS::LANG_DOMAIN ),
			] );
		}
		$this->settings_ui->admin_init();
	}

	/**
	 * Render settings page
	 */
	public function settings_ui() {
		require ABSPATH . 'wp-admin/options-head.php';
		$this->settings_ui->add_section( [
			'id'    => 'wp_criticalcss_queue',
			'title' => 'WP Critical CSS Queue',
			'form'  => false,
		] );

		ob_start();

		?>
		<style type="text/css">
			.queue > th {
				display: none;
			}
		</style>
		<form method="post">
			<?php
			$this->queue_table->prepare_items();
			$this->queue_table->display();
			?>
		</form>
		<?php
		$this->settings_ui->add_field( 'wp_criticalcss_queue', [
			'name'  => 'queue',
			'label' => null,
			'type'  => 'html',
			'desc'  => ob_get_clean(),
		] );

		$this->settings_ui->admin_init();
		$this->settings_ui->show_navigation();
		$this->settings_ui->show_forms();
		?>

		<?php
	}


	/**
	 * Validate API key is real and error if so
	 *
	 * @param $options
	 *
	 * @return bool
	 */
	public function validate_criticalcss_apikey( $options ) {
		$valid = true;
		if ( empty( $options['apikey'] ) ) {
			$valid = false;
			add_settings_error( 'apikey', 'invalid_apikey', __( 'API Key is empty', CriticalCSS::LANG_DOMAIN ) );
		}
		if ( ! $valid ) {
			return $valid;
		}
		$api = new CriticalCSS\API( $options['apikey'] );
		if ( ! $api->ping() ) {
			add_settings_error( 'apikey', 'invalid_apikey', 'CriticalCSS.com API Key is invalid' );
			$valid = false;
		}

		return ! $valid ? $valid : $options['apikey'];
	}

	/**
	 * @return \WP\CriticalCSS\Settings\API
	 */
	public function get_settings_ui() {
		return $this->settings_ui;
	}

	/**
	 *
	 */
	public function screen_option() {
		add_screen_option( 'per_page', [
			'label'   => 'Queue Items',
			'default' => 20,
			'option'  => 'queue_items_per_page',
		] );
		$this->queue_table = new CriticalCSS\Queue\ListTable( WPCCSS()->get_api_queue() );
	}

	/**
	 * @param $value
	 * @param $old_value
	 *
	 * @return array
	 */
	public function sync_options( $value, $old_value ) {
		$original_old_value = $old_value;
		if ( ! is_array( $old_value ) ) {
			$old_value = [];
		}

		$value = array_merge( $old_value, $value );

		if ( isset( $value['force_web_check'] ) && 'on' == $value['force_web_check'] ) {
			$value['force_web_check'] = 'off';
			$this->app->get_cache_manager()->reset_web_check_transients();
		}

		if ( is_multisite() ) {
			update_site_option( CriticalCSS::OPTIONNAME, $value );
			$value = $original_old_value;
		}

		return $value;
	}
}
