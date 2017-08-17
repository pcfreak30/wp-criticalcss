<?php

namespace WP\CriticalCSS\Testing\Integration\CriticalCSS\Cache;

use WP\CriticalCSS\Testing\Integration\TestCase;

class ManagerTest extends TestCase {

	public function test_init_template_cache_off() {
		wp_criticalcss()->init();
		$this->assertEquals(
			10, has_action(
				'post_updated', [
					wp_criticalcss()->get_cache_manager(),
					'reset_web_check_post_transient',
				]
			)
		);
		$this->assertEquals(
			10, has_action(
				'edited_term', [
					wp_criticalcss()->get_cache_manager(),
					'reset_web_check_term_transient',
				]
			)
		);
	}
}

