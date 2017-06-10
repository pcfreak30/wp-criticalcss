<?php

namespace WP;

use WP\CriticalCSS\ComponentAbstract;

class CriticalCSS {
	/**
	 *
	 */
	const VERSION = '0.6.3';

	/**
	 *
	 */
	const LANG_DOMAIN = 'wp_criticalcss';

	/**
	 *
	 */
	const OPTIONNAME = 'wp_criticalcss';

	/**
	 *
	 */
	const TRANSIENT_PREFIX = 'wp_criticalcss_web_check_';
	/**
	 * @var array
	 */

	/**
	 * @var bool
	 */
	protected $nocache = false;
	/**
	 * @var \WP\CriticalCSS\Web\Check\Background\Process
	 */
	protected $web_check_queue;
	/**
	 * @var \WP\CriticalCSS\API\Background\Process
	 */
	protected $api_queue;
	/**
	 * @var array
	 */
	protected $settings = [];
	/**
	 * @var string
	 */
	protected $template;

	/**
	 * @var \WP\CriticalCSS\Admin\UI
	 */
	protected $admin_ui;

	/**
	 * @var \WP\CriticalCSS\Data\Manager
	 */
	protected $data_manager;

	/**
	 * @var \WP\CriticalCSS\Cache\Manager
	 */
	protected $cache_manager;

	/**
	 * @var \WP\CriticalCSS\Request
	 */
	protected $request;
	/**
	 * @var \WP\CriticalCSS\Integration\Manager
	 */
	protected $integration_manager;
	/**
	 * @var \WP\CriticalCSS\Settings\Manager
	 */
	protected $settings_manager;
	/**
	 * @var \WP\CriticalCSS\Frontend
	 */
	protected $frontend;


	public function __construct(
		CriticalCSS\Settings\Manager $settings_manager,
		CriticalCSS\Admin\UI $admin_ui,
		CriticalCSS\Data\Manager $data_manager,
		CriticalCSS\Cache\Manager $cache_manager,
		CriticalCSS\Request $request,
		CriticalCSS\Integration\Manager $integration_manager,
		CriticalCSS\API\Background\Process $api_queue,
		CriticalCSS\Frontend $frontend,
		CriticalCSS\Web\Check\Background\Process $web_check_queue
	) {
		$this->settings_manager    = $settings_manager;
		$this->settings            = $this->settings_manager->get_settings();
		$this->admin_ui            = $admin_ui;
		$this->data_manager        = $data_manager;
		$this->cache_manager       = $cache_manager;
		$this->request             = $request;
		$this->integration_manager = $integration_manager;
		$this->api_queue           = $api_queue;
		$this->web_check_queue     = $web_check_queue;
		$this->frontend            = $frontend;;
		$this->set_parent();
	}

	protected function set_parent() {
		foreach ( $this as $property ) {
			if ( $property instanceof ComponentAbstract ) {
				$property->set_app( $this );
			}
		}
	}

	/**
	 * @return \WP\CriticalCSS\Admin\UI
	 */
	public function get_admin_ui() {
		return $this->admin_ui;
	}

	/**
	 * @return \WP\CriticalCSS\Frontend
	 */
	public function get_frontend() {
		return $this->frontend;
	}

	/**
	 * @return \WP\CriticalCSS\Settings\Manager
	 */
	public function get_settings_manager() {
		return $this->settings_manager;
	}

	/**
	 * @return \WP\CriticalCSS\Web\Check\Background\Process
	 */
	public function get_web_check_queue() {
		return $this->web_check_queue;
	}

	/**
	 * @return \WP\CriticalCSS\Data\Manager
	 */
	public function get_data_manager() {
		return $this->data_manager;
	}

	/**
	 * @return CriticalCSS\Cache\Manager
	 */
	public function get_cache_manager() {
		return $this->cache_manager;
	}

	/**
	 *
	 */


