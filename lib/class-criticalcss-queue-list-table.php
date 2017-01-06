<?php

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * Class CriticalCSS_Queue_List_Table
 */
class CriticalCSS_Queue_List_Table extends WP_List_Table {
	/**
	 * @var \WP_CriticalCSS_Background_Process
	 */
	private $_background_queue;

	/**
	 * CriticalCSS_Queue_List_Table constructor.
	 *
	 * @param \WP_CriticalCSS_Background_Process $background_queue
	 */
	public function __construct( WP_CriticalCSS_Background_Process $background_queue ) {
		$this->_background_queue = $background_queue;
		parent::__construct( array(
			'singular' => __( 'Queue Item', 'criticalcss' ),
			'plural'   => __( 'Queue Items', 'criticalcss' ),
			'ajax'     => false,
		) );
	}

	/**
	 *
	 */
	public function no_items() {
		_e( 'Nothing in the queue.', 'sp' );
	}

	/**
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'url'            => __( 'URL', WP_CriticalCSS::LANG_DOMAIN ),
			'status'         => __( 'Status', WP_CriticalCSS::LANG_DOMAIN ),
			'queue_position' => __( 'Queue Position', WP_CriticalCSS::LANG_DOMAIN ),
		);

		return $columns;
	}

	/**
	 *
	 */
	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = $this->get_column_info();
		$this->_process_bulk_action();

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

		$key = $this->_background_queue->get_identifier() . '_batch_%';

		$query = $wpdb->get_results( $wpdb->prepare( "
			SELECT *
			FROM {$table}
			WHERE {$column} LIKE %s
			GROUP BY {$value_column}
			ORDER BY {$key_column} ASC
		", $key ) );

		$pending_batches = array();
		$new_batches     = array();
		foreach ( $query as $item ) {
			$item = unserialize( $item->option_value );
			$item = end( $item );
			if ( isset( $item['queue_id'] ) && ! isset( $pending_batchess[ $item['queue_id'] ] ) ) {
				$pending_batches[ $item['queue_id'] ] = $item;
			} else {
				$new_batches[] = $item;
			}
		}


		$this->items = array_merge( array_values( $pending_batches ), $new_batches );

		$per_page     = $this->get_items_per_page( 'queue_items_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = count( $this->items );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
		) );

		$start = ( $current_page - 1 ) * $per_page;
		$end   = $total_items;
		if ( $per_page < $total_items ) {
			$end = $per_page * $current_page;
			if ( $end > $total_items ) {
				$end = $total_items;
			}
		}
		$this->items = array_slice( $this->items, $start, $end - $start );
	}

	private function _process_bulk_action() {
		if ( 'purge' == $this->current_action() ) {
			$queue = new WP_CriticalCSS_Background_Process();
			while ( ( $item = $queue->get_batch() ) && ! empty( $item->data ) ) {
				$queue->delete( $item->key );
			}
		}
	}

	protected function get_bulk_actions() {
		return array( 'purge' => __( 'Purge', WP_CriticalCSS::LANG_DOMAIN ) );
	}

	/**
	 * @param array $item
	 *
	 * @return false|mixed|string|\WP_Error
	 */
	protected function column_url( array $item ) {
		return WP_CriticalCSS::get_permalink( $item );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_status( array $item ) {
		if ( ! empty( $item['queue_id'] ) ) {
			switch ( $item['status'] ) {
				case 'JOB_UNKNOWN':
					return __( 'Unknown', WP_CriticalCSS::LANG_DOMAIN );
					break;
				case 'JOB_QUEUED':
					return __( 'Queued', WP_CriticalCSS::LANG_DOMAIN );
					break;
				case 'JOB_ONGOING':
					return __( 'In Progress', WP_CriticalCSS::LANG_DOMAIN );
					break;
				case 'JOB_DONE':
					return __( 'Completed', WP_CriticalCSS::LANG_DOMAIN );
					break;
			}
		} else {
			return __( 'Pending', WP_CriticalCSS::LANG_DOMAIN );
		}
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_queue_position( array $item ) {
		if ( ! isset( $item['queue_id'] ) || ! isset( $item['queue_index'] ) ) {
			return __( 'N/A', WP_CriticalCSS::LANG_DOMAIN );
		}

		return $item['queue_index'];
	}
}