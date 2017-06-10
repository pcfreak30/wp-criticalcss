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
	protected $settings = array();
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


	public function __construct(
		CriticalCSS\Admin\UI $admin_ui,
		CriticalCSS\Data\Manager $data_manager,
		CriticalCSS\Cache\Manager $cache_manager,
		CriticalCSS\Request $request,
		CriticalCSS\Integration\Manager $integration_manager,
		CriticalCSS\API\Background\Process $api_queue
	) {
		$this->settings            = $this->get_settings();
		$this->admin_ui            = $admin_ui;
		$this->data_manager        = $data_manager;
		$this->cache_manager       = $cache_manager;
		$this->request             = $request;
		$this->integration_manager = $integration_manager;
		$this->api_queue           = $api_queue;
		$this->set_parent();
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

	protected function set_parent() {
		foreach ( $this as $property ) {
			if ( $property instanceof ComponentAbstract ) {
				$property->set_app( $this );
			}
		}
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
	public function wp_head() {
		if ( get_query_var( 'nocache' ) ) :
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
		$this->integration_manager->enable_integrations();
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
			remove_action(
				'update_option_criticalcss', array(
					$this,
					'after_options_updated',
				)
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
					array(
						'fields'       => 'ids',
						'site__not_in' => array( 1 ),
					)
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
				array(
					'web_check_interval' => DAY_IN_SECONDS,
					'template_cache'     => 'off',
				), $this->get_settings(), array(
					'version' => self::VERSION,
				)
			)
		);

		$this->init();
		$this->request->add_rewrite_rules();

		$this->web_check_queue->create_table();
		$this->api_queue->create_table();

		flush_rewrite_rules();
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
		$this->init_components();

		if ( ! is_admin() ) {
			add_action(
				'wp_print_styles', array(
				$this,
				'print_styles',
			), 7 );
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		add_action(
			'after_switch_theme', array(
				$this,
				'reset_web_check_transients',
			)
		);
		add_action(
			'upgrader_process_complete', array(
				$this,
				'reset_web_check_transients',
			)
		);
		if ( ! ( ! empty( $this->settings['template_cache'] ) && 'on' == $this->settings['template_cache'] ) ) {
			add_action(
				'post_updated', array(
					$this,
					'reset_web_check_post_transient',
				)
			);
			add_action(
				'edited_term', array(
					$this,
					'reset_web_check_term_transient',
				)
			);
		}
		if ( is_admin() ) {
			add_action(
				'wp_loaded', array(
					$this,
					'wp_action',
				)
			);
		} else {
			add_action(
				'wp', array(
					$this,
					'wp_action',
				)
			);
			add_action(
				'wp_head', array(
					$this,
					'wp_head',
				)
			);
		}
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
				<style type="text/css" id="criticalcss" data-no-minify="1"><?php echo $cache ?></style>
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
		$parts = array(
			'object_id',
			'type',
			'url',
		);
		if ( 'on' == $this->settings['template_cache'] ) {
			$template = $this->template;
			$parts    = array( 'template' );
		}
		$type = compact( $parts );

		return md5( serialize( $type ) );
	}

	/**
	 * @param array $path
	 */

	/**
	 *
	 */
	public function reset_web_check_transients() {
		$this->cache_manager->delete_cache_branch();
	}

	/**
	 * @param $post
	 */
	public function reset_web_check_post_transient( $post ) {
		$post = get_post( $post );
		$hash = $this->get_item_hash(
			array(
				'object_id' => $post->ID,
				'type'      => 'post',
			)
		);
		$this->cache_manager->delete_cache_branch( array( $hash ) );
	}

	/**
	 * @param $term
	 *
	 * @internal param \WP_Term $post
	 */
	public function reset_web_check_term_transient( $term ) {
		$term = get_term( $term );
		$hash = $this->get_item_hash(
			array(
				'object_id' => $term->term_id,
				'type'      => 'term',
			)
		);
		$this->cache_manager->delete_cache_branch( array( $hash ) );
	}

	/**
	 *
	 */

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
			$hash = $this->get_item_hash(
				array(
					'object_id' => $post_id,
					'type'      => 'post',
				)
			);
		} else {
			$hash = $this->get_item_hash(
				array(
					'type' => 'url',
					'url'  => site_url(),
				)
			);
		}
		$this->cache_manager->delete_cache_branch( array( $hash ) );
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
}
