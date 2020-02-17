<?php


namespace WP_CriticalCSS\Models;


use WP_CriticalCSS\Abstracts\Model;
use WP_CriticalCSS\Abstracts\Queue;

class Template_Log extends Model {
	const TABLE_NAME = 'template_log';

	public function get_entries_by_template( $template ) {
		if ( is_array( $template ) ) {
			$template = wp_json_encode( $template );
		}

		return $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM {$this->get_table_name()} WHERE `template` = %s", $template ) );
	}

	protected function get_schema() {
		$charset_collate = $this->wpdb->get_charset_collate();
		$table           = $this->get_table_name();

		return "CREATE TABLE $table (
  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
  template  VARCHAR(255),
  object_id  BIGINT(10),
  type VARCHAR (10),
  url TEXT,
  ) {$charset_collate}";
	}
}
