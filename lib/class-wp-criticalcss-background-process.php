<?php

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
		$batch       = new stdClass();
		$batch->data = array();
		$batch->key  = '';
		if ( ! $this->is_queue_empty() ) {
			$batch = parent::get_batch();
		}

		return $batch;
	}

	/**
	 * @return int
	 */
	public function cron_interval() {
		return 1;
	}

	/**
	 * @inheritDoc
	 */
	public function save() {
		$save = parent::save();
		$this->schedule_event();

		return $save;
	}

	/**
	 * @return mixed
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	protected function get_queue_item_count() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $this->identifier . '_batch_%';

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}
			WHERE {$column} LIKE %s
		", $key ) );

		return $count;
	}
}