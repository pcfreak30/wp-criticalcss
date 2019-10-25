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
		//add_rewrite_endpoint( 'nocache', E_ALL );
		add_rewrite_rule( 'nocache/?$', 'index.php?nocache=1', 'top' );
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
		$post_rewrite   = $this->generate_rewrite_rules( $this->wp_rewrite->permalink_structure, EP_PERMALINK );
		$root_rewrite   = $this->generate_rewrite_rules( $this->wp_rewrite->root . '/', EP_ROOT );
		$search_rewrite = $this->generate_rewrite_rules( $this->wp_rewrite->get_search_permastruct(), EP_SEARCH );
		$page_rewrite   = $this->generate_rewrite_rules( $this->wp_rewrite->get_page_permastruct(), EP_PAGES, true, true, false, false );

		$cpt_rules = [];

		foreach ( $this->wp_rewrite->extra_permastructs as $permastructname => $struct ) {
			if ( is_array( $struct ) ) {
				if ( count( $struct ) == 2 ) {
					$cpt_rules[ $permastructname ] = $this->generate_rewrite_rules( $struct[0], $struct[1] );
				} else {
					$cpt_rules[ $permastructname ] = $this->generate_rewrite_rules( $struct['struct'], $struct['ep_mask'], $struct['paged'], $struct['feed'], $struct['forcomments'], $struct['walk_dirs'], $struct['endpoints'] );
				}
			} else {
				$cpt_rules[ $permastructname ] = $this->generate_rewrite_rules( $struct );
			}
		}
		if ( $this->wp_rewrite->use_verbose_page_rules ) {
			$rules = array_merge( call_user_func_array( 'array_merge', $cpt_rules ),$root_rewrite, $search_rewrite, $page_rewrite, $post_rewrite, $rules );
		} else {
			$rules = array_merge( call_user_func_array( 'array_merge', $cpt_rules ), $root_rewrite, $search_rewrite, $post_rewrite, $page_rewrite, $rules );
		}

		return $rules;
	}

	/**
	 * Generate rewrite rules based off of WP-Write but specific to nocache endpoints
	 *
	 * @param      $permalink_structure
	 * @param int  $ep_mask
	 * @param bool $paged
	 * @param bool $feed
	 * @param bool $forcomments
	 * @param bool $walk_dirs
	 * @param bool $endpoints
	 *
	 * @return array
	 */
	private function generate_rewrite_rules( $permalink_structure, $ep_mask = EP_NONE, $paged = true, $feed = true, $forcomments = false, $walk_dirs = true, $endpoints = true ) {
		preg_match_all( '/%.+?%/', $permalink_structure, $tokens );

		$num_tokens = count( $tokens[0] );

		$index   = $this->wp_rewrite->index; //probably 'index.php'
		$queries = array();
		for ( $i = 0; $i < $num_tokens; ++ $i ) {
			if ( 0 < $i ) {
				$queries[ $i ] = $queries[ $i - 1 ] . '&';
			} else {
				$queries[ $i ] = '';
			}

			$query_token   = str_replace( $this->wp_rewrite->rewritecode, $this->wp_rewrite->queryreplace, $tokens[0][ $i ] ) . $this->wp_rewrite->preg_index( $i + 1 );
			$queries[ $i ] .= $query_token;
		}
		$structure = $permalink_structure;
		$front     = substr( $permalink_structure, 0, strpos( $permalink_structure, '%' ) );

		if ( '/' !== $front ) {
			$structure = str_replace( $front, '', $structure );
		}
		$structure = trim( $structure, '/' );
		$dirs      = $walk_dirs ? explode( '/', $structure ) : array( $structure );
		$num_dirs  = count( $dirs );

		// Strip slashes from the front of $front.
		$front = preg_replace( '|^/+|', '', $front );

		$post_rewrite = array();
		$struct       = $front;
		for ( $j = 0; $j < $num_dirs; ++ $j ) {
			// Get the struct for this dir, and trim slashes off the front.
			$struct .= $dirs[ $j ] . '/'; // Accumulate. see comment near explode('/', $structure) above.
			$struct = ltrim( $struct, '/' );

			// Replace tags with regexes.
			$match = str_replace( $this->wp_rewrite->rewritecode, $this->wp_rewrite->rewritereplace, $struct );

			// Make a list of tags, and store how many there are in $num_toks.
			$num_toks = preg_match_all( '/%.+?%/', $struct, $toks );

			// Get the 'tagname=$matches[i]'.
			$query = ( ! empty( $num_toks ) && isset( $queries[ $num_toks - 1 ] ) ) ? $queries[ $num_toks - 1 ] : '';

			if ( ! empty( $query ) ) {
				if ( '&' !== $query[ strlen( $query ) - 1 ] ) {
					$query .= '&';
				}
			}
			$query .= 'nocache=1';

			// Start creating the array of rewrites for this dir.
			$rewrite = array();

			// If we've got some tags in this dir.
			if ( $num_toks ) {
				$post = false;

				/*
				 * Check to see if this dir is permalink-level: i.e. the structure specifies an
				 * individual post. Do this by checking it contains at least one of 1) post name,
				 * 2) post ID, 3) page name, 4) timestamp (year, month, day, hour, second and
				 * minute all present). Set these flags now as we need them for the endpoints.
				 */
				if ( strpos( $struct, '%postname%' ) !== false
				     || strpos( $struct, '%post_id%' ) !== false
				     || ( strpos( $struct, '%year%' ) !== false && strpos( $struct, '%monthnum%' ) !== false && strpos( $struct, '%day%' ) !== false && strpos( $struct, '%hour%' ) !== false && strpos( $struct, '%minute%' ) !== false && strpos( $struct, '%second%' ) !== false )
				) {
					$post = true;
				}

				if ( ! $post ) {
					// For custom post types, we need to add on endpoints as well.
					foreach ( get_post_types( array( '_builtin' => false ) ) as $ptype ) {
						if ( strpos( $struct, "%$ptype%" ) !== false ) {
							$post = true;
							break;
						}
					}
				}

				// If creating rules for a permalink, do all the endpoints like attachments etc.
				if ( $post ) {

					// Trim slashes from the end of the regex for this dir.
					$match = rtrim( $match, '/' );
					/*
					 * Post pagination, e.g. <permalink>/2/
					 * Previously: '(/[0-9]+)?/?$', which produced '/2' for page.
					 * When cast to int, returned 0.
					 */
					$match = $match . '(?:/([0-9]+))?/nocache/?$';
					$query = $index . '?' . $query . '&page=' . $this->wp_rewrite->preg_index( $num_toks + 1 );

					// Not matching a permalink so this is a lot simpler.
				} else {
					// Close the match and finalise the query.
					$match .= 'nocache/?$';
					$query = $index . '?' . $query;
				}

				/*
				 * Create the final array for this dir by joining the $rewrite array (which currently
				 * only contains rules/queries for trackback, pages etc) to the main regex/query for
				 * this dir
				 */
				/** @noinspection SlowArrayOperationsInLoopInspection */
				$rewrite = array_merge( $rewrite, array( $match => $query ) );
			}
			// Add the rules for this dir to the accumulating $post_rewrite.
			/** @noinspection SlowArrayOperationsInLoopInspection */
			$post_rewrite = array_merge( $rewrite, $post_rewrite );
		}

		return $post_rewrite;
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
