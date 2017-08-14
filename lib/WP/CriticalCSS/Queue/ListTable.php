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
			'url'            => __( 'URL', wp_criticalcss()->get_lang_domain() ),
			'template'       => __( 'Template', wp_criticalcss()->get_lang_domain() ),
			'status'         => __( 'Status', wp_criticalcss()->get_lang_domain() ),
			'queue_position' => __( 'Queue Position', wp_criticalcss()->get_lang_domain() ),
		];
		if ( is_multisite() ) {
			$columns = array_merge( [
				'blog_id' => __( 'Blog', wp_criticalcss()->get_lang_domain() ),
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

		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY LOCATE('queue_id', {$table}.data) DESC, LOCATE('queue_index', {$table}.data) DESC LIMIT %d,%d", $start, $per_page ), ARRAY_A );

		usort( $this->items, [ $this, 'sort_items' ] );

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		] );
	}

	private function _process_bulk_action() {
		if ( 'purge' === $this->current_action() ) {
			wp_criticalcss()->get_api_queue()->purge();
			wp_criticalcss()->get_cache_manager()->reset_web_check_transients();
		}
	}

	protected function get_bulk_actions() {
		return [
			'purge' => __( 'Purge', wp_criticalcss()->get_lang_domain() ),
		];
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_blog_id( array $item ) {
		if ( empty( $item['blog_id'] ) ) {
			return __( 'N/A', wp_criticalcss()->get_lang_domain() );
		}

		$details = get_blog_details( [
			'blog_id' => $item['blog_id'],
		] );

		if ( empty( $details ) ) {
			return __( 'Blog Deleted', wp_criticalcss()->get_lang_domain() );
		}

		return $details->blogname;
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_url( array $item ) {
		$settings = wp_criticalcss()->get_settings_manager()->get_settings();
		if ( 'on' === $settings['template_cache'] ) {
			return __( 'N/A', wp_criticalcss()->get_lang_domain() );
		}

		return wp_criticalcss()->get_permalink( $item );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_template( array $item ) {
		$settings = wp_criticalcss()->get_settings_manager()->get_settings();
		if ( 'on' === $settings['template_cache'] ) {
			if ( ! empty( $item['template'] ) ) {
				return $item['template'];
			}
		}

		return __( 'N/A', wp_criticalcss()->get_lang_domain() );
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
						return __( 'Unknown', wp_criticalcss()->get_lang_domain() );
						break;
					case CriticalCSS\API::STATUS_QUEUED:
						return __( 'Queued', wp_criticalcss()->get_lang_domain() );
						break;
					case CriticalCSS\API::STATUS_ONGOING:
						return __( 'In Progress', wp_criticalcss()->get_lang_domain() );
						break;
					case CriticalCSS\API::STATUS_DONE:
						return __( 'Completed', wp_criticalcss()->get_lang_domain() );
						break;
				}
			} else {
				if ( empty( $data['status'] ) ) {
					return __( 'Pending', wp_criticalcss()->get_lang_domain() );
				}
				switch ( $data['status'] ) {
					case CriticalCSS\API::STATUS_UNKNOWN:
						return __( 'Unknown', wp_criticalcss()->get_lang_domain() );
						break;
					default:
						return __( 'Pending', wp_criticalcss()->get_lang_domain() );
				}
			}
		} else {
			return __( 'Pending', wp_criticalcss()->get_lang_domain() );
		}
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_queue_position( array $item ) {
		$data = maybe_unserialize( $item['data'] );
		if ( ! isset( $data['queue_id'], $data['queue_index'] ) ) {
			return __( 'N/A', wp_criticalcss()->get_lang_domain() );
		}

		return $data['queue_index'];
	}

	/**
	 * @param array $a
	 * @param array $b
	 *
	 * @return int
	 */
	private function sort_items( $a, $b ) {
		$a['data'] = maybe_unserialize( $a['data'] );
		$b['data'] = maybe_unserialize( $b['data'] );
		if ( isset( $a['data']['queue_index'] ) ) {
			if ( isset( $b['data']['queue_index'] ) ) {
				return $a['data']['queue_index'] > $b['data']['queue_index'] ? 1 : - 1;
			}

			return 1;
		}

		return - 1;
	}
}
