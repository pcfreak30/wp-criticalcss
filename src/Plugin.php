<?php

namespace WP_CriticalCSS;

use WP_CriticalCSS\Admin\UI;
use WP_CriticalCSS\Managers\Integration;
use WP_CriticalCSS\Managers\Model;
use WP_CriticalCSS\Settings\Manager;

/**
 * Class Plugin
 *
 * @package WP_CriticalCSS
 * @property \WP_CriticalCSS\Managers\Integration $integration_manager
 * @property \WP_CriticalCSS\Data                 $data_manager
 * @property \WP_CriticalCSS\Cache                $cache_manager
 * @property \WP_CriticalCSS\Request              $request
 * @property \WP_CriticalCSS\Settings\Manager     $settings_manager
 * @property \WP_CriticalCSS\Queue_Manager        $queue_manager
 * @property \WP_CriticalCSS\Admin\UI             $admin_ui
 * @property \WP_CriticalCSS\Installer            $installer
 * @property \WP_CriticalCSS\Managers\Model       $model_manager
 */
class Plugin extends Core\Plugin {
	/**
	 *
	 */
	const VERSION = '0.7.7';

	/**
	 * Plugin slug name
	 */
	const PLUGIN_SLUG = 'wp_criticalcss';
	/**
	 *
	 */
	const TRANSIENT_PREFIX = 'wp_criticalcss_';
	/**
	 *
	 */
	const OPTIONNAME = 'wp_criticalcss';

	/**
	 * @var Model
	 */
	private $model_manager = Model::class;
	/**
	 * @var \WP_CriticalCSS\Managers\Integration
	 */
	private $integration_manager = Integration::class;
	/**
	 * @var \WP_CriticalCSS\Admin\UI
	 */
	private $admin_ui = UI::class;
	/**
	 * @var \WP_CriticalCSS\Data
	 */
	private $data_manager = Data::class;
	/**
	 * @var \WP_CriticalCSS\Cache
	 */
	private $cache_manager = Cache::class;
	/**
	 * @var \WP_CriticalCSS\Request
	 */
	private $request = Request::class;
	/**
	 * @var \WP_CriticalCSS\Settings\Manager
	 */
	private $settings_manager = Manager::class;
	/**
	 * @var \WP_CriticalCSS\Frontend
	 */
	private $frontend = Frontend::class;
	/**
	 * @var \WP_CriticalCSS\Installer
	 */
	private $installer = Installer::class;

	/**
	 * @var \WP_CriticalCSS\Queue_Manager
	 */
	private $queue_manager = Queue_Manager::class;

	/**
	 * @return \WP_CriticalCSS\Managers\Integration
	 */
	public function get_integration_manager() {
		return $this->integration_manager;
	}

	/**
	 * @param \WP_CriticalCSS\Managers\Integration $integration_manager
	 */
	public function set_integration_manager( Integration $integration_manager ) {
		$this->integration_manager = $integration_manager;
	}

	/**
	 * @return \WP_CriticalCSS\Data
	 */
	public function get_data_manager() {
		return $this->data_manager;
	}

	/**
	 * @param \WP_CriticalCSS\Data $data_manager
	 */
	public function set_data_manager( Data $data_manager ) {
		$this->data_manager = $data_manager;
	}

	/**
	 * @return \WP_CriticalCSS\Cache
	 */
	public function get_cache_manager() {
		return $this->cache_manager;
	}

	/**
	 * @param \WP_CriticalCSS\Cache $cache_manager
	 */
	public function set_cache_manager( Cache $cache_manager ) {
		$this->cache_manager = $cache_manager;
	}

	/**
	 * @return \WP_CriticalCSS\Request
	 */
	public function get_request() {
		return $this->request;
	}

	/**
	 * @param \WP_CriticalCSS\Request $request
	 */
	public function set_request( Request $request ) {
		$this->request = $request;
	}

	/**
	 * @return \WP_CriticalCSS\Frontend
	 */
	public function get_frontend() {
		return $this->frontend;
	}

	/**
	 * @param \WP_CriticalCSS\Frontend $frontend
	 */
	public function set_frontend( Frontend $frontend ) {
		$this->frontend = $frontend;
	}

