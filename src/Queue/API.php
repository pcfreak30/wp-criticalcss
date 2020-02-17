<?php


namespace WP_CriticalCSS\Queue;


use WP_CriticalCSS\Core\Component;
use WP_CriticalCSS\Interfaces\QueueTaskInterface;

/**
 * Class API
 *
 * @package WP_CriticalCSS\Queue
 * @property \WP_CriticalCSS\Queue_Manager $parent
 * @property  \WP_CriticalCSS\Plugin       $plugin
 */
class API extends Component implements QueueTaskInterface {

	/**
	 * @var \WP_CriticalCSS\API
	 */
	private $api;
	/**
	 * @var bool
	 */
	private $ping_checked;

	/**
	 * API constructor.
	 *
	 * @param \WP_CriticalCSS\API $api
	 */
	public function __construct( \WP_CriticalCSS\API $api ) {
		$this->api = $api;
	}

	/**
	 * @param array $item
	 *
	 * @return void
	 */
	public function process( array $item ) {

		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		if ( ! ( $item = $this->parent->maybe_fetch_args( $this->plugin->model_manager->API_Queue, $item ) ) ) {
			return;
		}

		$settings = $this->plugin->settings_manager->settings;

		if ( empty( $settings ) || empty( $settings['apikey'] ) ) {
			return;
		}


		$this->api->api_key = $settings['apikey'];
		if ( ! empty( $item['timestamp'] ) && $item['timestamp'] + 8 >= time() ) {
			$this->parent->schedule_api_task( $item );

			return;
		}
		if ( ! $this->ping_checked ) {
			if ( $this->api->ping() ) {
				$this->ping_checked = true;
			} else {
				return;
			}
		}
		$item['timestamp'] = time();
		$url               = $this->plugin->get_permalink( $item );
		if ( empty( $url ) ) {
			return;
		}
		$bad_urls = $this->api->get_invalid_url_regexes();
		$bad_urls = array_filter( $bad_urls, function ( $regex ) use ( $url ) {
			return preg_match( "~$regex~", $url );
		} );
		if ( ! empty( $bad_urls ) ) {
			return;
		}
		if ( 2083 <= strlen( $url ) ) {
			return;
		}
		if ( ! empty( $item['queue_id'] ) ) {
			$result = $this->api->get_result( $item['queue_id'] );
			if ( $result instanceof \WP_Error ) {
				return;
			}
			/** @var \stdClass $result */
			if ( ! empty( $result->status ) ) {
				$item['status'] = $result->status;
			}
			/** @var \stdClass $result */
			if ( ! empty( $result->resultStatus ) ) {
				$item['result_status'] = $result->resultStatus;
			}
			/** @var \stdClass $result */
			if ( ! empty( $result->error ) || in_array( $result->status, [
					'JOB_UNKNOWN',
					'JOB_FAILED',
				] ) ) {
				unset( $item['queue_id'] );
				$this->parent->schedule_api_task( $item );

				return;
			}
			if ( 'JOB_QUEUED' === $result->status ) {
				// @codingStandardsIgnoreLine
				$item['queue_index'] = $result->queueIndex;
				$this->parent->schedule_api_task( $item );

				return;
			}
			if ( 'JOB_ONGOING' === $result->status ) {
				$this->parent->schedule_api_task( $item );

				return;
			}
			if ( 'JOB_DONE' === $result->status ) {
				/** @var \stdClass $result */
				if ( 'GOOD' === $result->resultStatus && ! empty( $result->css ) ) {
					$this->plugin->integration_manager->disable_integrations();
					if ( ! empty( $item['template'] ) ) {
						$logs = $this->plugin->model_manager->Template_Log->get_entries_by_template( $item['template'] );
						foreach ( $logs as $log ) {
							$url = $this->plugin->get_permalink( $log );
							if ( ! parse_url( $url, PHP_URL_QUERY ) ) {
								$this->plugin->cache_manager->purge_page_cache( $log['type'], $log['object_id'], $url );
							}
							$this->plugin->model_manager->Template_Log->delete( [
								'object_id' => $log['object_id'],
								'type'      => $log['type'],
								'url'       => $log['url'],
							] );
						}
					} else {
						$this->plugin->cache_manager->purge_page_cache( $item['type'], $item['object_id'] ?: null, $this->plugin->get_permalink( $item ) );
					}
					$this->plugin->integration_manager->enable_integrations();
					$this->plugin->data_manager->set_cache( $item, $result->css );
					if ( empty( $item['template'] ) ) {
						$this->plugin->data_manager->set_css_hash( $item, $item['css_hash'] );
						$this->plugin->data_manager->set_html_hash( $item, $item['html_hash'] );
					}
					$this->plugin->model_manager->API_Queue->delete_item( $this->current_action );
					$this->plugin->model_manager->Template_Log->insert( $item );
				}
			} else {
				unset( $item['queue_id'] );
				$this->parent->schedule_api_task( $item );
			}
		} else {
			$result = $this->api->generate( $item );
			if ( ! ( $result instanceof \WP_Error ) ) {
				$item['queue_id'] = $result->id;
				$item['status']   = $result->status;
			}

			$this->parent->schedule_api_task( $item );
		}

	}

	/**
	 * @return \WP_CriticalCSS\API
	 */
	public function get_api() {
		return $this->api;
	}
}
