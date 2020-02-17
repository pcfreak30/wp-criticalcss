<?php


namespace WP_CriticalCSS\Admin\UI\Table;


use ActionScheduler_Store;
use WP_CriticalCSS\Abstracts\ListTable;
use WP_CriticalCSS\API;

/**
 * Class API_Queue
 *
 * @package WP_CriticalCSS\Admin\UI\Table
 * @property \WP_CriticalCSS\Plugin $plugin
 */
class API_Queue extends ListTable {

	/**
	 *
	 */
	public function setup() {
		$this->queue = $this->plugin->model_manager->Processed_Log;
		parent::setup();
	}

	public function get_columns() {
		$columns = [
			'url'            => __( 'URL', $this->plugin->get_lang_domain() ),
			'template'       => __( 'Template', $this->plugin->get_lang_domain() ),
			'status'         => __( 'Status', $this->plugin->get_lang_domain() ),
			'queue_position' => __( 'Queue Position', $this->plugin->get_lang_domain() ),
		];
		return $columns;
	}

	/**
	 * @return mixed|void
	 */
	protected function do_prepare_items() {
		$this->items = $this->queue->get_items();
		usort( $this->items, [ $this, 'sort_items' ] );
	}

	protected function process_purge_action() {
		parent::process_purge_action();
		wp_criticalcss()->get_cache_manager()->reset_web_check_transients();
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
		$settings = $this->plugin->settings_manager->get_settings();
		if ( 'on' === $settings['template_cache'] ) {
			return __( 'N/A', $this->plugin->get_lang_domain() );
		}

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
		if ( ! empty( $data['queue_id'] ) ) {
			switch ( $data['status'] ) {
				case API::STATUS_UNKNOWN:
					return __( 'Unknown', $this->plugin->get_lang_domain() );
					break;
				case API::STATUS_QUEUED:
					return __( 'Queued', $this->plugin->get_lang_domain() );
					break;
				case API::STATUS_ONGOING:
					return __( 'In Progress', $this->plugin->get_lang_domain() );
					break;
				case API::STATUS_DONE:
					return __( 'Completed', $this->plugin->get_lang_domain() );
					break;
			}
		}

		return __( 'Pending', $this->plugin->get_lang_domain() );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_queue_position( array $item ) {
		if ( ! isset( $item['queue_id'], $item['queue_index'] ) ) {
			return __( 'N/A', $this->plugin->get_lang_domain() );
		}

		return $item['queue_index'];
	}

	protected function get_args() {
		return [
			'singular' => __( 'Queue Item', $this->plugin->get_lang_domain() ),
			'plural'   => __( 'Queue Items', $this->plugin->get_lang_domain() ),
			'ajax'     => false,
		];
	}

	/**
	 * @param array $a
	 * @param array $b
	 *
	 * @return int
	 */
	private function sort_items( $a, $b ) {
		if ( isset( $a['queue_index'] ) ) {
			if ( isset( $b['queue_index'] ) ) {
				return $a['queue_index'] > $b['queue_index'] ? 1 : - 1;
			}

			return 1;
		}

		return - 1;
	}
}
