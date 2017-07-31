<?php

namespace WP\CriticalCSS\Queue;

use WP\CriticalCSS;
use WP\CriticalCSS\API\Background\Process;

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * Class ListTable
 */
class ListTable extends \WP_List_Table {
	/**
	 * @var Process
	 */
	private $_api_queue;

	/**
	 * ListTable constructor.
	 *
	 * @param \WP\CriticalCSS\API\Background\Process $api_queue
	 *
	 */
	public function __construct( Process $api_queue ) {
		$this->_api_queue = $api_queue;
		parent::__construct( [
			'singular' => __( 'Queue Item', 'criticalcss' ),
			'plural'   => __( 'Queue Items', 'criticalcss' ),
			'ajax'     => false,
		] );
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
	public function get_columns() {
		$columns = [
			'url'            => __( 'URL', CriticalCSS::LANG_DOMAIN ),
			'template'       => __( 'Template', CriticalCSS::LANG_DOMAIN ),
			'status'         => __( 'Status', CriticalCSS::LANG_DOMAIN ),
			'queue_position' => __( 'Queue Position', CriticalCSS::LANG_DOMAIN ),
		];
		if ( is_multisite() ) {
			$columns = array_merge( [
				'blog_id' => __( 'Blog', CriticalCSS::LANG_DOMAIN ),
			], $columns );
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

		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );

		$paged = $this->get_pagenum();
		$start = ( $paged - 1 ) * $per_page;

		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} LIMIT %d,%d", $start, $per_page ), ARRAY_A );

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		] );
	}

	private function _process_bulk_action() {
		if ( 'purge' == $this->current_action() ) {
			WPCCSS()->get_api_queue()->purge();
			WPCCSS()->get_cache_manager()->reset_web_check_transients();
		}
	}

	protected function get_bulk_actions() {
		return [
			'purge' => __( 'Purge', CriticalCSS::LANG_DOMAIN ),
		];
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_blog_id( array $item ) {
		if ( empty( $item['blog_id'] ) ) {
			return __( 'N/A', CriticalCSS::LANG_DOMAIN );
		}

		$details = get_blog_details( [
			'blog_id' => $item['blog_id'],
		] );

		if ( empty( $details ) ) {
			return __( 'Blog Deleted', CriticalCSS::LANG_DOMAIN );
		}

		return $details->blogname;
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_url( array $item ) {
		$settings = WPCCSS()->get_settings_manager()->get_settings();
		if ( 'on' === $settings['template_cache'] ) {
			return __( 'N/A', CriticalCSS::LANG_DOMAIN );
		}

		return WPCCSS()->get_permalink( $item );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_template( array $item ) {
		$settings = WPCCSS()->get_settings_manager()->get_settings();
		if ( 'on' === $settings['template_cache'] ) {
			if ( ! empty( $item['template'] ) ) {
				return $item['template'];
			}
		}

		return __( 'N/A', CriticalCSS::LANG_DOMAIN );
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
					case CriticalCSS\API::STATUS_UNKNOWN:
						return __( 'Unknown', CriticalCSS::LANG_DOMAIN );
						break;
					case CriticalCSS\API::STATUS_QUEUED:
						return __( 'Queued', CriticalCSS::LANG_DOMAIN );
						break;
					case CriticalCSS\API::STATUS_ONGOING:
						return __( 'In Progress', CriticalCSS::LANG_DOMAIN );
						break;
					case CriticalCSS\API::STATUS_DONE:
						return __( 'Completed', CriticalCSS::LANG_DOMAIN );
						break;
				}
			} else {
				if ( empty( $data['status'] ) ) {
					return __( 'Pending', CriticalCSS::LANG_DOMAIN );
				}
				switch ( $data['status'] ) {
					case CriticalCSS\API::STATUS_UNKNOWN:
						return __( 'Unknown', CriticalCSS::LANG_DOMAIN );
						break;
					default:
						return __( 'Pending', CriticalCSS::LANG_DOMAIN );
				}
			}
		} else {
			return __( 'Pending', CriticalCSS::LANG_DOMAIN );
		}
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_queue_position( array $item ) {
		if ( ! isset( $item['queue_id'] ) || ! isset( $item['queue_index'] ) ) {
			return __( 'N/A', CriticalCSS::LANG_DOMAIN );
		}

		return $item['queue_index'];
	}
}
