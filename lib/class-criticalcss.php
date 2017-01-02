<?php

/**
 * Class CriticalCSS
 */
class CriticalCSS {
	/**
	 *
	 */
	const VERSION = '0.1.3';

	/**
	 *
	 */
	const LANG_DOMAIN = 'criticalcss';

	/**
	 *
	 */
	const OPTIONNAME = 'criticalcss';

	/**
	 * @var bool
	 */
	public static $nocache = false;
	/**
	 * @var \WeDevs_Settings_API
	 */
	protected static $_settings_ui;
	/**
	 * @var
	 */
	protected static $_background_queue;
	/**
	 * @var
	 */
	protected static $_queue_table;
	/**
	 * @var array
	 */
	private static $_settings = array();

	/**
	 *
	 */
	public static function init() {
		self::$_settings = self::get_settings();
		if ( empty( self::$_settings_ui ) ) {
			self::$_settings_ui = new CriticalCSS_Settings_API();
		}
		if ( empty( self::$_background_queue ) ) {
			self::$_background_queue = new CriticalCSS_Background_Process();
		}
		if ( ! is_admin() ) {
			add_action( 'wp_print_styles', array( __CLASS__, 'print_styles' ), 7 );
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
			add_action( 'network_admin_menu', array( __CLASS__, 'settings_init' ) );
		} else {
			add_action( 'admin_menu', array( __CLASS__, 'settings_init' ) );
		}
		add_action( 'pre_update_option_criticalcss', array( __CLASS__, 'sync_options' ), 10, 2 );
		add_action( 'pre_update_site_option_criticalcss', array( __CLASS__, 'sync_options' ), 10, 2 );

		add_action( 'after_switch_theme', array( __CLASS__, 'prune_transients' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'prune_transients' ) );
		if ( 'off' == self::$_settings['disable_autopurge'] ) {
			add_action( 'post_updated', array( __CLASS__, 'prune_post_transients' ) );
			add_action( 'edited_term', array( __CLASS__, 'prune_term_transients' ) );
		}
		add_action( 'request', array( __CLASS__, 'update_request' ) );
		if ( is_admin() ) {

			add_action( 'wp_loaded', array( __CLASS__, 'wp_action' ) );
		} else {
			add_action( 'wp', array( __CLASS__, 'wp_action' ) );
		}
		add_action( 'init', array( __CLASS__, 'init_action' ) );
		/*
		 * Prevent a 404 on homepage if a static page is set.
		 * Will store query_var outside \WP_Query temporarily so we don't need to do any extra routing logic and will appear as if it was not set.
		 */
		add_action( 'parse_request', array( __CLASS__, 'parse_request' ) );
		// Don't fix url or try to guess url if we are using nocache on the homepage
		add_filter( 'redirect_canonical', array( __CLASS__, 'redirect_canonical' ) );
		add_action( 'criticalcss_purge', array( __CLASS__, 'prune_transients' ) );
		add_action( 'update_option_criticalcss', array( __CLASS__, 'after_options_updated' ), 10, 2 );
	}

