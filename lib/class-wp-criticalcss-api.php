<?php

defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );
/**
 * Class CriticalCSS_API
 */
class WP_CriticalCSS_API {
	const STATUS_UNKNOWN = 'JOB_UNKNOWN';
	const STATUS_QUEUED = 'JOB_QUEUED';
	const STATUS_ONGOING = 'JOB_ONGOING';
	const STATUS_DONE = 'JOB_DONE';
	/**
	 * @var
	 */
	private $_api_key;

	/**
	 * CriticalCSS_API constructor.
	 *
	 * @param $api_key
	 */
	public function __construct( $api_key ) {
		$this->_api_key = $api_key;
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
	private function _send_request( $type, $endpoint, $query_args = array(), $args = array() ) {
		$type = strtolower( $type );
		$func = "wp_remote_{$type}";
		if ( ! function_exists( $func ) ) {
			return false;
		}
		$query_args = array_merge( $query_args, array( 'version' => WP_CriticalCSS::VERSION ) );
		$response   = $func( add_query_arg( $query_args, "https://criticalcss.com/api/premium/${endpoint}" ), array_merge_recursive( array(
			'headers' => array(
				'Authorization' => 'JWT ' . $this->_api_key,
			),
		), $args ) );
		if ( $response instanceof WP_Error ) {
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
		return $this->_send_request( 'get', 'results', array( 'resultId' => $item_id ) );
	}

	/**
	 * @param array $item
	 *
	 * @return array|bool|mixed|object
	 */
	public function generate( array $item ) {
		$response = $this->_send_request( 'post', 'generate', array(), array(
			'body' => array(
				'height'  => 900,
				'width'   => 1300,
				'url'     => WP_CriticalCSS::get_permalink( $item ),
				'aff'     => 3,
				'version' => WP_CriticalCSS::VERSION,
			),
		) );

		return $response instanceof WP_Error ? $response : $response->job;
	}
}