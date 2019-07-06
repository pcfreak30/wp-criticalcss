<?php

namespace WP\CriticalCSS;

use ComposePress\Core\Abstracts\Component;

/**
 * Class Request
 *
 * @property string $template
 */
class Request extends Component {
	/**
	 * @var
	 */
	protected $nocache;
	/**
	 * @var
	 */
	protected $template;

	protected $original_is_single;

	protected $original_is_page;

	/**
	 * WP_CriticalCSS_Request constructor.
	 */
	public function init() {
		add_action( 'init', [
			$this,
			'add_rewrite_rules',
		] );
		add_action( 'init', [
			$this,
			'check_log_cron',
		] );
		add_filter( 'rewrite_rules_array', [
			$this,
			'fix_rewrites',
		], 11 );
		add_action( 'request', [
			$this,
			'update_request',
		] );
		if ( 'on' === $this->plugin->settings_manager->get_setting( 'template_cache' ) ) {
			add_action( 'template_include', [
				$this,
				'template_include',
			], PHP_INT_MAX );
		}

		/*
		 * Prevent a 404 on homepage if a static page is set.
		 * Will store query_var outside \WP_Query temporarily so we don't need to do any extra routing logic and will appear as if it was not set.
		 */
		add_action( 'query_vars', [
			$this,
			'query_vars',
		] );
		add_action( 'parse_request', [
			$this,
			'parse_request',
		] );
		// Don't fix url or try to guess url if we are using nocache on the homepage
		add_filter( 'redirect_canonical', [
			$this,
			'redirect_canonical',
		], 0 );
		add_filter( 'redirect_canonical', [
			$this,
			'redirect_canonical_fix_vars',
		], PHP_INT_MAX );

	}

	/**
	 *
	 */
	public function add_rewrite_rules() {
		add_rewrite_endpoint( 'nocache', E_ALL );
		add_rewrite_rule( 'nocache/?$', 'index.php?nocache=1', 'top' );
		$taxonomies = get_taxonomies( [
			'public'   => true,
			'_builtin' => false,
		], 'objects' );
		$post_types = get_post_types( [
			'public'   => true,
			'_builtin' => false,
		], 'objects' );
		foreach ( $taxonomies as $tax_id => $tax ) {
			if ( ! empty( $tax->rewrite ) ) {
				add_rewrite_rule( $tax->rewrite['slug'] . '/(.+?)/nocache/?$', 'index.php?' . $tax_id . '=$matches[1]&nocache', 'top' );
			}
		}
		foreach ( $post_types as $post_type_id => $post_type ) {
			if ( ! empty( $post_type->rewrite ) ) {
				add_rewrite_rule( $post_type->rewrite['slug'] . '/nocache/?$', 'index.php?post_type=' . $post_type_id . '&nocache', 'top' );
			}
		}
	}

	public function check_log_cron() {
		$scheduled   = wp_next_scheduled( 'wp_criticalcss_purge_log' );
		$integration = apply_filters( 'wp_criticalcss_cache_integration', false );
		if ( ! $scheduled && ! $integration ) {
			wp_schedule_single_event( time() + (int) $this->plugin->settings_manager->get_setting( 'web_check_interval' ), 'wp_criticalcss_purge_log' );
		}
		if ( $scheduled && $integration ) {
			wp_unschedule_event( $scheduled, 'wp_criticalcss_purge_log' );
		}
	}

	/**
	 *
	 */
	public function fix_rewrites( $rules ) {
		$nocache_rules = [
			// Fix page archives
			'(.?.+?)/page(?:/([0-9]+))?/nocache/?' => 'index.php?pagename=$matches[1]&paged=$matches[2]&nocache',
		];
		// Fix all custom taxonomies
		$tokens = get_taxonomies( [
			'public'   => true,
			'_builtin' => false,
		] );
		foreach ( $rules as $match => $query ) {
			if ( false !== strpos( $match, 'nocache' ) && preg_match( '/' . implode( '|', $tokens ) . '/', $query ) ) {
				$nocache_rules[ $match ] = $query;
				unset( $rules[ $match ] );
			}
		}
		$rules = array_merge( $nocache_rules, $rules );

		return $rules;
	}

