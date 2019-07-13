<?php

namespace WP\CriticalCSS\Web\Check\Background;

use WP\CriticalCSS\Background\ProcessAbstract;
use WP\CriticalCSS\Queue\Web\Check\Table;

class Process extends ProcessAbstract {
	protected $action = 'wp_criticalcss_web_check';

	private $_processed_urls = [];

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
		$item = $this->set_processing();
		$url  = wp_criticalcss()->get_permalink( $item );
		if ( isset( $this->_processed_urls[ $url ] ) ) {
			return false;
		}
		$api_queue = wp_criticalcss()->get_api_queue();

		if ( $api_queue->get_item_exists( $item ) ) {
			return false;
		}

		$css_hash  = wp_criticalcss()->get_data_manager()->get_css_hash( $item );
		$html_hash = wp_criticalcss()->get_data_manager()->get_html_hash( $item );
		$url       = wp_criticalcss()->get_permalink( $item );
		if ( empty( $url ) ) {
			return false;
		}
		$result = wp_remote_get( wp_criticalcss()->get_permalink( $item ), apply_filters( 'wp_criticalcss_web_check_request_args', [], $item ) );

		if ( $result instanceof \WP_Error ) {
			if ( empty( $item['error'] ) ) {
				$item['error'] = 0;
			}

			if ( $item['error'] <= apply_filters( 'wp_criticalcss_web_check_retries', 3 ) ) {
				$item['error'] ++;
				sleep( 1 );
				$item = $this->set_pending();

				return $item;
			}

			return false;
		}

		$document = new \DOMDocument();
		if ( ! @$document->loadHTML( $result['body'] ) ) {
			return false;
		}
		$xpath = new \DOMXpath( $document );
		$css   = '';
		$urls  = [];
		foreach ( $xpath->query( '((//style|//STYLE)|(//link|//LINK)[@rel="stylesheet"])' ) as $tag ) {
			$name = strtolower( $tag->tagName );
			$rel  = $tag->getAttribute( 'rel' );
			$href = $tag->getAttribute( 'href' );
			if ( 'link' == $name ) {
				if ( 'stylesheet' == $rel ) {

					// If not a stylesheet, rocket_async_css_process_file return false, or exclude regex/file extension matches, move on
					if ( 'stylesheet' !== $rel ) {
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
			foreach ( $matches[1] as $match ) {
				$href = $match;
				if ( 0 === strpos( $href, '//' ) ) {
					//Handle no protocol urls
					$href = 'http:' . $match;
				}
				$href   = set_url_scheme( $href );
				$urls[] = $href;
			}
		}
		$urls = apply_filters( 'wp_criticalcss_web_check_css_urls', $urls, $item );
		foreach ( $urls as $url ) {
			$host = parse_url( $url, PHP_URL_HOST );
			if ( empty( $host ) ) {
				$url = site_url( $url );
			}
			$file = wp_remote_get( $url, [
				'sslverify' => false,
			] );
			// Catch Error
			if ( $file instanceof \WP_Error || ( is_array( $file ) && ( empty( $file['response']['code'] ) || ! in_array( $file['response']['code'], [
							200,
							304,
						] ) ) )
			) {
				continue;
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
			if ( null !== $body ) {
				$new_html_hash = hash( "crc32b", $document->saveHTML( $body ) );
				if ( empty( $html_hash ) || $html_hash != $new_html_hash ) {
					$changed   = true;
					$html_hash = $new_html_hash;
				}
			}
		}

		if ( $changed && ! $api_queue->get_item_exists( $item ) ) {
			$item['css_hash']  = $css_hash;
			$item['html_hash'] = $html_hash;
			wp_criticalcss()->get_integration_manager()->disable_integrations();
			wp_criticalcss()->get_cache_manager()->purge_page_cache( $item['type'], $item['object_id'], wp_criticalcss()->get_permalink( $item ) );
			wp_criticalcss()->get_integration_manager()->enable_integrations();
			wp_criticalcss()->get_data_manager()->set_cache( $item, '' );
			$api_queue->push_to_queue( $item )->save();
		}
		$this->_processed_urls[ $url ] = true;

		return false;
	}

	private function set_processing() {
		return $this->set_status( Table::STATUS_PROCESSING );
	}

	private function set_status( $status ) {
		$batch          = $this->get_batch();
		$data           = end( $batch->data );
		$data['status'] = $status;
		$batch->data    = [ $data ];
		$this->update( $batch->key, $batch->data );

		return $data;
	}

	private function set_pending() {
		return $this->set_status( Table::STATUS_PENDING );
	}
}
