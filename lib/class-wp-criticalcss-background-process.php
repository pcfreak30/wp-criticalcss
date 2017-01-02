<?php

class WP_CriticalCSS_Background_Process extends WP_Background_Process {
	protected $action = 'wp_criticalcss';
	private $_ping_checked = false;

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();
		add_filter( $this->cron_interval_identifier, array( $this, 'cron_interval' ) );
		$this->schedule_event();
	}

	/**
	 * @inheritDoc
	 */
	public function get_batch() {
		$batch       = new stdClass();
		$batch->data = array();
		$batch->key  = '';
		if ( ! $this->is_queue_empty() ) {
			$batch = parent::get_batch();
		}

		return $batch;
	}

	/**
	 * @return int
	 */
	public function cron_interval() {
		return 1;
	}

	/**
	 * @inheritDoc
	 */
	public function save() {
		$save = parent::save();
		$this->schedule_event();

		return $save;
	}

	/**
	 * @return mixed
	 */
	public function get_identifier() {
		return $this->identifier;
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
		$cache = get_transient( WP_CriticalCSS::get_transient_name( $item ) );
		if ( ! empty( $cache ) ) {
			return false;
		}
		$settings = WP_CriticalCSS::get_settings();

		if ( empty( $settings ) || empty( $settings['apikey'] ) ) {
			return false;
		}

		if ( ! empty( $item['timestamp'] ) && $item['timestamp'] >= time() + ( SECOND_IN_SECONDS * 8 ) ) {
			return $item;
		}

		$api = new CriticalCSS_API( $settings['apikey'] );

		if ( ! $this->_ping_checked ) {
			if ( $api->ping() ) {
				$this->_ping_checked = true;
			} else {
				return false;
			}
		}

		$item['timestamp'] = time();
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
					WP_CriticalCSS::purge_cache( $item['type'], $item['object_id'], WP_CriticalCSS::get_permalink( $item ) );
					set_transient( WP_CriticalCSS::get_transient_name( $item ), $result->css );
				}
			}
			delete_transient( WP_CriticalCSS::get_transient_name() . '_pending' );
		} else {
			$result = $api->generate( $item );
			if ( $result instanceof WP_Error ) {
				return false;
			}
			$item['queue_id'] = $result->id;

			return $item;
		}

		return false;
	}
}