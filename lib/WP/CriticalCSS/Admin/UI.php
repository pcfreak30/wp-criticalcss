<?php

namespace WP\CriticalCSS\Admin;

use pcfreak30\WordPress\Plugin\Framework\ComponentAbstract;
use WP\CriticalCSS;
use WP\CriticalCSS\API;
use WP\CriticalCSS\Queue\API\Table as APITable;
use WP\CriticalCSS\Queue\Log\Table as LogTable;
use WP\CriticalCSS\Queue\Web\Check\Table as WebCheckTable;
use WP\CriticalCSS\Settings\API as SettingsAPI;

/**
 * Class UI
 *
 * @package WP\CriticalCSS\Admin
 * @property \WP\CriticalCSS $plugin
 */
class UI extends ComponentAbstract {
	/**
	 * @var \WP\CriticalCSS\Settings\API
	 */
	private $settings_ui;
	/**
	 * @var \WP\CriticalCSS\Queue\ListTableAbstract
	 */
	private $api_table;

	private $api;
	/**
	 * @var \WP\CriticalCSS\Queue\Web\Check\Table
	 */
	private $web_check_table;
	private $log_table;

	/**
	 * UI constructor.
	 *
	 * @param \WP\CriticalCSS\API|\WP\CriticalCSS\Settings\API $settings_ui
	 * @param \WP\CriticalCSS\API                              $api
	 * @param APITable                                         $api_table
	 * @param WebCheckTable                                    $web_check_table
	 * @param \WP\CriticalCSS\Queue\Log\Table                  $log_table
	 */
	public function __construct( SettingsAPI $settings_ui, API $api, APITable $api_table, WebCheckTable $web_check_table, LogTable $log_table ) {
		$this->settings_ui     = $settings_ui;
		$this->api             = $api;
		$this->api_table       = $api_table;
		$this->web_check_table = $web_check_table;
		$this->log_table       = $log_table;
	}


	/**
	 *
	 */
	public function init() {
		$this->setup_components();

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
			add_action( 'update_option_wp_criticalcss_web_check_queue', [
				$this,
				'delete_dummy_option',
			], 10, 2 );
			add_action( 'update_option_wp_criticalcss_api_queue', [
				$this,
				'delete_dummy_option',
			], 10, 2 );
			add_action( 'update_option_wp_criticalcss_log', [
				$this,
				'delete_dummy_option',
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
			'id'    => $this->plugin->get_option_name(),
			'title' => 'Options',
		] );
		$this->settings_ui->add_field( $this->plugin->get_option_name(), [
			'name'              => 'apikey',
			'label'             => 'API Key',
			'type'              => 'text',
			'sanitize_callback' => [
				$this,
				'validate_criticalcss_apikey',
			],
			'desc'              => __( 'API Key for CriticalCSS.com. Please view yours at <a href="https://www.criticalcss.com/account/api-keys?aff=3">CriticalCSS.com</a>', CriticalCSS::LANG_DOMAIN ),
		] );
		$this->settings_ui->add_field( $this->plugin->get_option_name(), [
			'name'  => 'force_web_check',
			'label' => 'Force Web Check',
			'type'  => 'checkbox',
			'desc'  => __( 'Force a web check on all pages for css changes. This will run for new web requests.', $this->plugin->get_lang_domain() ),
		] );
		$this->settings_ui->add_field( $this->plugin->get_option_name(), [
			'name'  => 'template_cache',
			'label' => 'Template Cache',
			'type'  => 'checkbox',
			'desc'  => __( 'Cache Critical CSS based on WordPress templates and not the post, page, term, author page, or arbitrary url.', $this->plugin->get_lang_domain() ),
		] );
		if ( ! apply_filters( 'wp_criticalcss_cache_integration', false ) ) {
			$this->settings_ui->add_field( $this->plugin->get_option_name(), [
				'name'  => 'web_check_interval',
				'label' => 'Web Check Interval',
				'type'  => 'number',
				'desc'  => __( 'How often in seconds web pages should be checked for changes to re-generate CSS', $this->plugin->get_lang_domain() ),
			] );
		}
		$this->settings_ui->add_field( $this->plugin->get_option_name(), [
			'name'  => 'force_include_styles',
			'label' => 'Force Include Styles',
			'type'  => 'textarea',
			'desc'  => __( 'A list of CSS selectors and/or regex patterns for css selectors', $this->plugin->get_lang_domain() ),
		] );
		$this->settings_ui->admin_init();
	}

