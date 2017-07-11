<?php

namespace WP\CriticalCSS;

use WP\CriticalCSS;

/**
 * Class API
 */
class API extends ComponentAbstract {
	const STATUS_UNKNOWN = 'JOB_UNKNOWN';
	const STATUS_QUEUED = 'JOB_QUEUED';
	const STATUS_ONGOING = 'JOB_ONGOING';
	const STATUS_DONE = 'JOB_DONE';
	/**
	 * @var
	 */
	protected $api_key;

	/**
	 * CriticalCSS_API constructor.
	 *
	 * @param $api_key
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * @return bool
	 */
	public function ping() {
		$response = $this->_send_request( 'get', 'ping' );
		$response = (array) $response;

		return empty( $response );
	}

	/**
	 * @param string $type
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $args
	 *
	 * @return array|bool|mixed|object
	 */
	private function _send_request( $type, $endpoint, $query_args = [], $args = [] ) {
		$type = strtolower( $type );
		$func = "wp_remote_{$type}";
		if ( ! function_exists( $func ) ) {
			return false;
		}
		$query_args = array_merge( $query_args, [
			'version' => CriticalCSS::VERSION,
		] );
		$response   = $func( add_query_arg( $query_args, "https://criticalcss.com/api/premium/{$endpoint}" ), array_merge_recursive( [
			'headers' => [
				'Authorization' => 'JWT ' . $this->api_key,
			],
		], $args ) );
		if ( $response instanceof \WP_Error ) {
			return $response;
		}
		if ( is_array( $response ) ) {
			return json_decode( $response['body'] );
		} else {
			return false;
		}
	}

	/**
	 * @param $item_id
	 *
	 * @return array|bool|mixed|object
	 */
	public function get_result( $item_id ) {
		return $this->_send_request( 'get', 'results', [
			'resultId' => $item_id,
		] );
	}

	/**
	 * @param array $item
	 *
	 * @return array|bool|mixed|object
	 */
	public function generate( array $item ) {
		$response = $this->_send_request( 'post', 'generate', [], [
			'body' => [
				'height'                   => 900,
				'width'                    => 1300,
				'url'                      => $this->app->get_permalink( $item ),
				'aff'                      => 3,
				'version'                  => CriticalCSS::VERSION,
				'wpCriticalCssQueueLength' => $this->app->get_api_queue()->get_length(),
			],
		] );

		return $response instanceof \WP_Error ? $response : $response->job;
	}

	public function get_invalid_url_regexes() {

		$cache_name = CriticalCSS::LANG_DOMAIN . '_imvalid_urls';
		$cache      = $this->app->get_cache_manager()->get_store()->get_transient( $cache_name );

		if ( empty( $cache ) ) {
			$response = $this->_send_request( 'get', 'invalid-generate-url-rules' );
			$cache    = $response->rules;
			$this->app->get_cache_manager()->get_store()->set_transient( $cache_name, $cache, WEEK_IN_SECONDS * 2 );
		}

		return $cache;
	}
}
