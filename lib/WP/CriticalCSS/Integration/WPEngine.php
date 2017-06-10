<?php

namespace WP\CriticalCSS\Integration;
/**
 * Class WPEngine
 */
class WPEngine extends IntegrationAbstract {

	/**
	 * WP_CriticalCSS_Integration_WPEngine constructor.
	 */
	public function __construct() {
		if ( class_exists( 'WPECommon' ) ) {
			parent::__construct();
		}
	}

	/**
	 *
	 */
	public function enable() {
		add_action( 'wp_criticalcss_purge_cache', array(
			$this,
			'_purge_cache',
		) );
	}

	/**
	 * @return void
	 */
	public function disable() {
		remove_action( 'wp_criticalcss_purge_cache', array(
			$this,
			'_purge_cache',
		) );
	}

	/**
	 * @SuppressWarnings(PHPMD)
	 * @param null $type
	 * @param null $object_id
	 * @param null $url
	 */
	private function _purge_cache( $type = null, $object_id = null, $url = null ) {
		global $wpe_varnish_servers;
		if ( class_exists( 'WPECommon' ) ) {
			if ( empty( $type ) ) {
				/** @noinspection PhpUndefinedClassInspection */
				WpeCommon::purge_varnish_cache();
			} elseif ( 'post' == $type ) {
				/** @noinspection PhpUndefinedClassInspection */
				WpeCommon::purge_varnish_cache( $object_id );
			} else {
				$blog_url = home_url();
				// @codingStandardsIgnoreLine
				$blog_url_parts = @parse_url( $blog_url );
				$blog_domain    = $blog_url_parts['host'];
				$purge_domains  = array( $blog_domain );
				$object_parts   = parse_url( $url );
				$object_uri     = rtrim( $object_parts   ['path'], '/' ) . '(.*)';
				if ( ! empty( $object_parts['query'] ) ) {
					$object_uri .= '?' . $object_parts['query'];
				}
				$paths = array( $object_uri );
				/** @noinspection PhpUndefinedClassInspection */
				$purge_domains = array_unique( array_merge( $purge_domains, WpeCommon::get_blog_domains() ) );
				if ( defined( 'WPE_CLUSTER_TYPE' ) && WPE_CLUSTER_TYPE == 'pod' ) {
					$wpe_varnish_servers = array( 'localhost' );
				} // End if().
				elseif ( ! isset( $wpe_varnish_servers ) ) {
					if ( ! defined( 'WPE_CLUSTER_ID' ) || ! WPE_CLUSTER_ID ) {
						$lbmaster = 'lbmaster';
					} elseif ( WPE_CLUSTER_ID >= 4 ) {
						$lbmaster = 'localhost'; // so the current user sees the purge
					} else {
						$lbmaster = 'lbmaster-' . WPE_CLUSTER_ID;
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
						$headers = array(
							'X-Purge-Path' => $path_regex,
							'X-Purge-Host' => $purge_domain_regex,
						);
						/** @noinspection PhpUndefinedClassInspection */
						WpeCommon::http_request_async( 'PURGE', $varnish, 9002, $hostname, '/', $headers, 0 );
					}
				}
			}// End if().
			sleep( 1 );
		}// End if().
	}
}
