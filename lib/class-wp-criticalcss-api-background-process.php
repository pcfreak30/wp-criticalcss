<?php
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

class WP_CriticalCSS_API_Background_Process extends WP_CriticalCSS_Background_Process {
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
		$settings = WPCCSS()->get_settings();

		if ( empty( $settings ) || empty( $settings['apikey'] ) ) {
			return false;
		}

		if ( ! empty( $item['timestamp'] ) && $item['timestamp'] + ( SECOND_IN_SECONDS * 8 ) >= time() ) {
			return $item;
		}

		$api = new WP_CriticalCSS_API( $settings['apikey'] );
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
		if ( ! empty( $item['queue_id'] ) ) {
			$result = $api->get_result( $item['queue_id'] );
			if ( $result instanceof WP_Error ) {
				return false;
			}
			if ( ! empty( $result->status ) ) {
				$item['status'] = $result->status;
			}
			if ( ! empty( $result->resultStatus ) ) {
				$item['result_status'] = $result->resultStatus;
			}
			if ( 'JOB_UNKNOWN' == $result->status ) {
				unset( $item['queue_id'] );

				return $item;
			}
			if ( 'JOB_ONGOING' == $result->status || 'JOB_QUEUED' == $result->status ) {
				if ( 'JOB_QUEUED' == $result->status ) {
					$item['queue_index'] = $result->queueIndex;
				}

				return $item;
			}
			if ( 'JOB_DONE' == $result->status ) {
				if ( 'GOOD' == $result->resultStatus && ! empty( $result->css ) ) {
					WPCCSS()->disable_integrations();
					if ( ! empty( $item['template'] ) ) {
						WPCCSS()->purge_page_cache();
					} else {
						WPCCSS()->purge_page_cache( $item['type'], $item['object_id'], WPCCSS()->get_permalink( $item ) );
					}
					WPCCSS()->enable_integrations();
					WPCCSS()->set_cache( $item, $result->css );
					WPCCSS()->set_css_hash( $item, $item['css_hash'] );
					WPCCSS()->set_html_hash( $item, $item['html_hash'] );
				}
			}
		} else {
			$result = $api->generate( $item );
			if ( $result instanceof WP_Error ) {
				return false;
			}
			$item['queue_id'] = $result->id;
			$item['status']   = $result->status;

			return $item;
		}

		return false;
	}
}