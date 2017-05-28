<?php

/**
 * Class CriticalCSS
 */
class WP_CriticalCSS {
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
	 * @var
	 */
	protected static $instance;
	/**
	 * @var array
	 */
	protected $integrations = array(
		'WP_CriticalCSS_Integration_Rocket_Async_CSS',
		'WP_CriticalCSS_Integration_Root_Relative_URLS',
		'WP_CriticalCSS_Integration_WP_Rocket',
		'WP_CriticalCSS_Integration_WPEngine',
	);
	/**
	 * @var bool
	 */
	protected $nocache = false;
	/**
	 * @var WP_CriticalCSS_Web_Check_Background_Process
	 */
	protected $web_check_queue;
	/**
	 * @var WP_CriticalCSS_API_Background_Process
	 */
	protected $api_queue;
	/**
	 * @var array
	 */
	protected $settings = array();
	/**
	 * @var string
	 */
	protected $template;

	/**
	 * @var \WP_CriticalCSS_Admin_UI
	 */
	protected $admin_ui;

	/**
	 * @var \WP_CriticalCSS_Data_Manager
	 */
	protected $data_manager;

	/**
	 * @var \WP_CriticalCSS_Cache_Manager
	 */
	protected $cache_manager;

	/**
	 * @var \WP_CriticalCSS_Request
	 */
	protected $request;

	protected $integrations_setup = false;

	protected $components_setup = false;

	/**
	 * @return \WP_CriticalCSS
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return \WP_CriticalCSS_Data_Manager
	 */
	public function get_data_manager() {
		return $this->data_manager;
	}

	/**
	 * @return \WP_CriticalCSS_Cache_Manager
	 */
	public function get_cache_manager() {
		return $this->cache_manager;
	}

	/**
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}


	/**
	 *
	 */
	public function wp_head() {
		if ( get_query_var( 'nocache' ) ):
			?>
            <meta name="robots" content="noindex, nofollow"/>
			<?php
		endif;
	}


	/**
	 *
	 */
	public function wp_action() {
		set_query_var( 'nocache', $this->request->is_no_cache() );
		$this->enable_integrations();
	}

	/**
	 *
	 */
	public function enable_integrations() {
		do_action( 'wp_criticalcss_enable_integrations' );

	}

	/**
	 *
	 */
	public function activate() {
		global $wpdb;
		$settings    = $this->get_settings();
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
			remove_action( 'update_option_criticalcss', array( $this, 'after_options_updated' ) );
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
			foreach ( get_sites( array( 'fields' => 'ids', 'site__not_in' => array( 1 ) ) ) as $blog_id ) {
				switch_to_blog( $blog_id );
				$wpdb->query( "DROP TABLE {$wpdb->prefix}_wp_criticalcss_web_check_queue IF EXISTS" );
				$wpdb->query( "DROP TABLE {$wpdb->prefix}_wp_criticalcss_api_queue IF EXISTS" );
				restore_current_blog();
			}
		}

		$this->update_settings( array_merge( array(
			'web_check_interval' => DAY_IN_SECONDS,
			'template_cache'     => 'off',
		), $this->get_settings(), array( 'version' => self::VERSION ) ) );

		$this->init();
		$this->request->add_rewrite_rules();

		$this->web_check_queue->create_table();
		$this->api_queue->create_table();

