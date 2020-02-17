<?php


namespace WP_CriticalCSS\Abstracts;


use WP_CriticalCSS\Core\Component;

/**
 * Class Model
 *
 * @package WP_CriticalCSS\Abstracts
 * @property-read \wpdb $wpdb
 */
abstract class Model extends Component {
	/**
	 *
	 */
	const TABLE_NAME = '';

	/**
	 *
	 */
	public function install() {
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $this->get_schema() );
	}

	/**
	 * @return mixed
	 */
	abstract protected function get_schema();

	public function get_table_name() {
		return $this->wpdb->prefix . $this->plugin->safe_slug . '_'. static::TABLE_NAME;
	}
	/**
	 * @param $id
	 *
	 * @return bool|false|int
	 */
	public function delete_item( $id ) {
		return $this->delete( [ 'id' => (int) $id ] );
	}

	public function delete( array $where ) {
		return $this->wpdb->delete( $this->get_table_name(), $where );
	}

	public function insert( array $data ) {
		return $this->wpdb->insert( $this->get_table_name(), $data );
	}
}
