<?php

namespace WP\CriticalCSS\API\Background;

/**
 * Class Process
 *
 * @package WP\CriticalCSS\API\Background
 */
class Process extends \WP\CriticalCSS\Background\ProcessAbstract {
	protected $action = 'wp_criticalcss_api';
	private $_ping_checked = false;

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
		$settings = WPCCSS()->get_settings_manager()->get_settings();

		if ( empty( $settings ) || empty( $settings['apikey'] ) ) {
			return false;
		}

		if ( ! empty( $item['timestamp'] ) && $item['timestamp'] + 8 >= time() ) {
			return $item;
		}
		$api = wpccss_container()->create( '\\WP\\CriticalCSS\\API', [ $settings['apikey'] ] );
		$api->set_app( WPCCSS() );
		if ( ! $this->_ping_checked ) {
			if ( $api->ping() ) {
				$this->_ping_checked = true;
			} else {
				return false;
			}
		}
		$item['timestamp'] = time();
		$url               = WPCCSS()->get_permalink( $item );
		if ( empty( $url ) ) {
			return false;
		}
		$bad_urls = $api->get_invalid_url_regexes();
		$bad_urls = array_filter( $bad_urls, function ( $regex ) use ( $url ) {
			return preg_match( $regex, $url );
		} );
		if ( ! empty( $bad_urls ) ) {
			return false;
		}
		if ( 2083 <= strlen( $url ) ) {
			return false;
		}
		if ( ! empty( $item['queue_id'] ) ) {
			$result = $api->get_result( $item['queue_id'] );
			if ( $result instanceof \WP_Error ) {
				return false;
			}
			if ( ! empty( $result->status ) ) {
				$item['status'] = $result->status;
			}
			// @codingStandardsIgnoreLine
			if ( ! empty( $result->resultStatus ) ) {
				// @codingStandardsIgnoreLine
				$item['result_status'] = $result->resultStatus;
			}
			if ( 'JOB_UNKNOWN' === $result->status ) {
				unset( $item['queue_id'] );

				return $item;
			}
			if ( ! empty( $result->error ) || 'JOB_QUEUED' === $result->status ) {
				// @codingStandardsIgnoreLine
				$item['queue_index'] = $result->queueIndex;

				return $item;
			}
			if ( 'JOB_DONE' === $result->status ) {
				// @codingStandardsIgnoreLine
				if ( 'GOOD' === $result->resultStatus && ! empty( $result->css ) ) {
					WPCCSS()->get_integration_manager()->disable_integrations();
					if ( ! empty( $item['template'] ) ) {
						WPCCSS()->get_cache_manager()->purge_page_cache();
					} else {
						WPCCSS()->get_cache_manager()->purge_page_cache( $item['type'], $item['object_id'], WPCCSS()->get_permalink( $item ) );
					}
					WPCCSS()->get_integration_manager()->enable_integrations();
					WPCCSS()->get_data_manager()->set_cache( $item, $result->css );
					WPCCSS()->get_data_manager()->set_css_hash( $item, $item['css_hash'] );
					WPCCSS()->get_data_manager()->set_html_hash( $item, $item['html_hash'] );
				}
			}
		} else {
			$result = $api->generate( $item );
			if ( $result instanceof \WP_Error ) {
				return false;
			}
			$item['queue_id'] = $result->id;
			$item['status']   = $result->status;

			return $item;
		}

		return false;
	}
}
