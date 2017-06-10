<?php

namespace WP\CriticalCSS\Testing\Integration\CriticalCSS\Cache;

use WP\CriticalCSS\Testing\Integration\TestCase;

class ManagerTest extends TestCase {


	public function test_update_cache_fragment() {
		WPCCSS()->get_settings_manager()->update_settings(
			[
				'template_cache'     => 'off',
				'web_check_interval' => HOUR_IN_SECONDS,
			]
		);
		WPCCSS()->init();
		$this->assertTrue( WPCCSS()->get_cache_manager()->update_cache_fragment( [ 'test' ], true ) );
		$this->assertTrue( WPCCSS()->get_cache_manager()->get_cache_fragment( [ 'test' ] ) );
	}

	public function test_delete_cache_branch() {
		WPCCSS()->get_settings_manager()->update_settings(
			[
				'template_cache'     => 'off',
				'web_check_interval' => HOUR_IN_SECONDS,
			]
		);
		WPCCSS()->init();
		WPCCSS()->get_cache_manager()->update_cache_fragment( [ 'test' ], true );
		$this->assertTrue( WPCCSS()->get_cache_manager()->delete_cache_branch() );
		$this->assertFalse( WPCCSS()->get_cache_manager()->get_cache_fragment( [] ) );
	}

	public function test_delete_cache_leaf() {
		WPCCSS()->get_settings_manager()->update_settings(
			[
				'template_cache'     => 'off',
				'web_check_interval' => HOUR_IN_SECONDS,
			]
		);
		WPCCSS()->init();
		WPCCSS()->get_cache_manager()->update_cache_fragment( [ 'test' ], true );
		$this->assertTrue( WPCCSS()->get_cache_manager()->delete_cache_leaf( [ 'test' ] ) );
		$this->assertFalse( WPCCSS()->get_cache_manager()->get_cache_fragment( [ 'test' ] ) );
	}

	public function test_get_cache_fragment() {
		WPCCSS()->get_settings_manager()->update_settings(
			[
				'template_cache'     => 'off',
				'web_check_interval' => HOUR_IN_SECONDS,
			]
		);
		WPCCSS()->init();
		WPCCSS()->get_cache_manager()->update_cache_fragment( [ 'test' ], true );
		$this->assertTrue( WPCCSS()->get_cache_manager()->get_cache_fragment( [ 'test' ] ) );
	}

	public function test_get_expire_period() {
		WPCCSS()->get_settings_manager()->update_settings(
			[
				'web_check_interval' => HOUR_IN_SECONDS,
			]
		);
		WPCCSS()->init();
		$this->assertEquals( HOUR_IN_SECONDS, WPCCSS()->get_cache_manager()->get_expire_period() );
	}

	public function test_init_template_cache_off() {
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

