<?php


namespace WP\CriticalCSS\Queue\Log;


use WP\CriticalCSS\Queue\ListTableAbstract;

class Table extends ListTableAbstract {
	public function __construct( array $args = [] ) {
		parent::__construct( [
			'singular' => __( 'Processed Log Item', wp_criticalcss()->get_lang_domain() ),
			'plural'   => __( 'Processed Log Items', wp_criticalcss()->get_lang_domain() ),
			'ajax'     => false,
		] );
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'url'      => __( 'URL', wp_criticalcss()->get_lang_domain() ),
			'template' => __( 'Template', wp_criticalcss()->get_lang_domain() ),
		];
		if ( is_multisite() ) {
			$columns = array_merge( [
				'blog_id' => __( 'Blog', wp_criticalcss()->get_lang_domain() ),
			], $columns );
		}

		return $columns;
	}

	protected function get_bulk_actions() {
		return [];
	}

	protected function do_prepare_items() {
		$wpdb        = wp_criticalcss()->wpdb;
		$table       = $this->get_table_name();
		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} LIMIT %d,%d", $this->start, $this->per_page ), ARRAY_A );
	}

	protected function get_table_name() {
		return wp_criticalcss()->log->get_table_name();
	}

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
		if ( ! empty( $item['template'] ) ) {
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

		if ( ! empty( $item['template'] ) ) {
			return $item['template'];
		}

		return __( 'N/A', wp_criticalcss()->get_lang_domain() );
	}
}