	/**
	 * @return \WP_CriticalCSS\Installer
	 */
	public function get_installer() {
		return $this->installer;
	}

	/**
	 * @param \WP_CriticalCSS\Installer $installer
	 */
	public function set_installer( Installer $installer ) {
		$this->installer = $installer;
	}

	/**
	 * @param bool $network_wide
	 */
	public function activate( $network_wide ) {
		$this->installer->activate( $network_wide );
	}

	/**
	 * @param bool $network_wide
	 */
	public function deactivate( $network_wide ) {
		// TODO: Implement deactivate() method.
	}

	/**
	 *
	 */
	public function uninstall() {
		// TODO: Implement uninstall() method.
	}

	/**
	 * @param array $object
	 *
	 * @return false|mixed|string|\\WP_Error
	 */
	public
	function get_permalink(
		array $object
	) {
		if ( ! empty( $object['blog_id'] ) ) {
			switch_to_blog( $object['blog_id'] );
		}
		$enable_integration = false;
		if ( $this->integration_manager->is_enabled() ) {
			$this->integration_manager->disable_integrations();
			$enable_integration = true;
		}
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
		if ( $enable_integration ) {
			$this->integration_manager->enable_integrations();
		}
		if ( $url instanceof \WP_Error ) {
			return false;
		}

		$url_parts = parse_url( $url );
		if ( empty( $url_parts['path'] ) ) {
			$url_parts['path'] = '/';
		}
		$url_parts['path'] = trailingslashit( $url_parts['path'] ) . 'nocache/';
		$url               = http_build_url( $url_parts );
		if ( ! empty( $object['blog_id'] ) ) {
			restore_current_blog( $object['blog_id'] );
		}

		return apply_filters( 'wp_criticalcss_get_permalink', $url );
	}

	/**
	 * @return string
	 */
	public function get_transient_prefix() {
		return static::TRANSIENT_PREFIX;
	}

	/**
	 * @return string
	 */
	public function get_option_name() {
		return static::OPTIONNAME;
	}

	/**
	 * @return string
	 */
	public function get_lang_domain() {
		return $this->get_safe_slug();
	}

	/**
	 * @return \WP_CriticalCSS\Admin\UI
	 */
	public function get_admin_ui() {
		return $this->admin_ui;
	}

	/**
	 * @param \WP_CriticalCSS\Admin\UI $admin_ui
	 */
	public function set_admin_ui( $admin_ui ) {
		$this->admin_ui = $admin_ui;
	}

	/**
	 * @return \WP_CriticalCSS\Settings\Manager
	 */
	public function get_settings_manager() {
		return $this->settings_manager;
	}

	/**
	 * @param \WP_CriticalCSS\Settings\Manager $settings_manager
	 */
	public function set_settings_manager( $settings_manager ) {
		$this->settings_manager = $settings_manager;
	}

	/**
	 * @return string
	 */
	public function get_model_manager() {
		return $this->model_manager;
	}

	/**
	 * @param string $model_manager
	 */
	public function set_model_manager( $model_manager ) {
		$this->model_manager = $model_manager;
	}

	/**
	 * @return \WP_CriticalCSS\Queue_Manager
	 */
	public function get_queue_manager() {
		return $this->queue_manager;
	}

	/**
	 * @param \WP_CriticalCSS\Queue_Manager $queue_manager
	 */
	public function set_queue_manager( $queue_manager ) {
		$this->queue_manager = $queue_manager;
	}

	/**
	 * @return bool|void
	 * @throws \ComposePress\Core\Exception\ComponentMissing
	 * @throws \ComposePress\Core\Exception\Plugin
	 */
	protected function load_components() {
		if ( is_admin() ) {
			$this->load( 'admin_ui' );
			$this->load( 'installer' );
		}
		$this->load( 'settings_manager' );
		$this->load( 'model_manager' );
		$this->load( 'data_manager' );
		$this->load( 'cache_manager' );
		$this->load( 'integration_manager' );
		$this->load( 'request' );
		$this->load( 'queue' );
		$this->load( 'frontend' );
	}

}
