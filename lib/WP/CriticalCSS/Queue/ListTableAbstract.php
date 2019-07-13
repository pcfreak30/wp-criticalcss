<?php

namespace WP\CriticalCSS\Queue;

use WP\CriticalCSS\Background\ProcessAbstract;

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * Class ListTable
 */
abstract class ListTableAbstract extends \WP_List_Table {
	/**
	 *
	 */
	const TABLE_NAME = '';
	/**
	 * @var int
	 */
	protected $per_page = 0;
	/**
	 * @var int
	 */
	protected $start = 0;
	/**
	 * @var int
	 */
	protected $total_items = 0;
	/**
	 * @var ProcessAbstract
	 */
	protected $queue;

	protected $args = [];

	/**
	 * ListTable constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = [] ) {
		$this->args = $args;
	}

	public function display() {
		$this->_column_headers = [ $this->get_columns() ];
		parent::display();
	}

	public function init() {
		add_screen_option( 'per_page', [
			'label'   => ucfirst( static::TABLE_NAME ) . ' Queue Items',
			'default' => 20,
			'option'  => static::TABLE_NAME . '_queue_items_per_page',
		] );
		parent::__construct( $this->args );
	}

	/**
	 *
	 */
	public function no_items() {
		_e( 'Nothing in the queue.', 'sp' );
	}

	/**
	 * @param ProcessAbstract $queue
	 */
	public function set_queue( $queue ) {
		$this->queue = $queue;
	}

	/**
	 *
	 */
	public function prepare_items() {
		$this->pre_prepare_items();
		$this->do_prepare_items();
		$this->post_prepare_items();
	}

	/**
	 *
	 */
	protected function pre_prepare_items() {
		$this->_column_headers = $this->get_column_info();
		$this->process_bulk_action();
		$table             = $this->get_table_name();
		$this->total_items = wp_criticalcss()->wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
		$this->per_page    = $this->total_items;
		if ( ! $this->per_page ) {
			$this->per_page = 1;
		}
		$this->start = 0;
	}

	/**
	 * @return void
	 */
	protected function process_bulk_action() {
		if ( 'purge' === $this->current_action() ) {
			$this->process_purge_action();
		}
	}

	protected function process_purge_action() {
		$this->queue->purge();
	}

	/**
	 * @return string
	 */
	protected function get_table_name() {
		$wpdb       = wp_criticalcss()->wpdb;
		$table_name = static::TABLE_NAME;
		if ( is_multisite() ) {
			return "{$wpdb->base_prefix}wp_criticalcss_{$table_name}_queue";
		}

		return "{$wpdb->prefix}wp_criticalcss_{$table_name}_queue";
	}

	/**
	 * @return mixed
	 */
	abstract protected function do_prepare_items();

	/**
	 *
	 */
	protected function post_prepare_items() {
		$this->set_pagination_args( [
			'total_items' => $this->total_items,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $this->total_items / $this->per_page ),
		] );
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		return [
			'purge' => __( 'Purge', wp_criticalcss()->get_lang_domain() ),
		];
	}


}
