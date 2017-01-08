<?php

class WP_CriticalCSS_API_Background_Process extends WP_CriticalCSS_Background_Process {
	protected $action = 'wp_criticalcss_api';
	private $_ping_checked = false;

	private $_batches_processed = 0;

	/**
	 * Get batch
	 *
	 * @return stdClass Return the first batch from the queue
	 */
	public function get_batch() {
		global $wpdb;

		$table        = $wpdb->options;
		$column       = 'option_name';
		$key_column   = 'option_id';
		$value_column = 'option_value';

		if ( is_multisite() ) {
			$table        = $wpdb->sitemeta;
			$column       = 'meta_key';
			$key_column   = 'meta_id';
			$value_column = 'meta_value';
		}

		$key = $this->identifier . '_batch_%';

		$query = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			FROM {$table}
			WHERE {$column} LIKE %s
			ORDER BY {$key_column} ASC
			LIMIT 1 OFFSET %d
		", $key, $this->_batches_processed ) );

		$batch       = new stdClass();
		$batch->key  = $query->$column;
		$batch->data = maybe_unserialize( $query->$value_column );
		if ( empty( $batch->data ) ) {
			$batch->data = array();
		}

		return $batch;
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
		$settings = WP_CriticalCSS::get_settings();

		if ( empty( $settings ) || empty( $settings['apikey'] ) ) {
			return false;
		}

		if ( ! empty( $item['timestamp'] ) && $item['timestamp'] + ( SECOND_IN_SECONDS * 8 ) >= time() ) {
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
				$this->_batches_processed ++;
				if ( $this->_batches_processed > $this->get_queue_item_count() ) {
					$this->_batches_processed = 0;
				}

				return $item;
			}
			if ( 'JOB_ONGOING' == $result->status || 'JOB_QUEUED' == $result->status ) {
				if ( 'JOB_QUEUED' == $result->status ) {
					$item['queue_index'] = $result->queueIndex;
				}
				$this->_batches_processed ++;
				if ( $this->_batches_processed > $this->get_queue_item_count() ) {
					$this->_batches_processed = 0;
				}

				return $item;
			}
			if ( 'JOB_DONE' == $result->status ) {
				if ( 'GOOD' == $result->resultStatus && ! empty( $result->css ) ) {
					WP_CriticalCSS::purge_cache( $item['type'], $item['object_id'], WP_CriticalCSS::get_permalink( $item ) );
					WP_CriticalCSS::set_cache( $item, $result->css );
					WP_CriticalCSS::set_hash( $item, $item['hash'] );
				}
			}
		} else {
			$result = $api->generate( $item );
			if ( $result instanceof WP_Error ) {
				return false;
			}
			$item['queue_id'] = $result->id;

			$this->_batches_processed ++;
			if ( $this->_batches_processed > $this->get_queue_item_count() ) {
				$this->_batches_processed = 0;
			}

			return $item;
		}

		return false;
	}
}