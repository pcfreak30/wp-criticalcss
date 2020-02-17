<?php


namespace WP_CriticalCSS\Admin\UI\Table;


use ActionScheduler_Store;
use WP_CriticalCSS\Abstracts\ListTable;
use WP_CriticalCSS\API;

/**
 * Class Web_Queue
 *
 * @package WP_CriticalCSS\Admin\UI\Table
 * @property \WP_CriticalCSS\Plugin $plugin
 */
class Web_Queue extends ListTable {
	/**
	 *
	 */
	public function setup() {
		$this->queue = $this->plugin->model_manager->Processed_Log;

		parent::setup();
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'url'    => __( 'URL', $this->plugin->get_lang_domain() ),
			'status' => __( 'Status', $this->plugin->get_lang_domain() ),
		];

		return $columns;
	}

	/**
	 * @return mixed|void
	 */
	protected function do_prepare_items() {
		$this->items = $this->queue->get_items();
	}

	protected function process_purge_action() {
		parent::process_purge_action();
		$this->plugin->cache_manager->reset_web_check_transients();
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_blog_id( array $item ) {
		if ( empty( $item['blog_id'] ) ) {
			return __( 'N/A', $this->plugin->get_lang_domain() );
		}

		$details = get_blog_details( [
			'blog_id' => $item['blog_id'],
		] );

		if ( empty( $details ) ) {
			return __( 'Blog Deleted', $this->plugin->get_lang_domain() );
		}

		return $details->blogname;
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_url( array $item ) {
		return $this->plugin->get_permalink( $item );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_template( array $item ) {
		$settings = $this->plugin->settings_manager->get_settings();
		if ( 'on' === $settings['template_cache'] ) {
			if ( ! empty( $item['template'] ) ) {
				return $item['template'];
			}
		}

		return __( 'N/A', $this->plugin->get_lang_domain() );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_status( array $item ) {
		ActionScheduler_Store::instance()->fetch_action( $item['action_id'] );
		switch ( $this->current_action_status ) {
			case ActionScheduler_Store::STATUS_RUNNING:
				return __( 'Processing', $this->plugin->get_lang_domain() );
				break;
			default:
				return __( 'Pending', $this->plugin->get_lang_domain() );
		}
	}

	protected function get_args() {
		return [
			'singular' => __( 'Web Check Queue Item', $this->plugin->get_lang_domain() ),
			'plural'   => __( 'Web Check Queue Queue Items', $this->plugin->get_lang_domain() ),
			'ajax'     => false,
		];
	}
}
