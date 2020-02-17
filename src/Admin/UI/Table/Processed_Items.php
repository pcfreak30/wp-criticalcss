<?php


namespace WP_CriticalCSS\Admin\UI\Table;


use WP_CriticalCSS\Abstracts\ListTable;

/**
 * Class Processed_Items
 *
 * @package WP_CriticalCSS\Admin\UI\Table
 * @property \WP_CriticalCSS\Plugin $plugin
 */
class Processed_Items extends ListTable {
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
			'url'      => __( 'URL', $this->plugin->get_lang_domain() ),
			'template' => __( 'Template', $this->plugin->get_lang_domain() ),
		];

		return $columns;
	}


	/**
	 * @return mixed|void
	 */
	protected function do_prepare_items() {
		$this->items = $this->queue->get_items();
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_url( array $item ) {
		if ( ! empty( $item['template'] ) ) {
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

		if ( ! empty( $item['template'] ) ) {
			return $item['template'];
		}

		return __( 'N/A', $this->plugin->get_lang_domain() );
	}

	protected function get_args() {
		return [
			'singular' => __( 'Processed Log Item', $this->plugin->get_lang_domain() ),
			'plural'   => __( 'Processed Log Items', $this->plugin->get_lang_domain() ),
			'ajax'     => false,
		];
	}
}
