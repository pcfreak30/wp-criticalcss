<?php

namespace WP_CriticalCSS;

use WP_CriticalCSS\Core\Component;
use WP_CriticalCSS\Queue\API as API_Queue;
use WP_CriticalCSS\Queue\Web as Web_Queue;

/**
 * Class Queue
 *
 * @package WP_CriticalCSS
 * @property \WP_CriticalCSS\Plugin $plugin
 * @property  Web_Queue             $web_queue
 * @property  API_Queue             $api_queue
 */
class Queue_Manager extends Component {
	/**
	 *
	 */
	const WEB_ACTION = 'wp_criticalcss_web_check';
	/**
	 *
	 */
	const API_ACTION = 'wp_criticalcss_api';

	/**
	 * @var int
	 */
	private $current_action = 0;
	/**
	 * @var \WP_CriticalCSS\Queue\Web
	 */
	private $web_queue;
	/**
	 * @var \WP_CriticalCSS\Queue\API
	 */
	private $api_queue;

	/**
	 * Process constructor.
	 *
	 * @param \WP_CriticalCSS\API $api
	 */
	public function __construct( Web_Queue $web_queue, API_Queue $api_queue ) {
		$this->web_queue = $web_queue;
		$this->api_queue = $api_queue;
	}

	public function setup() {
		add_action( 'action_scheduler_before_process_queue', [ $this, 'prepare_queue' ] );
		add_action( 'action_scheduler_after_process_queue', [ $this, 'revert_queue' ] );
		add_action( 'action_scheduler_stored_action', [ $this, 'set_current_action' ] );
		add_action( 'action_scheduler_before_execute', [ $this, 'set_current_action' ] );
		add_action( self::WEB_ACTION, [ $this->web_queue, 'process' ] );
		add_action( self::API_ACTION, [ $this->api_queue, 'process' ] );
	}

	public function prepare_queue() {
		add_filter( 'query', [ $this, 'modify_claim_query' ] );


		$api = as_get_scheduled_actions( [
			'hook'  => self::API_ACTION,
			'group' => $this->plugin->safe_slug,
		] );

		if ( empty( $api ) ) {
			return;
		}

		add_filter( 'action_scheduler_queue_runner_batch_size', [ $this, 'limit_batch' ] );
	}

	public function revert_queue() {
		remove_filter( 'query', [ $this, 'modify_claim_query' ] );
	}

	public function modify_claim_query( $sql ) {
		if ( false !== strpos( $sql, "UPDATE {$this->wpdb->actionscheduler_actions}" ) ) {
			$sql = str_replace( 'action_id ASC', 'action_id DESC', $sql );
		}

		return $sql;
	}

	public function limit_batch() {
		return 2;
	}

	public function web_check_task( $args ) {

	}

	public function maybe_fetch_args( Abstracts\Queue $queue, array $args ) {

		if ( empty( $args['action_id'] ) ) {
			return false;
		}
		$item = $queue->get_item( $args['action_id'] );

		return $item ?: false;

	}
	/*	public function maybe_fetch_args( $args ) {
			if ( array_keys( array_keys( $args ) ) === array_keys( $args ) ) {
				$args = end( $args );
			}

			if ( ! empty( $args['args_id'] ) ) {
				$transient_name = "wp_criticalcss_queue_item_{$args['args_id']}_args";
				$stored_args    = get_transient( $transient_name );
				if ( empty( $stored_args ) ) {
					return false;
				}

				delete_transient( $transient_name );

				return $stored_args;
			}

			return $args;
		}*/

	/*public function get_item_exists( $action, $args ) {
		ksort( $args );

		if ( ( $cache = $this->get_item_cache( $action, $args ) ) !== false ) {
			return false;
		}

		$items = as_get_scheduled_actions( [
			'hook'     => $action,
			'group'    => $this->plugin->safe_slug,
			'per_page' => 0,
		] );

		foreach ( $items as $item ) {
			$item_args        = $item->get_args();
			$item_args        = end( $item_args );
			$item_args_count  = count( $item_args );
			$args_count       = count( $args );
			$found_args       = array_intersect_assoc( $item_args, $args );
			$found_args_count = count( $found_args );
			if ( $found_args ) {
				if ( $args_count === $item_args_count ) {
					if ( $found_args === $item_args ) {
						$this->set_item_cache( $action, $args, true );
						return true;
					}
				}
				if ( $args_count > $item_args_count && $found_args_count === $item_args_count ) {
					$this->set_item_cache( $action, $args, true );
					return true;
				}
				if ( $args_count < $item_args_count && $found_args_count === $args_count ) {
					$this->set_item_cache( $action, $args, true );
					return true;
				}
			}
		}
		$this->set_item_cache( $action, $args, false );
		return false;
	}

	private function get_item_cache( $action, $args ) {
		$hash = md5( serialize( $args ) );

		return get_transient( "{$this->plugin->safe_slug}_{$action}_exists_{$hash}" );
	}

	private function set_item_cache( $action, $args, $found ) {
		$hash = md5( serialize( $args ) );
		set_transient( "{$this->plugin->safe_slug}_{$action}_exists_{$hash}", $found, MINUTE_IN_SECONDS );
	}*/

	public function schedule_web_task( $args ) {
		$this->plugin->model_manager->Web_Queue->refresh_item( $args );

		return $this->schedule_task( self::WEB_ACTION, $args );
	}

	public function schedule_task( $action, $args ) {
		as_enqueue_async_action( $action, $args, $this->plugin->safe_slug );
	}

	/*	public function maybe_store_args( $args ) {
			$args        = [ $args ];
			$length_test = wp_json_encode( $args );

			if ( 191 < strlen( $length_test ) ) {
				$hash = uniqid( '', true );
				set_transient( "wp_criticalcss_queue_item_{$hash}_args", $args[0], MONTH_IN_SECONDS );
				$args[0] = [ 'args_id' => $hash ];
			}

			return $args;
		}*/

	public function schedule_api_task( $args ) {
		$action_id         = $this->plugin->model_manager->API_Queue->refresh_item( $args );
		$args['action_id'] = $action_id;
		$this->schedule_task( self::API_ACTION, [ 'action_id' => $action_id ] );

		return $this->plugin->model_manager->API_Queue->refresh_item( $args );
	}

	/**
	 * @return \WP_CriticalCSS\API
	 */
	public function get_api() {
		return $this->api;
	}
	/**
	 * @return int
	 */
	public function get_current_action() {
		return $this->current_action;
	}

	/**
	 * @param int $current_action
	 */
	public function set_current_action( $current_action ) {
		$this->current_action = $current_action;
	}

	/**
	 * @return \WP_CriticalCSS\Queue\Web
	 */
	public function get_web_queue() {
		return $this->web_queue;
	}

	/**
	 * @return \WP_CriticalCSS\Queue\API
	 */
	public function get_api_queue() {
		return $this->api_queue;
	}

	private function delete_item_cache( $action, $args ) {
		$hash = md5( serialize( $args ) );
		delete_transient( "{$this->plugin->safe_slug}_{$action}_exists_{$hash}" );
	}
}
