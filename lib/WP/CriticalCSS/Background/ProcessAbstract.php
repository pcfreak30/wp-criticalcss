<?php
declare( ticks=1 );

namespace WP\CriticalCSS\Background;

abstract class ProcessAbstract extends \WP_Background_Process {
	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();
		add_filter( $this->cron_interval_identifier, [
			$this,
			'cron_interval',
		] );
		remove_action( 'wp_ajax_' . $this->identifier, array( $this, 'maybe_handle' ) );
		remove_action( 'wp_ajax_nopriv_' . $this->identifier, array( $this, 'maybe_handle' ) );
		$this->schedule_event();
		if ( function_exists( 'pcntl_signal' ) ) {
			pcntl_signal( SIGINT, [ $this, 'handle_interrupt' ] );
		}
		add_action( 'shutdown', [ $this, 'unlock_process' ] );
	}

	public function handle_interrupt() {
		do_action( 'shutdown' );
		exit;
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
		if ( ! function_exists( 'dbDelta' ) ) {
			include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();
		$table           = $this->get_table_name();
		$sql             = "CREATE TABLE $table (
  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
  template  VARCHAR(255),
  object_id  BIGINT(10),
  type VARCHAR (10),
  url TEXT,
  data TEXT,";
		if ( is_multisite() ) {
			$sql .= "\n" . 'blog_id BIGINT(20),';
		}
		dbDelta( "$sql\nPRIMARY KEY  (id)\n) {$charset_collate};" );
	}

	protected function get_table_name() {
		global $wpdb;
		if ( is_multisite() ) {
			return "{$wpdb->base_prefix}{$this->action}_queue";
		}

		return "{$wpdb->prefix}{$this->action}_queue";

	}

	public function get_item_exists( $item ) {
		global $wpdb;

		$args  = [];
		$table = $this->get_table_name();
		$sql   = "SELECT *
			FROM `{$table}`
			WHERE ";

		if ( ! empty( $item['template'] ) ) {
			$sql    .= '`template` = %s';
			$args[] = $item['template'];
		} else {
			if ( 'url' === $item['type'] ) {
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

		if ( null === $result ) {
			$result = false;
		}

		return $result;
	}

	public function save() {
		global $wpdb;
		$table = $this->get_table_name();
		foreach ( $this->data as $item ) {
			$data = array_merge( [], $item );
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

		$this->data = [];

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

	public function handle_cron_healthcheck() {
		if ( $this->is_process_running() ) {
			// Background process already running.
			return;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();

			return;
		}

		$this->handle();

		if ( 'cli' !== php_sapi_name() ) {
			exit;
		}

	}

	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	protected function is_queue_empty() {
		$count = $this->get_length();

		return 0 == $count;
	}

	public function get_length() {
		global $wpdb;
		$table = $this->get_table_name();
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		return $count;
	}

	protected function handle() {
		$this->lock_process();
		$batch          = $this->get_batch();
		$original_batch = clone $batch;
		$cli            = 'cli' === php_sapi_name();
		do {
			if ( ! $batch ) {
				$batch          = $this->get_batch();
				$original_batch = clone $batch;
			}
			foreach ( $batch->data as $key => $value ) {
				$task = $this->task( $value );

				if ( false !== $task ) {
					$batch->data[ $key ] = $task;
				} else {
					unset( $batch->data[ $key ] );
				}

				if ( ( $this->time_exceeded() || $this->memory_exceeded() ) && ! $cli ) {
					// Batch limits reached.
					break;
				}

				sleep( apply_filters( 'wp_criticalcss_process_delay', $task ? 5.0 : 0.5 ) );
			}

			// Update or delete current batch.
			if ( ! empty( $batch->data ) ) {
				if ( serialize( $original_batch ) !== serialize( $batch ) ) {
					$this->update( $batch->key, $batch->data );
					$original_batch = $batch;
				}

			} else {
				$this->delete( $batch->key );
				$original_batch = null;
				$batch          = null;
			}

			sleep( apply_filters( 'wp_criticalcss_process_delay', 0.5 ) );
		} while ( ! $this->is_queue_empty() && $cli );

		$this->unlock_process();

		// Start next batch or complete process.
		if ( ! $this->is_queue_empty() ) {
			$this->dispatch();
		} else {
			$this->complete();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_batch() {

		global $wpdb;

		$batch       = new \stdClass();
		$batch->data = [];
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
			$batch      = new \stdClass();
			$batch->key = $result->id;
			unset( $result->id );
			$data = maybe_unserialize( $result->data );
			if ( null !== $data ) {
				$result = (object) array_merge( (array) $result, $data );
			}
			unset( $result->data );

			$batch->data = [ (array) $result ];
		}

		return $batch;
	}

	public function update( $key, $items ) {
		global $wpdb;
		$table = $this->get_table_name();
		foreach ( $items as $item ) {
			$data = array_merge( [], $item );
			unset( $data['object_id'] );
			unset( $data['type'] );
			unset( $data['url'] );
			unset( $data['template'] );
			$item['data'] = maybe_serialize( $data );
			$item         = array_diff_key( $item, $data );
			if ( ! empty( $data ) ) {
				$wpdb->update( $table, $item, [
					'id' => $key,
				] );
			}
		}

		return $this;
	}

	public function delete( $key ) {
		global $wpdb;

		$wpdb->delete( $this->get_table_name(), [
			'id' => (int) $key,
		] );
	}

	public function unlock_process() {
		return parent::unlock_process();
	}

	public function dispatch() {
		$this->schedule_event();
	}
}