		flush_rewrite_rules();
	}

	/**
	 * @return array
	 */
	public function get_settings() {
		$settings = array();
		if ( is_multisite() ) {
			$settings = get_site_option( self::OPTIONNAME, array() );
			if ( empty( $settings ) ) {
				$settings = get_option( self::OPTIONNAME, array() );
			}
		} else {
			$settings = get_option( self::OPTIONNAME, array() );
		}

		return $settings;
	}

	/**
	 * @param array $settings
	 */
	public function set_settings( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	public function update_settings( array $settings ) {
		$this->set_settings( $settings );
		if ( is_multisite() ) {
			return update_site_option( self::OPTIONNAME, $settings );
		} else {
			return update_option( self::OPTIONNAME, $settings );
		}
	}

	/**
	 *
	 */
	public function init() {
		$this->settings = $this->get_settings();
		if ( ! $this->components_setup ) {
			$this->setup_components();
		}

		if ( ! is_admin() ) {
			add_action( 'wp_print_styles', array( $this, 'print_styles' ), 7 );
		}

		$this->setup_integrations();

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		add_action( 'after_switch_theme', array( $this, 'reset_web_check_transients' ) );
		add_action( 'upgrader_process_complete', array( $this, 'reset_web_check_transients' ) );
		if ( ! ( ! empty( $this->settings['template_cache'] ) && 'on' == $this->settings['template_cache'] ) ) {
			add_action( 'post_updated', array( $this, 'reset_web_check_post_transient' ) );
			add_action( 'edited_term', array( $this, 'reset_web_check_term_transient' ) );
		}
		if ( is_admin() ) {
			add_action( 'wp_loaded', array( $this, 'wp_action' ) );
		} else {
			add_action( 'wp', array( $this, 'wp_action' ) );
			add_action( 'wp_head', array( $this, 'wp_head' ) );
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
	 * @return false|mixed|string|\WP_Error
	 */
	public function get_permalink( array $object ) {
		$this->disable_integrations();
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
		$this->enable_integrations();

		if ( $url instanceof WP_Error ) {
			return false;
		}

		$url_parts         = parse_url( $url );
		$url_parts['path'] = trailingslashit( $url_parts['path'] ) . 'nocache/';
		if ( class_exists( 'http\Url' ) ) {
			/** @noinspection PhpUndefinedClassInspection */
			$url = new \http\Url( $url_parts );
			$url = $url->toString();
		} else {
			if ( ! function_exists( 'http_build_url' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'http_build_url.php';
			}
			$url = http_build_url( $url_parts );
		}

		return $url;
	}

	/**
	 *
	 */
	public function disable_integrations() {
		do_action( 'wp_criticalcss_disable_integrations' );
	}

	/**
	 * @param $type
	 * @param $object_id
	 * @param $url
	 */
	public function purge_page_cache( $type = null, $object_id = null, $url = null ) {
		$url = preg_replace( '#nocache/$#', '', $url );

		do_action( 'wp_criticalcss_purge_cache', $type, $object_id, $url );
	}

	/**
	 *
	 */
	public function print_styles() {
		if ( ! get_query_var( 'nocache' ) && ! is_404() ) {
			$cache        = $this->data_manager->get_cache();
			$style_handle = null;

			$cache = apply_filters( 'wp_criticalcss_print_styles_cache', $cache );

			do_action( 'wp_criticalcss_before_print_styles', $cache );

			if ( ! empty( $cache ) ) {
				?>
                <style type="text/css" id="criticalcss" data-no-minify="1"><?= $cache ?></style>
				<?php
			}
			$type  = $this->request->get_current_page_type();
			$hash  = $this->get_item_hash( $type );
			$check = $this->cache_manager->get_cache_fragment( array( $hash ) );
			if ( 'on' == $this->settings['template_cache'] && ! empty( $type['template'] ) ) {
				if ( empty( $cache ) && ! $this->api_queue->get_item_exists( $type ) ) {
					$this->api_queue->push_to_queue( $type )->save();
				}
			} else {
				if ( empty( $check ) && ! $this->web_check_queue->get_item_exists( $type ) ) {
					$this->web_check_queue->push_to_queue( $type )->save();
					$this->cache_manager->update_cache_fragment( array( $hash ), true );
				}
			}

			do_action( 'wp_criticalcss_after_print_styles' );
		}
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function get_item_hash( $item ) {
		extract( $item );
		$parts = array( 'object_id', 'type', 'url' );
		if ( 'on' == $this->settings['template_cache'] ) {
			$template = $this->template;
			$parts    = array( 'template' );
		}
		$type = compact( $parts );

		return md5( serialize( $type ) );
	}


	/**
	 *
	 */
	public function reset_web_check_transients() {
		$this->cache_manager->delete_cache_branch();
	}

	/**
	 * @param array $path
	 */


	/**
	 * @param $post
	 */
	public function reset_web_check_post_transient( $post ) {
		$post = get_post( $post );
		$hash = $this->get_item_hash( array( 'object_id' => $post->ID, 'type' => 'post' ) );
		$this->cache_manager->delete_cache_branch( array( $hash ) );
	}

	/**
	 * @param $term
	 *
	 * @internal param \WP_Term $post
	 */
	public function reset_web_check_term_transient( $term ) {
		$term = get_term( $term );
		$hash = $this->get_item_hash( array( 'object_id' => $term->term_id, 'type' => 'term' ) );
		$this->cache_manager->delete_cache_branch( array( $hash ) );
	}

	/**
	 * @internal param \WP_Term $post
	 */
	public function reset_web_check_home_transient() {
		$page_for_posts = get_option( 'page_for_posts' );
		if ( ! empty( $page_for_posts ) ) {
			$post_id = $page_for_posts;
		}
		if ( empty( $post_id ) || ( ! empty( $post_id ) && get_permalink( $post_id ) != site_url() ) ) {
			$page_on_front = get_option( 'page_on_front' );
			if ( ! empty( $page_on_front ) ) {
				$post_id = $page_on_front;
			} else {
				$post_id = false;
			}
		}
		if ( ! empty( $post_id ) && get_permalink( $post_id ) == site_url() ) {
			$hash = $this->get_item_hash( array( 'object_id' => $post_id, 'type' => 'post' ) );
		} else {
			$hash = $this->get_item_hash( array( 'type' => 'url', 'url' => site_url() ) );
		}
		$this->cache_manager->delete_cache_branch( array( $hash ) );
	}

	/**
	 *
	 */


	/**
	 * @return \WP_CriticalCSS_API_Background_Process
	 */
	public function get_api_queue() {
		return $this->api_queue;
	}

	/**
	 * @return \WP_CriticalCSS_Request
	 */
	public function get_request() {
		return $this->request;
	}

	public function setup_integrations( $force = false ) {

		if ( ! $this->integrations_setup || $force ) {
			$integrations = array();
			foreach ( $this->integrations as $integration ) {
				if ( $this->integrations_setup ) {
					$integration = get_class( $integration );
				}
				$integrations[ $integration ] = new $integration();
			}
			$this->integrations       = $integrations;
			$this->integrations_setup = true;
		}

	}

	public function setup_components() {
		$this->admin_ui         = new WP_CriticalCSS_Admin_UI();
		$this->web_check_queue  = new WP_CriticalCSS_Web_Check_Background_Process();
		$this->api_queue        = new WP_CriticalCSS_API_Background_Process();
		$this->data_manager     = new WP_CriticalCSS_Data_Manager();
		$this->cache_manager    = new WP_CriticalCSS_Cache_Manager();
		$this->request          = new WP_CriticalCSS_Request();
		$this->components_setup = true;
	}
}