	/**
	 *
	 */
	public function activate() {
		global $wpdb;
		$settings    = $this->settings_manager->get_settings();
		$no_version  = ! empty( $settings ) && empty( $settings['version'] );
		$version_0_3 = false;
		$version_0_4 = false;
		$version_0_5 = false;
		if ( ! $no_version ) {
			$version     = $settings['version'];
			$version_0_3 = version_compare( '0.3.0', $version ) === 1;
			$version_0_4 = version_compare( '0.4.0', $version ) === 1;
			$version_0_5 = version_compare( '0.5.0', $version ) === 1;
		}
		if ( $no_version || $version_0_3 || $version_0_4 ) {
			remove_action(
				'update_option_criticalcss', [
					$this,
					'after_options_updated',
				]
			);
			if ( isset( $settings['disable_autopurge'] ) ) {
				unset( $settings['disable_autopurge'] );
				$this->update_settings( $settings );
			}
			if ( isset( $settings['expire'] ) ) {
				unset( $settings['expire'] );
				$this->update_settings( $settings );
			}
		}
		if ( $no_version || $version_0_3 || $version_0_4 || $version_0_5 ) {
			$wpdb->get_results( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_criticalcss_%', '_transient_timeout_criticalcss_%' ) );
		}

		if ( is_multisite() ) {
			foreach (
				get_sites(
					[
						'fields'       => 'ids',
						'site__not_in' => [ 1 ],
					]
				) as $blog_id
			) {
				switch_to_blog( $blog_id );
				$wpdb->query( "DROP TABLE {$wpdb->prefix}_wp_criticalcss_web_check_queue IF EXISTS" );
				$wpdb->query( "DROP TABLE {$wpdb->prefix}_wp_criticalcss_api_queue IF EXISTS" );
				restore_current_blog();
			}
		}

		$this->update_settings(
			array_merge(
				[
					'web_check_interval' => DAY_IN_SECONDS,
					'template_cache'     => 'off',
				], $this->get_settings(), [
					'version' => self::VERSION,
				]
			)
		);

		$this->init();
		$this->request->add_rewrite_rules();

		$this->web_check_queue->create_table();
		$this->api_queue->create_table();

		flush_rewrite_rules();
	}


	/**
	 *
	 */
	public function init() {
		$this->init_components();
	}

	public function init_components() {
		foreach ( $this as $property ) {
			if ( $property instanceof ComponentAbstract ) {
				$property->init();
			}
		}
	}

	/**
	 *
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * @param array $object
	 *
	 * @return false|mixed|string|\\WP_Error
	 */
	public function get_permalink( array $object ) {
		$this->integration_manager->disable_integrations();
		if ( ! empty( $object['object_id'] ) ) {
			$object['object_id'] = absint( $object['object_id'] );
		}
		switch ( $object['type'] ) {
			case 'post':
				$url = get_permalink( $object['object_id'] );
				break;
			case 'term':
				$url = get_term_link( $object['object_id'] );
				break;
			case 'author':
				$url = get_author_posts_url( $object['object_id'] );
				break;
			case 'url':
				$url = $object['url'];
				break;
			default:
				$url = $object['url'];
		}
		$this->integration_manager->enable_integrations();

		if ( $url instanceof \WP_Error ) {
			return false;
		}

		$url_parts         = parse_url( $url );
		$url_parts['path'] = trailingslashit( $url_parts['path'] ) . 'nocache/';
		if ( class_exists( 'http\Url' ) ) {
			/**
			 * @noinspection PhpUndefinedClassInspection
			 */
			$url = new \http\Url( $url_parts );
			$url = $url->toString();
		} else {
			$url = http_build_url( $url_parts );
		}

		return $url;
	}

	/**
	 * @return \WP\CriticalCSS\API\Background\Process
	 */
	public function get_api_queue() {
		return $this->api_queue;
	}

	/**
	 * @return \WP\CriticalCSS\Request
	 */
	public function get_request() {
		return $this->request;
	}

	/**
	 * @return \WP\CriticalCSS\Integration\Manager
	 */
	public function get_integration_manager() {
		return $this->integration_manager;
	}

	public function __destruct() {
		foreach ( $this as $property ) {
			if ( $property instanceof ComponentAbstract ) {
				$property->__destruct();
			}
		}
	}

	/**
	 * @param array $settings
	 */
	public function set_settings( $settings ) {
		$this->settings = $settings;
	}

}
