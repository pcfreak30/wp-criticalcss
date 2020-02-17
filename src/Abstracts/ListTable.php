<?php

namespace WP_CriticalCSS\Abstracts;


require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

use ComposePress\Core\Traits\Component_0_8_0_0;

abstract class ListTable extends \WP_List_Table {
	use Component_0_8_0_0;

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
	 * @var \WP_CriticalCSS\Abstracts\Queue
	 */
	protected $queue;

	/**
	 * @var string
	 */
	protected $current_action_status;

	/**
	 * ListTable constructor.
	 *
	 * @param array $args
	 */

	public function display() {
		$this->_column_headers = [ $this->get_columns() ];
		parent::display();
	}

	public function __construct( $args = array() ) {

	}

	public function setup() {
		add_action( 'action_scheduler_stored_action_class', [ $this, 'set_current_action_status' ], 10, 2 );
		add_action( 'current_screen', [ $this, 'prepare_items' ] );
	}

	public function set_current_action_status( $class, $status ) {
		$this->current_action_status = $status;

		return $status;
	}

	/**
	 *
	 */
	public function no_items() {
		_e( 'Nothing in the queue.', 'sp' );
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
		parent::__construct( $this->get_args() );
		$this->_column_headers = $this->get_column_info();
		$this->process_bulk_action();
		$this->total_items = $this->queue->get_length();
		$this->per_page    = $this->total_items;
		if ( ! $this->per_page ) {
			$this->per_page = 1;
		}
	}

	/**
	 * @return array
	 */
	abstract protected function get_args();

	/**
	 * @return void
	 */
	protected function process_bulk_action() {
		if ( 'purge' === $this->current_action() ) {
			$this->process_purge_action();
		}
	}

	protected function process_purge_action() {
		$this->queue->clear();
	}

	/**
	 * @return void
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
	 * @return \WP_CriticalCSS\Abstracts\Queue
	 */
	public function get_queue() {
		return $this->queue;
	}

	/**
	 * @param \WP_CriticalCSS\Abstracts\Queue $queue
	 */
	public function set_queue( $queue ) {
		$this->queue = $queue;
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		return [
			'purge' => __( 'Purge', $this->plugin->safe_slug ),
		];
	}


}
