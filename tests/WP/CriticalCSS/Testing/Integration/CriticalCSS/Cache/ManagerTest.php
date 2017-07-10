<?php

namespace WP\CriticalCSS\Testing\Integration\CriticalCSS\Cache;

use WP\CriticalCSS\Testing\Integration\TestCase;

class ManagerTest extends TestCase {

	public function test_init_template_cache_off() {
		WPCCSS()->get_settings_manager()->update_settings( [ 'web_check_interval' => DAY_IN_SECONDS ] );
		WPCCSS()->init();
		$this->assertEquals(
			10, has_action(
				'post_updated', [
					WPCCSS()->get_cache_manager(),
					'reset_web_check_post_transient',
				]
			)
		);
		$this->assertEquals(
			10, has_action(
				'edited_term', [
					WPCCSS()->get_cache_manager(),
					'reset_web_check_term_transient',
				]
			)
		);
	}
}

