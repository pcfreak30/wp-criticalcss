<?php

namespace WP;


use ComposePress\Core\Abstracts\Plugin;
use WP\CriticalCSS\Admin\UI;
use WP\CriticalCSS\API\Background\Process as BackgroundProcess;
use WP\CriticalCSS\Cache\Manager as CacheManager;
use WP\CriticalCSS\Data\Manager as DataManager;
use WP\CriticalCSS\Frontend;
use WP\CriticalCSS\Installer;
use WP\CriticalCSS\Integration\Manager as IntegrationManager;
use WP\CriticalCSS\Log;
use WP\CriticalCSS\Request;
use WP\CriticalCSS\Settings\Manager as SettingsManager;
use WP\CriticalCSS\Template\Log as TemplateLog;
use WP\CriticalCSS\Web\Check\Background\Process as WebCheckProcess;

/**
 * Class CriticalCSS
 *
 * @package WP
 * @property CacheManager                 $cache_manager
 * @property Request                      $request
 * @property DataManager                  $data_manager
 * @property IntegrationManager           $integration_manager
 * @property SettingsManager              $settings_manager
 * @property BackgroundProcess            $api_queue
 * @property WebCheckProcess              $web_check_queue
 * @property Frontend                     $frontend
 * @property UI                           $admin_ui
 * @property Installer                    $installer
 * @property Log                          $log
 * @property \WP\CriticalCSS\Template\Log $template_log
 */
class CriticalCSS extends Plugin {
	/**
	 *
	 */
	const VERSION = '0.7.7';

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
	const TRANSIENT_PREFIX = 'wp_criticalcss_';

	/**
	 *
	 */
	const PLUGIN_SLUG = 'wp-criticalcss';
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
	 * @var \WP\CriticalCSS\Integration\Manager
	 */
	protected $integration_manager;

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
	 * @var \WP\CriticalCSS\Settings\Manager
	 */
	protected $settings_manager;
	/**
	 * @var \WP\CriticalCSS\Frontend
	 */
	protected $frontend;
	/**
	 * @var \WP\CriticalCSS\Installer
	 */
	private $installer;
	/**
	 * @var \WP\CriticalCSS\Log
	 */
	private $log;

	/**
	 * @var \WP\CriticalCSS\Template\Log
	 */
	private $template_log;


	/**
	 * CriticalCSS constructor.
	 *
	 * @param \WP\CriticalCSS\Settings\Manager                                                           $settings_manager
	 * @param \WP\CriticalCSS\Admin\UI                                                                   $admin_ui
	 * @param \WP\CriticalCSS\Data\Manager                                                               $data_manager
	 * @param \WP\CriticalCSS\Cache\Manager                                                              $cache_manager
	 * @param \WP\CriticalCSS\Request                                                                    $request
	 * @param \WP\CriticalCSS\Integration\Manager                                                 $integration_manager
	 * @param \WP\CriticalCSS\API\Background\Process|\WP\CriticalCSS\Web\Check\Background\Process $api_queue
	 * @param \WP\CriticalCSS\Frontend                                                                   $frontend
	 * @param \WP\CriticalCSS\Web\Check\Background\Process                                               $web_check_queue
	 * @param \WP\CriticalCSS\Installer                                                                  $installer
	 * @param \WP\CriticalCSS\Log                                                                        $log
	 * @param \WP\CriticalCSS\Template\Log                                                               $template_log
	 */
	public function __construct(
		SettingsManager $settings_manager,
		UI $admin_ui,
		DataManager $data_manager,
		CacheManager $cache_manager,
		CriticalCSS\Request $request,
		IntegrationManager $integration_manager,
		BackgroundProcess $api_queue,
		Frontend $frontend,
		WebCheckProcess $web_check_queue,
		Installer $installer,
		Log $log,
		TemplateLog $template_log
	) {
		$this->settings_manager    = $settings_manager;
		$this->admin_ui            = $admin_ui;
		$this->data_manager        = $data_manager;
		$this->cache_manager       = $cache_manager;
		$this->request             = $request;
		$this->integration_manager = $integration_manager;
		$this->api_queue           = $api_queue;
		$this->web_check_queue     = $web_check_queue;
		$this->frontend            = $frontend;
		$this->installer           = $installer;
		$this->log                 = $log;
		$this->template_log        = $template_log;
		parent::__construct();
	}

	/**
	 * @return \WP\CriticalCSS\Installer
	 */
	public function get_installer() {
		return $this->installer;
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
	public function activate() {
		$this->installer->activate();
	}

	/**
	 *
	 */

	/**
	 *
	 */
	public function deactivate() {
		$this->installer->deactivate();
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
		if ( class_exists( 'http\Url' ) ) {
			/**
			 * @noinspection PhpUndefinedClassInspection
			 */
			$url = new \http\Url( $url_parts );
			$url = $url->toString();
		} else {
			$url = http_build_url( $url_parts );
		}
		if ( ! empty( $object['blog_id'] ) ) {
			restore_current_blog( $object['blog_id'] );
		}

		return apply_filters( 'wp_criticalcss_get_permalink', $url );
	}

	/**
	 * @return \WP\CriticalCSS\API\Background\Process
	 */
	public
	function get_api_queue() {
		return $this->api_queue;
	}

	/**
	 * @return \WP\CriticalCSS\Request
	 */
	public
	function get_request() {
		return $this->request;
	}

	/**
	 * @return \WP\CriticalCSS\Integration\Manager
	 */
	public
	function get_integration_manager() {
		return $this->integration_manager;
	}

	/**
	 * @return void
	 */
	public function uninstall() {
		// noop
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
		return static::LANG_DOMAIN;
	}

	/**
	 * @return string
	 */
	public function get_transient_prefix() {
		return static::TRANSIENT_PREFIX;
	}

	/**
	 * @return \WP\CriticalCSS\Log
	 */
	public function get_log() {
		return $this->log;
	}

	/**
	 * @return \WP\CriticalCSS\Template\Log
	 */
	public function get_template_log() {
		return $this->template_log;
	}

	protected function setup_components() {
		$components = $this->get_components();
		$this->set_component_parents( $components );
		foreach ( $components as $component ) {
			$component->init();
		}
	}

}
