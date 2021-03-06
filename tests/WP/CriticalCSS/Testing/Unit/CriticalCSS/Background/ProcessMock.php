<?php


namespace WP\CriticalCSS\Testing\Unit\CriticalCSS\Background;


use WP\CriticalCSS\Background\ProcessAbstract;

class ProcessMock extends ProcessAbstract {
	protected $action = 'test';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		return false;
	}
}