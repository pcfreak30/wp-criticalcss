<?php

namespace WP\CriticalCSS\API\Background;

use WP\CriticalCSS\API;

/**
 * Class Process
 *
 * @package WP\CriticalCSS\API\Background
 */
class Process extends \WP\CriticalCSS\Background\ProcessAbstract {
	protected $action = 'wp_criticalcss_api';
	private $ping_checked = false;
	/**
	 * @var \WP\CriticalCSS\API
	 */
	private $api;

	/**
	 * Process constructor.
	 *
	 * @param \WP\CriticalCSS\API $api
	 */
	public function __construct( API $api ) {
		$this->api = $api;
		parent::__construct();
	}

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
		if ( null === $this->api->parent ) {
			$this->api->parent = wp_criticalcss();
		}
		$settings = wp_criticalcss()->settings_manager->settings;

		if ( empty( $settings ) || empty( $settings['apikey'] ) ) {
			return false;
		}

		$this->api->api_key = $settings['apikey'];

		if ( ! empty( $item['timestamp'] ) && $item['timestamp'] + 8 >= time() ) {
			return $item;
		}
		if ( ! $this->ping_checked ) {
			if ( $this->api->ping() ) {
				$this->ping_checked = true;
			} else {
				return false;
			}
		}
		$item['timestamp'] = time();
		$url               = wp_criticalcss()->get_permalink( $item );
		if ( empty( $url ) ) {
			return false;
		}
		$bad_urls = $this->api->get_invalid_url_regexes();
		$bad_urls = array_filter( $bad_urls, function ( $regex ) use ( $url ) {
			return preg_match( "~$regex~", $url );
		} );
		if ( ! empty( $bad_urls ) ) {
			return false;
		}
		if ( 2083 <= strlen( $url ) ) {
			return false;
		}
		if ( ! empty( $item['queue_id'] ) ) {
			$result = $this->api->get_result( $item['queue_id'] );
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
			if ( ! empty( $result->error ) || in_array( $result->status, [
					'JOB_UNKNOWN',
					'JOB_FAILED',
				] ) ) {
				unset( $item['queue_id'] );

				return $item;
			}
			if ( 'JOB_QUEUED' === $result->status ) {
				// @codingStandardsIgnoreLine
				$item['queue_index'] = $result->queueIndex;

				return $item;
			}
			if ( 'JOB_ONGOING' === $result->status ) {
				return $item;
			}
			if ( 'JOB_DONE' === $result->status ) {
				// @codingStandardsIgnoreLine
				if ( 'GOOD' === $result->resultStatus && ! empty( $result->css ) ) {
					wp_criticalcss()->integration_manager->disable_integrations();
					if ( ! empty( $item['template'] ) ) {
						$logs = wp_criticalcss()->template_log->get( $item['template'] );
						foreach ( $logs as $log ) {
							$url = wp_criticalcss()->get_permalink( $log );
							if ( ! parse_url( $url, PHP_URL_QUERY ) ) {
								wp_criticalcss()->cache_manager->purge_page_cache( $log['type'], $log['object_id'], $url );
							}
							wp_criticalcss()->template_log->delete( $log['object_id'], $log['type'], $log['url'] );
						}
						wp_criticalcss()->cache_manager->purge_page_cache( $item['type'], $item['object_id'], wp_criticalcss()->get_permalink( $item ) );
					} else {
						wp_criticalcss()->cache_manager->purge_page_cache( $item['type'], $item['object_id'], wp_criticalcss()->get_permalink( $item ) );
					}
					wp_criticalcss()->integration_manager->enable_integrations();
					wp_criticalcss()->data_manager->set_cache( $item, $result->css );
					if ( empty( $item['template'] ) ) {
						wp_criticalcss()->data_manager->set_css_hash( $item, $item['css_hash'] );
						wp_criticalcss()->data_manager->set_html_hash( $item, $item['html_hash'] );
					}
					wp_criticalcss()->log->insert( $item );
				}
			} else {
				unset( $item['queue_id'] );

				return $item;
			}
		} else {
			$result = $this->api->generate( $item );
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
