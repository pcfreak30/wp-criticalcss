<?php

namespace WP\CriticalCSS\Integration;
/**
 * Class WPEngine
 */
class WPEngine extends IntegrationAbstract {

	/**
	 * WP_CriticalCSS_Integration_WPEngine constructor.
	 */
	public function init() {
		if ( class_exists( '\WPECommon' ) ) {
			parent::init();
		}
	}

	/**
	 *
	 */
	public function enable() {
		add_action( 'wp_criticalcss_purge_cache', [
			$this,
			'purge_cache',
		], 10, 3 );
		add_action( 'wp_criticalcss_nocache', [
			$this,
			'disable_cache',
		] );
	}

	/**
	 * @return void
	 */
	public function disable() {
	}

	/**
	 * @SuppressWarnings(PHPMD)
	 * @param null $type
	 * @param null $object_id
	 * @param null $url
	 */
	public function purge_cache( $type = null, $object_id = null, $url = null ) {
		global $wpe_varnish_servers;
		if ( class_exists( '\WPECommon' ) ) {
			if ( empty( $type ) ) {
				/** @noinspection PhpUndefinedClassInspection */
				\WpeCommon::purge_varnish_cache( null, true );
			} elseif ( 'post' === $type ) {
				/** @noinspection PhpUndefinedClassInspection */
				\WpeCommon::purge_varnish_cache( $object_id, true );
			} else {
				$blog_url = home_url();
				// @codingStandardsIgnoreLine
				$blog_url_parts = @parse_url( $blog_url );
				$blog_domain    = $blog_url_parts['host'];
				$purge_domains  = [ $blog_domain ];
				$object_parts   = parse_url( $url );
				$object_uri     = rtrim( $object_parts   ['path'], '/' ) . '(.*)';
				if ( ! empty( $object_parts['query'] ) ) {
					$object_uri .= '?' . $object_parts['query'];
				}
				$paths = [ $object_uri ];
				/** @noinspection PhpUndefinedClassInspection */
				$purge_domains = array_unique( array_merge( $purge_domains, \WpeCommon::get_blog_domains() ) );
				if ( defined( 'WPE_CLUSTER_TYPE' ) && WPE_CLUSTER_TYPE === 'pod' ) {
					$wpe_varnish_servers = [ 'localhost' ];
				} // End if().
				elseif ( ! isset( $wpe_varnish_servers ) ) {
					if ( ! defined( 'WPE_CLUSTER_ID' ) || ! WPE_CLUSTER_ID ) {
						$lbmaster = 'lbmaster';
					} elseif ( WPE_CLUSTER_ID >= 4 ) {
						$lbmaster = 'localhost'; // so the current user sees the purge
					} else {
						$lbmaster = 'lbmaster-' . WPE_CLUSTER_ID;
					}
					$wpe_varnish_servers = [ $lbmaster ];
				}
				$path_regex          = '(' . join( '|', $paths ) . ')';
				$hostname            = $purge_domains[0];
				$purge_domains       = array_map( 'preg_quote', $purge_domains );
				$purge_domain_chunks = array_chunk( $purge_domains, 100 );
				foreach ( $purge_domain_chunks as $chunk ) {
					$purge_domain_regex = '^(' . join( '|', $chunk ) . ')$';
					// Tell Varnish.
					foreach ( $wpe_varnish_servers as $varnish ) {
						$headers = [
							'X-Purge-Path' => $path_regex,
							'X-Purge-Host' => $purge_domain_regex,
						];
						/** @noinspection PhpUndefinedClassInspection */
						\WpeCommon::http_request_async( 'PURGE', $varnish, 9002, $hostname, '/', $headers, 0 );
					}
				}
			}// End if().
			sleep( 1 );
		}// End if().
	}

	public function disable_cache() {
		$permalink = $this->plugin->get_permalink( $this->plugin->request->get_current_page_type() );
		$this->purge_cache( $permalink['type'], $permalink['object_id'], $permalink['url'] );
	}
}