	/**
	 * Render settings page
	 */
	public function settings_ui() {
		require_once ABSPATH . 'wp-admin/options-head.php';
		$template_cache = 'on' === $this->plugin->settings_manager->get_setting( 'template_cache' );
		if ( ! $template_cache ) {
			$this->settings_ui->add_section( [
				'id'    => 'wp_criticalcss_web_check_queue',
				'title' => 'Web Check Queue',
				'form'  => false,
			] );
		}
		$this->settings_ui->add_section( [
			'id'    => 'wp_criticalcss_api_queue',
			'title' => 'API Queue',
			'form'  => false,
		] );
		$this->settings_ui->add_section( [
			'id'    => 'wp_criticalcss_log',
			'title' => 'Processed Log',
			'form'  => false,
		] );
		?>
		<style type="text/css">
			.form-table .api_queue > th, .form-table .web_check_queue > th {
				display: none;
			}

			.no-items, .manage-column, .form-table .api_queue td, .form-table .web_check_queue td {
				text-align: center !important;
			}

			.form-table th {
				width: auto;
			}

			.group h2 {
				display: none;
			}
		</style>

		<?php
		if ( ! $template_cache ): ?>
			<?php ob_start(); ?>
			<p>
				<?php _e( 'What is this? This queue is designed to process your content only if "template" mode is off. It detects changes to the content and sends to the "API Queue" if any are found.', $this->plugin->get_lang_domain() ); ?>
			</p>
			<form method="post">
				<?php
				$this->web_check_table->prepare_items();
				$this->web_check_table->display();
				?>
			</form>
		<?php endif;
		$this->settings_ui->add_field( 'wp_criticalcss_web_check_queue', [
			'name'  => 'web_check_queue',
			'label' => null,
			'type'  => 'html',
			'desc'  => ob_get_clean(),
		] );
		ob_start(); ?>
		<p>
			<?php _e( 'What is this? This queue actually processes requests by sending them to CriticalCSS.com and waiting on them to process. When done it will purge any supported cache and make that page just a bit faster :)', $this->plugin->get_lang_domain() ); ?>
		</p>
		<form method="post">
			<?php
			$this->api_table->prepare_items();
			$this->api_table->display();
			?>
		</form>
		<?php
		$this->settings_ui->add_field( 'wp_criticalcss_api_queue', [
			'name'  => 'api_queue',
			'label' => null,
			'type'  => 'html',
			'desc'  => ob_get_clean(),
		] );

		ob_start(); ?>
		<p>
			<?php _e( 'What is this? This is a list of all processed pages and/or templates. This log will clear when critical css expires.', $this->plugin->get_lang_domain() ); ?>
		</p>
		<form method="post">
			<?php
			$this->log_table->prepare_items();
			$this->log_table->display();
			?>
		</form>
		<?php
		$this->settings_ui->add_field( 'wp_criticalcss_log', [
			'name'  => 'log',
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
			add_settings_error( 'apikey', 'invalid_apikey', __( 'API Key is empty', $this->plugin->get_option_name() ) );
		}
		if ( ! $valid ) {
			return $valid;
		}
		$this->api->set_api_key( $options['apikey'] );
		if ( ! $this->api->ping() ) {
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
		$this->api_table->init();
		$this->web_check_table->init();
		$this->log_table->init();
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

		if ( is_multisite() ) {
			$old_value = $this->plugin->settings_manager->get_settings();
		}

		$value = array_merge( $old_value, $value );

		if ( isset( $value['force_web_check'] ) && 'on' === $value['force_web_check'] ) {
			$value['force_web_check'] = 'off';
			$this->plugin->get_cache_manager()->purge_page_cache();
		}
		if ( $value['web_check_interval'] != $old_value['web_check_interval'] ) {
			$scheduled = wp_next_scheduled( 'wp_criticalcss_purge_log' );
			if ( $scheduled ) {
				wp_unschedule_event( $scheduled, 'wp_criticalcss_purge_log' );
			}

		}

		if ( is_multisite() ) {
			update_site_option( $this->plugin->get_option_name(), $value );
			$value = $original_old_value;
		}


		return $value;
	}

	/**
	 * @param        $old_value
	 * @param        $value
	 * @param string $option
	 */
	public function delete_dummy_option( $old_value, $value, $option ) {
		delete_option( $option );
	}
}
