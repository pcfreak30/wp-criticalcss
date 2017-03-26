<?php
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

class WP_CriticalCSS_Web_Check_Background_Process extends WP_CriticalCSS_Background_Process {
	protected $action = 'wp_criticalcss_web_check';

	private $_processed_urls = array();

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$url = WP_CriticalCSS::get_permalink( $item );
		if ( isset( $this->_processed_urls[ $url ] ) ) {
			return false;
		}
		$api_queue = WP_CriticalCSS::get_api_queue();

		if ( $api_queue->get_item_exists( $item ) ) {
			return false;
		}

		$css_hash  = WP_CriticalCSS::get_css_hash( $item );
		$html_hash = WP_CriticalCSS::get_html_hash( $item );
		$url       = WP_CriticalCSS::get_permalink( $item );
		if ( empty( $url ) ) {
			return false;
		}
		$result = wp_remote_get( WP_CriticalCSS::get_permalink( $item ), apply_filters( 'wp_criticalcss_web_check_request_args', array(), $item ) );

		if ( $result instanceof WP_Error ) {
			if ( empty( $item['error'] ) ) {
				$item['error'] = 0;
			}

			if ( $item['error'] <= apply_filters( 'wp_criticalcss_web_check_retries', 3 ) ) {
				$item['error'] ++;
				sleep( 1 );

				return $item;
			}

			return false;
		}

		$document = new DOMDocument();
		if ( ! @$document->loadHTML( $result['body'] ) ) {
			return false;
		}
		$xpath = new DOMXpath( $document );
		$css   = '';
		$urls  = array();
		foreach ( $xpath->query( '((//style|//STYLE)|(//link|//LINK)[@rel="stylesheet"])' ) as $tag ) {
			$name = strtolower( $tag->tagName );
			$rel  = $tag->getAttribute( 'rel' );
			$href = $tag->getAttribute( 'href' );
			if ( 'link' == $name ) {
				if ( 'stylesheet' == $rel ) {

					// If not a stylesheet, rocket_async_css_process_file return false, or exclude regex/file extension matches, move on
					if ( 'stylesheet' != $rel ) {
						continue;
					}
					if ( 0 === strpos( $href, '//' ) ) {
						//Handle no protocol urls
						$href = 'http:' . $href;
					}
					$href   = set_url_scheme( $href );
					$urls[] = $href;
				} else {
					$css .= $tag->textContent;
				}
			}
		}
		if ( preg_match_all( '#loadCSS\s*\(\s*["\'](.*)["\']\s*#', $result['body'], $matches ) ) {
			foreach ( $matches as $match ) {
				$href = $match[1];
				if ( 0 === strpos( $href, '//' ) ) {
					//Handle no protocol urls
					$href = 'http:' . $match[1];
				}
				$href    = set_url_scheme( $href );
				$css_url = parse_url( set_url_scheme( $href ) );
				$urls[]  = $css_url;
			}
		}
		$urls = apply_filters( 'wp_criticalcss_web_check_css_urls', $urls, $item );
		foreach ( $urls as $url ) {
			$host = parse_url( $url, PHP_URL_HOST );
			if ( empty( $host ) ) {
				$url = site_url( $url );
			}
			$file = wp_remote_get( $url, array(
				'sslverify' => false,
			) );
			// Catch Error
			if ( $file instanceof \WP_Error || ( is_array( $file ) && ( empty( $file['response']['code'] ) || ! in_array( $file['response']['code'], array(
							200,
							304,
						) ) ) )
			) {
				if ( empty( $item['error'] ) ) {
					$item['error'] = 0;
				}
				if ( $item['error'] <= apply_filters( 'wp_criticalcss_web_check_retries', 3 ) ) {
					$item['error'] ++;
					sleep( 1 );

					return $item;
				}

				return false;
			}
			$css .= $file['body'];
		}
		$changed      = false;
		$new_css_hash = hash( "crc32b", $css );
		if ( empty( $css_hash ) || $css_hash != $new_css_hash ) {
			$changed  = true;
			$css_hash = $new_css_hash;
		}

		if ( ! $changed ) {
			$body = $document->getElementsByTagName( 'body' )->item( 0 );
			if ( ! empty( $body ) ) {
				$new_html_hash = hash( "crc32b", $document->saveHTML( $body ) );
				if ( empty( $html_hash ) || $html_hash != $new_html_hash ) {
					$changed   = true;
					$html_hash = $new_html_hash;
				}
			}
		}

		if ( $changed ) {
			$item['css_hash']  = $css_hash;
			$item['html_hash'] = $html_hash;
			WP_CriticalCSS::disable_external_integration();
			WP_CriticalCSS::purge_page_cache( $item['type'], $item['object_id'], WP_CriticalCSS::get_permalink( $item ) );
			WP_CriticalCSS::external_integration();
			WP_CriticalCSS::set_cache( $item, '' );
			$api_queue->push_to_queue( $item )->save();

		}
		$this->_processed_urls[ $url ] = true;

		return false;
	}
}