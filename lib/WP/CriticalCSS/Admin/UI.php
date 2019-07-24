<?php

namespace WP\CriticalCSS\Admin;

use ComposePress\Core\Abstracts\Component;
use WP\CriticalCSS;
use WP\CriticalCSS\Admin\UI\Post;
use WP\CriticalCSS\Admin\UI\Term;
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
class UI extends Component {
	/**
	 * @var \WP\CriticalCSS\Settings\API
	 */
	private $settings_ui;
	/**
	 * @var \WP\CriticalCSS\Queue\ListTableAbstract
	 */
	private $api_table;

	/**
	 * @var \WP\CriticalCSS\API
	 */
	private $api;
	/**
	 * @var \WP\CriticalCSS\Queue\Web\Check\Table
	 */
	private $web_check_table;
	/**
	 * @var \WP\CriticalCSS\Queue\Log\Table
	 */
	private $log_table;

	/**
	 * @var \WP\CriticalCSS\Admin\UI\Post
	 */
	private $post_ui;

	/**
	 * @var \WP\CriticalCSS\Admin\UI\Term
	 */
	private $term_ui;

	/**
	 * UI constructor.
	 *
	 * @param \WP\CriticalCSS\API|\WP\CriticalCSS\Settings\API $settings_ui
	 * @param \WP\CriticalCSS\API                              $api
	 * @param APITable                                         $api_table
	 * @param WebCheckTable                                    $web_check_table
	 * @param \WP\CriticalCSS\Queue\Log\Table                  $log_table
	 * @param \WP\CriticalCSS\Admin\UI\Post                    $post_ui
	 * @param \WP\CriticalCSS\Admin\UI\Term                    $term_ui
	 */
	public function __construct( SettingsAPI $settings_ui, API $api, APITable $api_table, WebCheckTable $web_check_table, LogTable $log_table, Post $post_ui, Term $term_ui ) {
		$this->settings_ui     = $settings_ui;
		$this->api             = $api;
		$this->api_table       = $api_table;
		$this->web_check_table = $web_check_table;
		$this->log_table       = $log_table;
		$this->post_ui         = $post_ui;
		$this->term_ui         = $term_ui;
	}

	/**
	 * @return \WP\CriticalCSS\Queue\ListTableAbstract
	 */
	public function get_api_table() {
		return $this->api_table;
	}

	/**
	 * @return \WP\CriticalCSS\API
	 */
	public function get_api() {
		return $this->api;
	}

	/**
	 * @return \WP\CriticalCSS\Queue\Web\Check\Table
	 */
	public function get_web_check_table() {
		return $this->web_check_table;
	}

	/**
	 * @return \WP\CriticalCSS\Queue\Log\Table
	 */
	public function get_log_table() {
		return $this->log_table;
	}