	/**
	 * @return array
	 */
	public static function get_settings() {
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
	 * @param $old
	 * @param $new
	 */
	public static function after_options_updated( $old, $new ) {
		if ( 'on' == $new['disable_autopurge'] ) {
			if ( $timestamp = wp_next_scheduled( 'criticalcss_purge' ) ) {
				wp_unschedule_event( $timestamp, 'criticalcss_purge' );
			}
		}
	}

	/**
	 * @param $redirect_url
	 *
	 * @return bool
	 */
	public static function redirect_canonical( $redirect_url ) {
		global $wp_query;
		if ( ! array_diff( array_keys( $wp_query->query ), array( 'nocache' ) ) ) {
			$redirect_url = false;
		}

		return $redirect_url;
	}

	/**
	 * @param \WP $wp
	 */
	public static function parse_request( WP &$wp ) {
		if ( isset( $wp->query_vars['nocache'] ) ) {
			self::$nocache = $wp->query_vars['nocache'];
			unset( $wp->query_vars['nocache'] );
		}
	}
	/**
	 * @param $vars
	 *
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'nocache';

		return $vars;
	}

	/**
	 * @param $vars
	 *
	 * @return mixed
	 */
	public static function update_request( $vars ) {
		if ( isset( $vars['nocache'] ) ) {
			$vars['nocache'] = true;
		}

		return $vars;
	}

	/**
	 *
	 */
	public static function wp_action() {
		set_query_var( 'nocache', self::$nocache );
		if ( self::has_external_integration() ) {
			self::external_integration();
		} else {
			add_action( 'admin_bar_menu', array( __CLASS__, 'admin_menu' ) );
			add_action( 'admin_post_purge_criticalcss_cache', array( __CLASS__, 'admin_prune_transients' ) );
		}
	}

	/**
	 * @return bool
	 */
	public static function has_external_integration() {
		// // Compatibility with WP Rocket ASYNC CSS preloader integration
		if ( class_exists( 'Rocket_Async_Css_The_Preloader' ) ) {
			return true;
		}
		// WP-Rocket integration
		if ( function_exists( 'get_rocket_option' ) ) {
			return true;
		}

		return false;
	}

	/**
	 *
	 */
	public static function external_integration() {
		if ( get_query_var( 'nocache' ) ) {
			// Compatibility with WP Rocket ASYNC CSS preloader integration
			if ( class_exists( 'Rocket_Async_Css_The_Preloader' ) ) {
				remove_action( 'wp_enqueue_scripts', array(
					'Rocket_Async_Css_The_Preloader',
					'add_window_resize_js',
				) );
				remove_action( 'rocket_buffer', array( 'Rocket_Async_Css_The_Preloader', 'inject_div' ) );
			}
			define( 'DONOTCACHEPAGE', true );
		}
		// Compatibility with WP Rocket
		if ( function_exists( 'get_rocket_option' ) ) {
			add_action( 'after_rocket_clean_domain', array( __CLASS__, 'prune_transients' ) );
			if ( 'off' == self::$_settings['disable_autopurge'] ) {
				add_action( 'after_rocket_clean_post', array( __CLASS__, 'prune_post_transients' ) );
				add_action( 'after_rocket_clean_term', array( __CLASS__, 'prune_term_transients' ) );
				add_action( 'after_rocket_clean_home', array( __CLASS__, 'prune_home_transients' ) );
			}
		}
	}

	/**
	 *
	 */
	public static function activate() {
		self::update_settings( array_merge( array(
			'expire'            => DAY_IN_SECONDS,
			'disable_autopurge' => 'off',
		), self::get_settings() ) );
		self::init_action();
		flush_rewrite_rules();
	}

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	public static function update_settings( array $settings ) {
		if ( is_multisite() ) {
			return update_site_option( self::OPTIONNAME, $settings );
		} else {
			return update_option( self::OPTIONNAME, $settings );
		}
	}

	/**
	 *
	 */
	public static function init_action() {
		add_rewrite_endpoint( 'nocache', E_ALL );
		add_rewrite_rule( 'nocache/?$', 'index.php?nocache=1', 'top' );
		if ( 'off' == self::$_settings['disable_autopurge'] && ! wp_next_scheduled( 'criticalcss_purge' ) ) {
			wp_schedule_single_event( time() + self::get_expire_period(), 'criticalcss_purge' );
		}
	}

	/**
	 * @return int
	 */
	public static function get_expire_period() {
// WP-Rocket integration
		if ( function_exists( 'get_rocket_purge_cron_interval' ) ) {
			return get_rocket_purge_cron_interval();
		}
		$settings = self::get_settings();

		return absint( $settings['expire'] );
	}

	/**
	 *
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * @param array $object
	 *
	 * @return false|mixed|string|\WP_Error
	 */
	public static function get_permalink( array $object ) {
		self::disable_relative_plugin_filters();
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
		self::enable_relative_plugin_filters();

		return trailingslashit( $url ) . 'nocache/';
	}

	/**
	 *
	 */
	protected static function disable_relative_plugin_filters() {
		if ( class_exists( 'MP_WP_Root_Relative_URLS' ) ) {
			remove_filter( 'post_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
			remove_filter( 'page_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
			remove_filter( 'attachment_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
			remove_filter( 'post_type_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
			remove_filter( 'get_the_author_url', array( 'MP_WP_Root_Relative_URLS', 'dynamic_rss_absolute_url' ), 1 );
		}
	}

	/**
	 *
	 */
	protected static function enable_relative_plugin_filters() {
		if ( class_exists( 'MP_WP_Root_Relative_URLS' ) ) {
			add_filter( 'post_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
			add_filter( 'page_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
			add_filter( 'attachment_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
			add_filter( 'post_type_link', array( 'MP_WP_Root_Relative_URLS', 'proper_root_relative_url' ), 1 );
			add_filter( 'get_the_author_url', array( 'MP_WP_Root_Relative_URLS', 'dynamic_rss_absolute_url' ), 1, 2 );
		}
	}

	/**
	 * @param $type
	 * @param $object_id
	 * @param $url
	 */
	public static function purge_cache( $type, $object_id, $url ) {
		global $wpe_varnish_servers;
		$url = preg_replace( '#nocache/$#', '', $url );
// WP Engine Support
		if ( class_exists( 'WPECommon' ) ) {
			if ( 'post' == $type ) {
				WpeCommon::purge_varnish_cache( $object_id );
			} else {
				$blog_url       = home_url();
				$blog_url_parts = @parse_url( $blog_url );
				$blog_domain    = $blog_url_parts['host'];
				$purge_domains  = array( $blog_domain );
				$object_parts   = parse_url( $url );
				$object_uri     = rtrim( $object_parts   ['path'], '/' ) . "(.*)";
				$paths          = array( $object_uri );
				if ( ! empty( $object_parts['query'] ) ) {
					$object_uri .= "?" . $object_parts['query'];
				}
				$purge_domains = array_unique( array_merge( $purge_domains, WpeCommon::get_blog_domains() ) );
				if ( defined( 'WPE_CLUSTER_TYPE' ) && WPE_CLUSTER_TYPE == "pod" ) {
					$wpe_varnish_servers = array( "localhost" );
				} // Ordinarily, the $wpe_varnish_servers are set during apply. Just in case, let's figure out a fallback plan.
				else if ( ! isset( $wpe_varnish_servers ) ) {
					if ( ! defined( 'WPE_CLUSTER_ID' ) || ! WPE_CLUSTER_ID ) {
						$lbmaster = "lbmaster";
					} else if ( WPE_CLUSTER_ID >= 4 ) {
						$lbmaster = "localhost"; // so the current user sees the purge
					} else {
						$lbmaster = "lbmaster-" . WPE_CLUSTER_ID;
					}
					$wpe_varnish_servers = array( $lbmaster );
				}
				$path_regex          = '(' . join( '|', $paths ) . ')';
				$hostname            = $purge_domains[0];
				$purge_domains       = array_map( 'preg_quote', $purge_domains );
				$purge_domain_chunks = array_chunk( $purge_domains, 100 );
				foreach ( $purge_domain_chunks as $chunk ) {
					$purge_domain_regex = '^(' . join( '|', $chunk ) . ')$';
// Tell Varnish.
					foreach ( $wpe_varnish_servers as $varnish ) {
						$headers = array( 'X-Purge-Path' => $path_regex, 'X-Purge-Host' => $purge_domain_regex );
						WpeCommon::http_request_async( 'PURGE', $varnish, 9002, $hostname, '/', $headers, 0 );
					}
				}
			}
			sleep( 1 );
		}
// WP-Rocket Support
		if ( function_exists( 'rocket_clean_files' ) ) {
			if ( 'post' == $type ) {
				rocket_clean_post( $object_id );
			}
			if ( 'term' == $type ) {
				rocket_clean_term( $object_id, get_term( $object_id )->taxonomy );
			}
			if ( 'url' == $type ) {
				rocket_clean_files( $url );
			}
		}
	}

	/**
	 *
	 */
	public static function print_styles() {
		if ( ! get_query_var( 'nocache' ) ) {
			$cache        = get_transient( self::get_transient_name() );
			$style_handle = null;
			if ( ! empty( $cache ) ) {
				// Enable CDN in CSS for WP-Rocket
				if ( function_exists( 'rocket_cdn_css_properties' ) ) {
					$cache = rocket_cdn_css_properties( $cache );
				}
				// Compatibility with WP Rocket ASYNC CSS preloader integration
				if ( class_exists( 'Rocket_Async_Css_The_Preloader' ) ) {
					remove_action( 'wp_enqueue_scripts', array(
						'Rocket_Async_Css_The_Preloader',
						'add_window_resize_js',
					) );
					remove_action( 'rocket_buffer', array( 'Rocket_Async_Css_The_Preloader', 'inject_div' ) );
				}
				?>
                <style type="text/css" id="criticalcss" data-no-minify="1"><?= $cache ?></style>
				<?php
			} else {
				$pending = get_transient( self::get_transient_name() . '_pending' );

				if ( empty( $pending ) ) {
					$type = self::get_current_page_type();
					self::$_background_queue->push_to_queue( $type )->save();
					set_transient( self::get_transient_name() . '_pending', true );
				}
			}
		}
	}

	/**
	 * @param array $type
	 *
	 * @return string
	 */
	public static function get_transient_name( $type = array() ) {
		if ( empty( $type ) ) {
			$type = self::get_current_page_type();
		}
		if ( 'url' == $type['type'] ) {
			$name = "criticalcss_url_" . md5( $type['url'] );
		} else {
			$name = "criticalcss_{$type['type']}_{$type['object_id']}";
		}

		return $name;
	}

	/**
	 * @return array
	 */
	protected static function get_current_page_type() {
		global $wp;
		global $query_string;
		if ( is_home() ) {
			$page_for_posts = get_option( 'page_for_posts' );
			if ( ! empty( $page_for_posts ) ) {
				$object_id = $page_for_posts;
				$type      = 'post';
			}
		} else if ( is_front_page() ) {
			$page_on_front = get_option( 'page_on_front' );
			if ( ! empty( $page_on_front ) ) {
				$object_id = $page_on_front;
				$type      = 'post';
			}
		} else if ( is_singular() ) {
			$object_id = get_the_ID();
			$type      = 'post';
		} else if ( is_tax() || is_category() || is_tag() ) {
			$object_id = get_queried_object()->term_id;
			$type      = 'term';
		} else if ( is_author() ) {
			$object_id = get_the_author_meta( 'ID' );
			$type      = 'author';

		}

		if ( ! isset( $type ) ) {
			self::disable_relative_plugin_filters();
			$query = array();
			wp_parse_str( $query_string, $query );
			$url = add_query_arg( $query, site_url( $wp->request ) );
			self::enable_relative_plugin_filters();

			$type = 'url';
		}

		return compact( 'object_id', 'type', 'url' );
	}

	/**
	 *
	 */
	public static function settings_init() {
		$hook = add_options_page( 'Critical CSS', 'Critical CSS', 'manage_options', 'criticalcss', array(
			__CLASS__,
			'settings_ui',
		) );
		add_action( "load-$hook", array( __CLASS__, 'screen_option' ) );
		self::$_settings_ui->add_section( array( 'id' => 'criticalcss', 'title' => 'Critical CSS Options' ) );
		self::$_settings_ui->add_field( 'criticalcss', array(
			'name'              => 'apikey',
			'label'             => 'API Key',
			'type'              => 'text',
			'sanitize_callback' => array( __CLASS__, 'validate_criticalcss_apikey' ),
			'desc'              => __( 'API Key for CriticalCSS.com. Please view yours at <a href="https://www.criticalcss.com/account/api-keys?aff=3">CriticalCSS.com</a>', self::LANG_DOMAIN ),
		) );
		self::$_settings_ui->add_field( 'criticalcss', array(
			'name'  => 'disable_autopurge',
			'label' => 'Disable Auto-Purge',
			'type'  => 'checkbox',
			'desc'  => __( 'Do not automatically purge the CSS cache. This setting is ignored for theme updates, plugin updates, and switching themes.', self::LANG_DOMAIN ),
		) );
		if ( ! self::has_external_integration() ) {
			self::$_settings_ui->add_field( 'criticalcss', array(
				'name'  => 'expire',
				'label' => 'Cache Time',
				'type'  => 'number',
				'desc'  => __( 'How long css should be cached for', self::LANG_DOMAIN ),
			) );
		}
		self::$_settings_ui->admin_init();
	}

	/**
	 *
	 */
	public static function settings_ui() {
		self::$_settings_ui->add_section( array( 'id' => 'criticalcss_queue', 'title' => 'Critical CSS Queue' ) );

		ob_start();

		?>
        <style type="text/css">
            .queue > th {
                display: none;
            }
        </style>
		<?php
		self::$_queue_table->prepare_items();
		self::$_queue_table->display();

		self::$_settings_ui->add_field( 'criticalcss_queue', array(
			'name'  => 'queue',
			'label' => null,
			'type'  => 'html',
			'desc'  => ob_get_clean(),
		) );

		self::$_settings_ui->admin_init();
		self::$_settings_ui->show_navigation();
		self::$_settings_ui->show_forms();
	}

	/**
	 * @param $options
	 *
	 * @return bool
	 */
	public static function validate_criticalcss_apikey( $options ) {
		$valid = true;
		if ( empty( $options['apikey'] ) ) {
			$valid = false;
			add_settings_error( 'apikey', 'invalid_apikey', __( 'API Key is empty', self::LANG_DOMAIN ) );
		}
		if ( ! $valid ) {
			return $valid;
		}
		$api = new CriticalCSS_API( $options['apikey'] );
		if ( ! $api->ping() ) {
			add_settings_error( 'apikey', 'invalid_apikey', 'CriticalCSS.com API Key is invalid' );
			$valid = false;
		}

		return ! $valid ? $valid : $options['apikey'];
	}

	/**
	 * @param $value
	 * @param $old_value
	 *
	 * @return array
	 */
	public static function sync_options( $value, $old_value ) {
		if ( ! is_array( $old_value ) ) {
			$old_value = array();
		}

		return array_merge( $old_value, $value );
	}

	/**
	 * @param $post WP_Post
	 */
	public static function prune_post_transients( $post ) {
		global $wpdb;
		$post = get_post( $post );
		$wpdb->get_results( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", "_transient_criticalcss_post_{$post->ID}%", "_transient_timeout_criticalcss_post_{$post->ID}%" ) );
		wp_cache_flush();
	}

	/**
	 * @param $term
	 *
	 * @internal param \WP_Term $post
	 */
	public static function prune_term_transients( $term ) {
		global $wpdb;
		$term = get_term( $term );
		$wpdb->get_results( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", "_transient_criticalcss_term_{$term->term_id}%", "_transient_timeout_criticalcss_term_{$term->term_id}%" ) );
		wp_cache_flush();
	}

	/**
	 * @internal param \WP_Term $post
	 */
	public static function prune_home_transients() {
		global $wpdb;
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
			$transient        = "_transient_criticalcss_post_{$post_id}";
			$transient_expire = "_transient_timeout_criticalcss_post_{$post_id}";
		} else {
			$transient        = "_transient_criticalcss_url_" . md5( site_url() );
			$transient_expire = "_transient_timeout_criticalcss_url_" . md5( site_url() );
		}
		$wpdb->get_results( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", "$transient%", "$transient_expire%" ) );
		wp_cache_flush();
	}

	/**
	 *
	 */
	public static function screen_option() {
		add_screen_option( 'per_page', array(
			'label'   => 'Queue Items',
			'default' => 20,
			'option'  => 'queue_items_per_page',
		) );
		self::$_queue_table = new CriticalCSS_Queue_List_Table( self::$_background_queue );
	}

	/**
	 * @param \WP_Admin_Bar $wp_admin_bar
	 */
	public static function admin_menu( WP_Admin_Bar $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$action = 'purge_criticalcss_cache';
		$wp_admin_bar->add_menu( array(
			'id'    => "$action",
			'title' => 'Purge CriticalCSS Cache',
			'href'  => wp_nonce_url( add_query_arg( array(
				'_wp_http_referer' => urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
				'action'           => $action,
			), admin_url( 'admin-post.php' ) ), $action ),
		) );
	}

	/**
	 *
	 */
	public static function admin_prune_transients() {
		if ( check_ajax_referer( 'purge_criticalcss_cache' ) ) {
			self::prune_transients();
			wp_redirect( wp_get_referer() );
		}
	}

	/**
	 *
	 */
	public static function prune_transients() {
		global $wpdb;
		$wpdb->get_results( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_criticalcss_%', '_transient_timeout_criticalcss_%' ) );
		wp_cache_flush();
	}
}