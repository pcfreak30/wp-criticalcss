<?php


namespace WP_CriticalCSS\Queue;


use WP_CriticalCSS\Core\Component;
use WP_CriticalCSS\Interfaces\QueueTaskInterface;

/**
 * Class Web
 *
 * @package WP_CriticalCSS\Queue
 * @property \WP_CriticalCSS\Queue_Manager $parent
 * @property  \WP_CriticalCSS\Plugin       $plugin
 */
class Web extends Component implements QueueTaskInterface {

	/**
	 * @param array $item
	 */
	public function process( array $item ) {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		if ( ! ( $item = $this->parent->maybe_fetch_args( $this->plugin->model_manager->Web_Queue, $item ) ) ) {
			return;
		}
		if ( $this->plugin->model_manager->API_Queue->get_item_exists( $item ) ) {
			return;
		}

		$css_hash  = $this->plugin->data_manager->get_css_hash( $item );
		$html_hash = $this->plugin->data_manager->get_html_hash( $item );
		$url       = $this->plugin->get_permalink( $item );
		if ( empty( $url ) ) {
			return;
		}
		$result = wp_remote_get( $this->plugin->get_permalink( $item ), apply_filters( 'wp_criticalcss_web_check_request_args', [], $item ) );

		if ( $result instanceof \WP_Error ) {
			if ( empty( $item['error'] ) ) {
				$item['error'] = 0;
			}

			if ( $item['error'] <= apply_filters( 'wp_criticalcss_web_check_retries', 3 ) ) {
				$item['error'] ++;
				sleep( 1 );
				$this->parent->schedule_web_task( $item );
			}

			return;
		}

		$document = new \DOMDocument();
		if ( ! @$document->loadHTML( $result['body'] ) ) {
			return;
		}
		$xpath = new \DOMXpath( $document );
		$css   = '';
		$urls  = [];
		foreach ( $xpath->query( '((//style|//STYLE)|(//link|//LINK)[@rel="stylesheet"])' ) as $tag ) {
			$name = strtolower( $tag->tagName );
			$rel  = $tag->getAttribute( 'rel' );
			$href = $tag->getAttribute( 'href' );
			if ( 'link' === $name ) {
				if ( 'stylesheet' === $rel ) {

					// If not a stylesheet, rocket_async_css_process_file return, or exclude regex/file extension matches, move on
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
		$new_css_hash = hash( 'crc32b', $css );
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

		if ( $changed && ! $this->plugin->model_manager->API_Queue->get_item_exists( $item ) ) {
			$item['css_hash']  = $css_hash;
			$item['html_hash'] = $html_hash;
			$this->plugin->get_integration_manager()->disable_integrations();
			$this->plugin->get_cache_manager()->purge_page_cache( $item['type'], $item['object_id'], $this->plugin->get_permalink( $item ) );
			$this->plugin->get_integration_manager()->enable_integrations();
			$this->plugin->get_data_manager()->set_cache( $item, '' );
			$this->plugin->model_manager->Web_Queue->delete_item( $this->current_action );
			$this->parent->schedule_api_task( $item );
		}

	}
}