	/**
	 *
	 */
	public function init() {
		$this->setup_components();
		$this->api_table->set_queue( $this->plugin->api_queue );
		$this->log_table->set_queue( $this->plugin->log );
		$this->web_check_table->set_queue( $this->plugin->web_check_queue );
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
			] );
			add_action( 'update_option_wp_criticalcss_log', [
				$this,
				'delete_dummy_option',
			] );
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
		if ( 'on' !== $this->plugin->settings_manager->get_setting( 'template_cache' ) ) {
			$this->settings_ui->add_field( $this->plugin->get_option_name(), [
				'name'  => 'force_web_check',
				'label' => 'Force Web Check',
				'type'  => 'checkbox',
				'desc'  => __( 'Force a web check on all pages for css changes. This will run for new web requests.', $this->plugin->get_lang_domain() ),
			] );
		}
		if ( 'on' !== $this->plugin->settings_manager->get_setting( 'prioritize_manual_css' ) ) {
			$this->settings_ui->add_field( $this->plugin->get_option_name(), [
				'name'  => 'template_cache',
				'label' => 'Template Cache',
				'type'  => 'checkbox',
				'desc'  => __( 'Cache Critical CSS based on WordPress templates and not the post, page, term, author page, or arbitrary url. <p style="font-weight: bold;">This option may be useful if your website contains lots of content, pages, posts, etc. </p>', $this->plugin->get_lang_domain() ),
			] );
		}
		$this->settings_ui->add_field( $this->plugin->get_option_name(), [
			'name'  => 'prioritize_manual_css',
			'label' => 'Enable Manual CSS Override',
			'type'  => 'checkbox',
			'desc'  => __( 'Allow per post CSS, per term CSS, post type CSS or taxonomy CSS to override generated CSS always. By default generated css will take priority when it exists.', $this->plugin->get_lang_domain() ),
		] );

		if ( ! apply_filters( 'wp_criticalcss_cache_integration', false ) ) {
			$this->settings_ui->add_field( $this->plugin->get_option_name(), [
				'name'  => 'web_check_interval',
				'label' => 'Web Check Interval',
				'type'  => 'number',
				'desc'  => __( 'How often in seconds web pages should be checked for changes to re-generate CSS.', $this->plugin->get_lang_domain() ),
			] );
		}
		$this->settings_ui->add_field( $this->plugin->get_option_name(), [
			'name'              => 'force_include_styles',
			'label'             => 'Force Include Styles',
			'type'              => 'textarea',
			'desc'              => __( 'A list of CSS selectors and/or regex patterns for css selectors', $this->plugin->get_lang_domain() ),
			'sanitize_callback' => [
				$this,
				'validate_force_include_styles',
			],
		] );
		$this->settings_ui->add_field( $this->plugin->get_option_name(), [
			'name'  => 'fallback_css',
			'label' => 'Fallback CSS',
			'type'  => 'textarea',
			'desc'  => __( 'Global CSS to use as a fallback if generated and manual post css don\'t exist.', $this->plugin->get_lang_domain() ),
		] );
		if ( 'on' === $this->plugin->settings_manager->get_setting( 'prioritize_manual_css' ) ) {
			foreach ( get_post_types() as $post_type ) {
				$label = get_post_type_object( $post_type )->labels->singular_name;

				$this->settings_ui->add_field( $this->plugin->get_option_name(), [
					'name'  => "single_post_type_css_{$post_type}",
					'label' => 'Use Single CSS for ' . $label,
					'type'  => 'checkbox',
					'desc'  => sprintf( __( 'Use a single CSS for all %s items', $this->plugin->get_lang_domain() ), $label ),
				] );
				if ( 'on' === $this->plugin->settings_manager->get_setting( "single_post_type_css_{$post_type}" ) ) {
					$this->settings_ui->add_field( $this->plugin->get_option_name(), [
						'name'  => "single_post_type_css_{$post_type}_css",
						'label' => $label . ' post type CSS input',
						'type'  => 'textarea',
						'desc'  => sprintf( __( 'Enter CSS for all %s items', $this->plugin->get_lang_domain() ), $label ),
					] );
					$this->settings_ui->add_field( $this->plugin->get_option_name(), [
						'name'  => "single_post_type_css_{$post_type}_archive_css",
						'label' => $label . ' post type archive CSS input',
						'type'  => 'textarea',
						'desc'  => sprintf( __( 'Enter CSS for %s archive listings', $this->plugin->get_lang_domain() ), $label ),
					] );
				}
			}
			foreach ( get_taxonomies() as $taxonomy ) {
				$label = get_taxonomy( $taxonomy )->labels->singular_name;
				$this->settings_ui->add_field( $this->plugin->get_option_name(), [
					'name'  => "single_taxonomy_css_{$taxonomy}",
					'label' => 'Use Single CSS for ' . $label,
					'type'  => 'checkbox',
					'desc'  => sprintf( __( 'Use a single CSS for all %s items', $this->plugin->get_lang_domain() ), $label ),
				] );
				if ( 'on' === $this->plugin->settings_manager->get_setting( "single_taxonomy_css_{$taxonomy}_css" ) ) {
					$this->settings_ui->add_field( $this->plugin->get_option_name(), [
						'name'  => "single_taxonomy_css_{$taxonomy}_css",
						'label' => $label . ' taxonomy CSS input',
						'type'  => 'textarea',
						'desc'  => sprintf( __( 'Enter CSS for all %s items', $this->plugin->get_lang_domain() ), $label ),
					] );
				}
			}

		}
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
		if ( '' !== $this->plugin->settings_manager->get_setting( 'apikey' ) ) {
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
				<?php
				$this->settings_ui->add_field( 'wp_criticalcss_web_check_queue', [
					'name'  => 'web_check_queue',
					'label' => null,
					'type'  => 'html',
					'desc'  => ob_get_clean(),
				] );
			endif;
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
		}

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
			return '';
		}
		$this->api->set_api_key( $options['apikey'] );
		if ( ! $this->api->ping() ) {
			add_settings_error( 'apikey', 'invalid_apikey', 'CriticalCSS.com API Key is invalid' );
			$valid = false;
		}

		return ! $valid ? $valid : $options['apikey'];
	}

	/**
	 * @param $options
	 *
	 * @return bool|string
	 */
	public function validate_force_include_styles( $options ) {
		$valid = true;
		if ( ! empty( $options['force_include_styles'] ) ) {
			$lines = explode( "\n", $options['force_include_styles'] );
			$lines = array_map( 'trim', $lines );
			foreach ( $lines as $index => $line ) {
				if ( preg_match( '/^\/.*?\/[gimy]*$/', $line ) ) {
					preg_match( $line, null );
					if ( PREG_NO_ERROR !== preg_last_error() ) {
						add_settings_error( 'force_include_styles', 'invalid_force_include_styles_regex', sprintf( 'Line %d is an invalid regex for a force included style', $index + 1 ) );
						$valid = false;
						break;
					}

				}
			}
			if ( $valid ) {
				$options['force_include_styles'] = implode( "\n", $lines );
			}
		}

		return ! $valid ? $valid : $options['force_include_styles'];
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

	/**
	 * @return \WP\CriticalCSS\Admin\UI\Post
	 */
	public function get_post_ui() {
		return $this->post_ui;
	}

	/**
	 * @return \WP\CriticalCSS\Admin\UI\Term
	 */
	public function get_term_ui() {
		return $this->term_ui;
	}


}
