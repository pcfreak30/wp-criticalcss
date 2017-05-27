<?php


/**
 * Class WP_CriticalCSS_Admin_UI
 */
class WP_CriticalCSS_Admin_UI {
	/**
	 * @var \WP_CriticalCSS_Settings_API
	 */
	private $_settings_ui;

	/**
	 * @var \WP_CriticalCSS_Queue_List_Table
	 */
	private $_queue_table;

	/**
	 * WP_CriticalCSS_Admin_UI constructor.
	 */
	public function __construct() {
		add_action( 'network_admin_menu', array( $this, 'settings_init' ) );
		add_action( 'admin_menu', array( $this, 'settings_init' ) );
		add_action( 'pre_update_option_wp_criticalcss', array( $this, 'sync_options' ), 10, 2 );

	}

	/**
	 * Build settings page configuration
	 */
	public function settings_init() {
		if ( is_multisite() ) {
			$hook = add_submenu_page( 'settings.php', 'WP Critical CSS', 'WP Critical CSS', 'manage_network_options', 'wp_criticalcss', array(
				$this,
				'settings_ui',
			) );
		} else {
			$hook = add_options_page( 'WP Critical CSS', 'WP Critical CSS', 'manage_options', 'wp_criticalcss', array(
				$this,
				'settings_ui',
			) );
		}
		add_action( "load-$hook", array( $this, 'screen_option' ) );
		$this->_settings_ui->add_section( array(
			'id'    => WP_CriticalCSS::OPTIONNAME,
			'title' => 'WP Critical CSS Options',
		) );
		$this->_settings_ui->add_field( WP_CriticalCSS::OPTIONNAME, array(
			'name'              => 'apikey',
			'label'             => 'API Key',
			'type'              => 'text',
			'sanitize_callback' => array( $this, 'validate_criticalcss_apikey' ),
			'desc'              => __( 'API Key for CriticalCSS.com. Please view yours at <a href="https://www.criticalcss.com/account/api-keys?aff=3">CriticalCSS.com</a>', WP_CriticalCSS::LANG_DOMAIN ),
		) );
		$this->_settings_ui->add_field( WP_CriticalCSS::OPTIONNAME, array(
			'name'  => 'force_web_check',
			'label' => 'Force Web Check',
			'type'  => 'checkbox',
			'desc'  => __( 'Force a web check on all pages for css changes. This will run for new web requests.', WP_CriticalCSS::LANG_DOMAIN ),
		) );
		$this->_settings_ui->add_field( WP_CriticalCSS::OPTIONNAME, array(
			'name'  => 'template_cache',
			'label' => 'Template Cache',
			'type'  => 'checkbox',
			'desc'  => __( 'Cache Critical CSS based on WordPress templates and not the post, page, term, author page, or arbitrary url.', WP_CriticalCSS::LANG_DOMAIN ),
		) );
		if ( ! apply_filters( 'wp_criticalcss_cache_integration', false ) ) {
			$this->_settings_ui->add_field( WP_CriticalCSS::OPTIONNAME, array(
				'name'  => 'web_check_interval',
				'label' => 'Web Check Interval',
				'type'  => 'number',
				'desc'  => __( 'How often in seconds web pages should be checked for changes to re-generate CSS', WP_CriticalCSS::LANG_DOMAIN ),
			) );
		}
		$this->_settings_ui->admin_init();
	}

	/**
	 * Render settings page
	 */
	public function settings_ui() {
		require ABSPATH . 'wp-admin/options-head.php';
		$this->_settings_ui->add_section( array(
			'id'    => 'wp_criticalcss_queue',
			'title' => 'WP Critical CSS Queue',
			'form'  => false,
		) );

		ob_start();

		?>
        <style type="text/css">
            .queue > th {
                display: none;
            }
        </style>
        <form method="post">
			<?php
			$this->_queue_table->prepare_items();
			$this->_queue_table->display();
			?>
        </form>
		<?php
		$this->_settings_ui->add_field( 'wp_criticalcss_queue', array(
			'name'  => 'queue',
			'label' => null,
			'type'  => 'html',
			'desc'  => ob_get_clean(),
		) );

		$this->_settings_ui->admin_init();
		$this->_settings_ui->show_navigation();
		$this->_settings_ui->show_forms();
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
			add_settings_error( 'apikey', 'invalid_apikey', __( 'API Key is empty', WP_CriticalCSS::LANG_DOMAIN ) );
		}
		if ( ! $valid ) {
			return $valid;
		}
		$api = new WP_CriticalCSS_API( $options['apikey'] );
		if ( ! $api->ping() ) {
			add_settings_error( 'apikey', 'invalid_apikey', 'CriticalCSS.com API Key is invalid' );
			$valid = false;
		}

		return ! $valid ? $valid : $options['apikey'];
	}

	/**
	 * @return \WP_CriticalCSS_Settings_API
	 */
	public function get_settings_ui() {
		return $this->_settings_ui;
	}

	/**
	 *
	 */
	public function screen_option() {
		add_screen_option( 'per_page', array(
			'label'   => 'Queue Items',
			'default' => 20,
			'option'  => 'queue_items_per_page',
		) );
		$this->_queue_table = new WP_CriticalCSS_Queue_List_Table( WPCCSS()->get_api_queue() );
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
			$old_value = array();
		}

		$value = array_merge( $old_value, $value );

		if ( isset( $value['force_web_check'] ) && 'on' == $value['force_web_check'] ) {
			$value['force_web_check'] = 'off';
			WPCCSS()->reset_web_check_transients();
		}

		if ( is_multisite() ) {
			update_site_option( WP_CriticalCSS::OPTIONNAME, $value );
			$value = $original_old_value;
		}

		return $value;
	}
}