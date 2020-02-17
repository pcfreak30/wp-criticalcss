<?php


namespace WP_CriticalCSS\Interfaces;


/**
 * Interface QueueTaskInterface
 *
 * @package WP_CriticalCSS\Interfaces
 */
interface QueueTaskInterface {
	/**
	 * @param array $item
	 *
	 * @return void
	 */
	public function process( array $item );
}
