<?php


namespace WP\CriticalCSS\Testing\Admin;


use WP\CriticalCSS\Admin\UI;

class UIMock extends UI {
	/**
	 * @return \WP\CriticalCSS\Queue\ListTableAbstract
	 */
	public function get_queue_table() {
		return $this->queue_table;
	}

}