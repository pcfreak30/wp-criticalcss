<?php


namespace WP\CriticalCSS;

use pcfreak30\WordPress\Plugin\Framework\ComponentAbstract;

class Log extends ComponentAbstract {

	/**
	 *
	 */
	public function init() {

	}

	public function create_table() {
		$wpdb = $this->wpdb;
		if ( ! function_exists( 'dbDelta' ) ) {
			include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();
		if ( is_multisite() ) {
			$table = "{$wpdb->base_prefix}{$this->plugin->get_safe_slug()}_processed_items";
		} else {
			$table = "{$wpdb->prefix}{$this->plugin->get_safe_slug()}";
		}
		$sql = "CREATE TABLE $table (
  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
  template  VARCHAR(255),
  object_id  BIGINT(10),
  type VARCHAR (10),
  url TEXT,";
		if ( is_multisite() ) {
			$sql .= "\n" . 'blog_id BIGINT(20),';
		}
		dbDelta( "$sql\nPRIMARY KEY  (id)\n) {$charset_collate};" );
	}

	public function insert( $item ) {
		$wpdb = $this->wpdb;

		$data = array_merge( [], $item );
		unset( $data['object_id'] );
		unset( $data['type'] );
		unset( $data['url'] );
		unset( $data['template'] );
		if ( is_multisite() ) {
			unset( $data['blog_id'] );
		}
		$wpdb->insert( $this->get_table_name(), $item );
	}

	public function get_table_name() {
		$wpdb = $this->wpdb;
		if ( is_multisite() ) {
			$table = "{$wpdb->base_prefix}{$this->plugin->get_safe_slug()}_processed_items";
		} else {
			$table = "{$wpdb->prefix}{$this->plugin->get_safe_slug()}";
		}

		return $table;
	}

	public function purge() {
		$this->wpdb->query( "TRUNCATE TABLE {$this->get_table_name()}" );
	}
}