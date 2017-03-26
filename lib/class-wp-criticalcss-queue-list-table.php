<?php
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * Class CriticalCSS_Queue_List_Table
 */
class WP_CriticalCSS_Queue_List_Table extends WP_List_Table {
	/**
	 * @var \WP_CriticalCSS_API_Background_Process
	 */
	private $_api_queue;

	/**
	 * CriticalCSS_Queue_List_Table constructor.
	 *
	 * @param \WP_CriticalCSS_Background_Process $background_queue
	 */
	public function __construct( WP_CriticalCSS_API_Background_Process $api_queue ) {
		$this->_api_queue = $api_queue;
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
			'template'       => __( 'Template', WP_CriticalCSS::LANG_DOMAIN ),
			'status'         => __( 'Status', WP_CriticalCSS::LANG_DOMAIN ),
			'queue_position' => __( 'Queue Position', WP_CriticalCSS::LANG_DOMAIN ),
		);
		if ( is_multisite() ) {
			$columns = array_merge( array( 'blog_id' => __( 'Blog', WP_CriticalCSS::LANG_DOMAIN ) ), $columns );
		}

		return $columns;
	}

	/**
	 *
	 */
	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = $this->get_column_info();
		$this->_process_bulk_action();

		$per_page = $this->get_items_per_page( 'queue_items_per_page', 20 );

		if ( is_multisite() ) {
			$table = "{$wpdb->base_prefix}wp_criticalcss_api_queue";
		} else {
			$table = "{$wpdb->prefix}wp_criticalcss_api_queue";
		}

		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}wp_criticalcss_api_queue" );

		$paged = $this->get_pagenum();
		$start = ( $paged - 1 ) * $per_page;

		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} LIMIT %d,%d", $start, $per_page ), ARRAY_A );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	private function _process_bulk_action() {
		if ( 'purge' == $this->current_action() ) {
			WP_CriticalCSS::get_api_queue()->purge();
			WP_CriticalCSS::reset_web_check_transients();
		}
	}

	protected function get_bulk_actions() {
		return array( 'purge' => __( 'Purge', WP_CriticalCSS::LANG_DOMAIN ) );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_blog_id( array $item ) {
		if ( empty( $item['blog_id'] ) ) {
			return __( 'N/A', WP_CriticalCSS::LANG_DOMAIN );
		}

		$details = get_blog_details( array( 'blog_id' => $item['blog_id'] ) );

		if ( empty( $details ) ) {
			return __( 'Blog Deleted', WP_CriticalCSS::LANG_DOMAIN );
		}

		return $details->blogname;
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_url( array $item ) {
		$settings = WP_CriticalCSS::get_settings();
		if ( 'on' == $settings['template_cache'] ) {
			return __( 'N/A', WP_CriticalCSS::LANG_DOMAIN );
		}

		return WP_CriticalCSS::get_permalink( $item );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_template( array $item ) {
		$settings = WP_CriticalCSS::get_settings();
		if ( 'on' == $settings['template_cache'] ) {
			if ( ! empty( $item['template'] ) ) {
				return $item['template'];
			}

			return __( 'N/A', WP_CriticalCSS::LANG_DOMAIN );
		}

		return WP_CriticalCSS::get_permalink( $item );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_status( array $item ) {
		$data = maybe_unserialize( $item['data'] );
		if ( ! empty( $data ) ) {
			if ( ! empty( $data['queue_id'] ) ) {
				switch ( $data['status'] ) {
					case WP_CriticalCSS_API::STATUS_UNKNOWN:
						return __( 'Unknown', WP_CriticalCSS::LANG_DOMAIN );
						break;
					case WP_CriticalCSS_API::STATUS_QUEUED:
						return __( 'Queued', WP_CriticalCSS::LANG_DOMAIN );
						break;
					case WP_CriticalCSS_API::STATUS_ONGOING:
						return __( 'In Progress', WP_CriticalCSS::LANG_DOMAIN );
						break;
					case WP_CriticalCSS_API::STATUS_DONE:
						return __( 'Completed', WP_CriticalCSS::LANG_DOMAIN );
						break;
				}
			} else {
				switch ( $data['status'] ) {
					case WP_CriticalCSS_API::STATUS_UNKNOWN:
						return __( 'Unknown', WP_CriticalCSS::LANG_DOMAIN );
						break;
					default:
						return __( 'Pending', WP_CriticalCSS::LANG_DOMAIN );
				}
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