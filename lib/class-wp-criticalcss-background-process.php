<?php
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

abstract class WP_CriticalCSS_Background_Process extends WP_Background_Process {
	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();
		add_filter( $this->cron_interval_identifier, array( $this, 'cron_interval' ) );
		$this->schedule_event();
	}

	/**
	 * @inheritDoc
	 */
	public function get_batch() {

		global $wpdb;

		$batch       = new stdClass();
		$batch->data = array();
		$batch->key  = '';
		if ( ! $this->is_queue_empty() ) {
			if ( is_multisite() ) {
				$table = "{$wpdb->base_prefix}{$this->action}_queue";
			} else {
				$table = "{$wpdb->prefix}{$this->action}_queue";
			}
			$result     = $wpdb->get_row( "
			SELECT *
			FROM `{$table}`
			LIMIT 1
		" );
			$batch      = new stdClass();
			$batch->key = $result->id;
			unset( $result->id );
			$data = maybe_unserialize( $result->data );
			if ( ! is_null( $data ) ) {
				$result = (object) array_merge( (array) $result, $data );
			}
			unset( $result->data );

			$batch->data = array( (array) $result );
		}

		return $batch;
	}

	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	protected function is_queue_empty() {
		global $wpdb;

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}{$this->action}_queue`" );

		return 0 == $count;
	}

	/**
	 * @return int
	 */
	public function cron_interval() {
		return 1;
	}

	/**
	 * @return mixed
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	public function create_table() {
		global $wpdb;
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		if ( is_multisite() ) {
			$table = "{$wpdb->base_prefix}{$this->action}_queue";
		} else {
			$table = "{$wpdb->prefix}{$this->action}_queue";
		}
		$sql = "CREATE TABLE $table (
  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
  template  VARCHAR(255),
  object_id  BIGINT(10),
  type VARCHAR (10),
  url TEXT,
  data TEXT,
  ";
		if ( is_multisite() ) {
			$sql .= "blog_id BIGINT(20)";
		}
		dbDelta( "$sql
  PRIMARY KEY  (id)
) {$charset_collate};" );
	}

	public function get_item_exists( $item ) {
		global $wpdb;

		$args = array();
		if ( is_multisite() ) {
			$table = "{$wpdb->base_prefix}{$this->action}_queue";
		} else {
			$table = "{$wpdb->prefix}{$this->action}_queue";
		}
		$sql = "SELECT *
			FROM `{$table}`
			WHERE ";

		if ( ! empty( $item['template'] ) ) {
			$sql    .= '`template` = %s';
			$args[] = $item['template'];
		} else {
			if ( 'url' == $item['type'] ) {
				$sql    .= '`url` = %s';
				$args[] = $item['url'];
			} else {
				$sql    .= '`object_id` = %d AND `type` = %s';
				$args[] = $item['object_id'];
				$args[] = $item['type'];
			}
		}
		if ( is_multisite() ) {
			$sql    .= ' AND `blog_id` = %d';
			$args[] = get_current_blog_id();
		}
		$result = $wpdb->get_row( $wpdb->prepare( $sql, $args ) );

		if ( is_null( $result ) ) {
			$result = false;
		}

		return $result;
	}

	public function save() {
		global $wpdb;
		if ( is_multisite() ) {
			$table = "{$wpdb->base_prefix}{$this->action}_queue";
		} else {
			$table = "{$wpdb->prefix}{$this->action}_queue";
		}
		foreach ( $this->data as $item ) {
			$data = array_merge( array(), $item );
			unset( $data['object_id'] );
			unset( $data['type'] );
			unset( $data['url'] );
			unset( $data['template'] );
			if ( is_multisite() ) {
				unset( $data['blog_id'] );
			}
			$item['data'] = maybe_serialize( $data );
			$item         = array_diff_key( $item, $data );
			$wpdb->insert( $table, $item );
			if ( class_exists( 'WPECommon' ) ) {
				$wpdb->query( "DELETE q1 FROM $table q1, $table q2 WHERE q1.id > q2.id 
	AND (  
			(
				q1.object_id = q2.object_id AND q1.type != 'url' AND q2.type != 'url'
			) OR  
			(
				q1.url = q1.url   AND q1.type = 'url'  AND q2.type = 'url'
			)
		)" );
			}
		}
		$this->schedule_event();

		return $this;
	}

	public function update( $key, $items ) {
		global $wpdb;
		foreach ( $items as $item ) {
			$data = array_merge( array(), $item );
			unset( $data['object_id'] );
			unset( $data['type'] );
			unset( $data['url'] );
			unset( $data['template'] );
			$item['data'] = maybe_serialize( $data );
			$item         = array_diff_key( $item, $data );
			if ( ! empty( $data ) ) {
				$wpdb->update( "{$wpdb->prefix}{$this->action}_queue", $item, array( 'id' => $key ) );
			}
		}

		return $this;
	}

	public function purge() {
		global $wpdb;
		if ( is_multisite() ) {
			$table = "{$wpdb->base_prefix}{$this->action}_queue";
		} else {
			$table = "{$wpdb->prefix}{$this->action}_queue";
		}
		$wpdb->query( "TRUNCATE `{$table}`" );
	}

	public function delete( $key ) {
		global $wpdb;
		if ( is_multisite() ) {
			$table = "{$wpdb->base_prefix}{$this->action}_queue";
		} else {
			$table = "{$wpdb->prefix}{$this->action}_queue";
		}
		$wpdb->delete( $table, array( 'id' => (int) $key ) );
	}
}