	/**
	 * @param $redirect_url
	 *
	 * @return bool
	 */
	public function redirect_canonical( $redirect_url ) {
		global $wp_query;
		if ( ! array_diff( array_keys( $wp_query->query ), [ 'nocache' ] ) || get_query_var( 'nocache' ) ) {
			$redirect_url             = false;
			$wp_query->is_404         = false;
			$this->original_is_single = $wp_query->is_single;
			$this->original_is_page   = $wp_query->is_page;
			$wp_query->is_single      = false;
			$wp_query->is_page        = false;
		}

		return $redirect_url;
	}

	public function redirect_canonical_fix_vars( $redirect_url ) {
		global $wp_query;
		if ( null !== $this->original_is_single ) {
			$wp_query->is_single = $this->original_is_single;
		}
		if ( null !== $this->original_is_page ) {
			$wp_query->is_page = $this->original_is_page;
		}

		return $redirect_url;
	}

	/**
	 * @SuppressWarnings(PHPMD.ShortVariable)
	 * @param \WP $wp
	 */
	public function parse_request( \WP $wp ) {
		if ( isset( $wp->query_vars['nocache'] ) ) {
			$this->nocache = $wp->query_vars['nocache'];
			unset( $wp->query_vars['nocache'] );
		}
	}

	/**
	 * @param $vars
	 *
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = 'nocache';

		return $vars;
	}

	/**
	 * @param $vars
	 *
	 * @return mixed
	 */
	public function update_request( $vars ) {
		if ( isset( $vars['nocache'] ) ) {
			$vars['nocache'] = true;
		}

		return $vars;
	}

	/**
	 * @return bool
	 */
	public function is_no_cache() {
		return $this->nocache;
	}

	/**
	 * @param $template
	 *
	 * @return mixed
	 */
	public function template_include( $template ) {
		$this->template = str_replace( trailingslashit( WP_CONTENT_DIR ), '', $template );

		return $template;
	}

	/**
	 * @return string
	 */
	public function get_template() {
		return $this->template;
	}


	/**
	 * @SuppressWarnings(PHPMD.ShortVariable)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @return array
	 */
	public function get_current_page_type() {
		global $wp;
		$object_id = 0;
		if ( is_home() ) {
			$page_for_posts = get_option( 'page_for_posts' );
			if ( ! empty( $page_for_posts ) ) {
				$object_id = $page_for_posts;
				$type      = 'post';
			}
		} elseif ( is_front_page() ) {
			$page_on_front = get_option( 'page_on_front' );
			if ( ! empty( $page_on_front ) ) {
				$object_id = $page_on_front;
				$type      = 'post';
			}
		} elseif ( is_singular() ) {
			$object_id = get_the_ID();
			$type      = 'post';
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$object_id = get_queried_object()->term_id;
			$type      = 'term';
		} elseif ( is_author() ) {
			$object_id = get_the_author_meta( 'ID' );
			$type      = 'author';

		}

		$object_id = absint( $object_id );

		if ( ! isset( $type ) ) {
			$this->plugin->integration_manager->disable_integrations();
			$url  = site_url( $wp->request );
			$vars = [];
			foreach ( $this->wp->public_query_vars as $var ) {
				if ( isset( $_GET[ $var ] ) ) {
					$vars[ $var ] = $_GET[ $var ];
				}
			}
			$url = add_query_arg( $vars, $url );
			$this->plugin->integration_manager->enable_integrations();
			$type = 'url';
			unset( $object_id );
		}

		if ( 'on' === $this->plugin->settings_manager->get_setting( 'template_cache' ) ) {
			$template = $this->template;
		}

		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
		}

		$compact = [];

		foreach ( [ 'object_id', 'type', 'url', 'template', 'blog_id' ] as $var ) {
			if ( isset( $$var ) ) {
				$compact[ $var ] = $$var;
			}
		}

		return $compact;
	}

}
