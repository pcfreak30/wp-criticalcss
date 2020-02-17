<?php


namespace WP_CriticalCSS\Abstracts;


use WP_CriticalCSS\Abstracts\Model;

/**
 * Class Queue
 *
 * @package WP_CriticalCSS\Models
 * @property \WP_CriticalCSS\Plugin $plugin
 */
class Queue extends Model {

	/**
	 *
	 */
	const NAME = '';

	/**
	 * @return array|object|null
	 */
	public function get_items() {
		return array_map( [ $this, 'unformat_item' ], $this->wpdb->get_results( $this->get_select(), ARRAY_A ) );
	}

	protected function get_select( $fields = null ) {
		$sql_fields = '*';
		if ( null !== $fields ) {
			$sql_fields = $fields;
		}
		if ( \ActionScheduler_DataController::is_migration_complete() ) {
			return "SELECT {$sql_fields} FROM {$this->get_action_table()} a INNER JOIN {$this->get_table_name()} q ON q.action_id = a.action_id";
		}

		return "SELECT {$sql_fields} FROM {$this->wpdb->posts} a INNER JOIN {$this->get_table_name()} q ON q.action_id = a.ID";
	}

	protected function get_action_table() {
		$action_table = \ActionScheduler_StoreSchema::ACTIONS_TABLE;

		return $this->wpdb->$action_table;
	}

	public function get_length() {
		$length = $this->wpdb->get_col( $this->get_select( 'COUNT(*)' ) );

		return (int) end( $length );
	}

	public function get_item_exists_by_id( $id ) {
		return false !== $this->get_item( $id );
	}

	public function get_item( $id ) {
		$item = $this->wpdb->get_row( $this->wpdb->prepare( "{$this->get_select()} WHERE q.id = %d", $id ), ARRAY_A );
		if ( null === $item ) {
			return false;
		}

		return $this->unformat_item( $item );
	}

	protected function unformat_item( $item ) {
		$data = [];

		if ( ! empty( $item['data'] ) ) {
			$data = json_decode( $item['data'], true );
		}

		return array_merge( $item, $data );
	}

	/**
	 * @param array $item
	 *
	 * @return bool|false|int
	 */
	public function refresh_item( array $item ) {
		list( $where, $replacements ) = $this->get_where( $item );
		$existing = $this->wpdb->get_row( $this->wpdb->prepare( "{$this->get_select()} {$where} LIMIT 1", $replacements ) );

		$replacements['action_id'] = $this->plugin->queue->get_current_action();

		if ( ! empty( $existing ) ) {
			$result = $this->wpdb->update( $this->get_table_name(), $replacements, [ 'id' => $existing->id ] );

			return $result ? $existing->id : false;
		}

		return $this->wpdb->insert( $this->get_table_name(), $replacements );
	}

	protected function get_where( array $item ) {
		$item = $this->format_item( $item );

		foreach ( array_keys( $item ) as $key ) {
			$where [] = "q.`{$key}`= %s";
		}

		$where = implode( ' AND ', $where );

		return [ $where, $item ];
	}

	public function format_item( array $item ) {
		$keys         = array_intersect( [ 'template', 'object_id', 'type', 'url' ], array_keys( $item ) );
		$data_keys    = array_diff_key( array_keys( $item ), [ 'template', 'object_id', 'type', 'url' ] );
		$replacements = [];

		foreach ( $keys as $key ) {
			$replacements[ $key ] = $item[ $key ];
		}
		foreach ( $data_keys as $key ) {
			$replacements['data'][ $key ] = $item[ $key ];
		}

		if ( ! empty( $replacements['data'] ) ) {
			$replacements['data'] = wp_json_encode( $replacements['data'] );
		}

		return $replacements;
	}

	public function clear() {
		return $this->wpdb->query( "TRUNCATE {$this->get_table_name()}" );
	}

	public function get_item_exists( array $item ) {
		list( $where, $replacements ) = $this->get_where( $item );

		$existing = $this->wpdb->get_row( $this->wpdb->prepare( "{$this->get_select()} {$where} LIMIT 1", $replacements ) );

		return ! empty( $existing );
	}

	public function get_name() {
		return $this->plugin->safe_slug . '_' . static::NAME;
	}

	/**
	 * @return mixed|string
	 */
	protected function get_schema() {
		$charset_collate = $this->wpdb->get_charset_collate();
		$table           = $this->get_table_name();

		return "CREATE TABLE $table (
  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
  action_id  BIGINT(20),
  template  VARCHAR(255),
  object_id  BIGINT(10),
  type VARCHAR (10),
  url TEXT,
  data TEXT,
  PRIMARY KEY (id)
  ) {$charset_collate}";
	}
}
