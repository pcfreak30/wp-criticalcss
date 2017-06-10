<?php

namespace WP\CriticalCSS\Testing\Integration\CriticalCSS\Cache;

use WP\CriticalCSS\Testing\Integration\TestCase;

class Manager extends TestCase {


	public function test_update_cache_fragment() {
		WPCCSS()->update_settings(
			array(
				'template_cache'     => 'off',
				'web_check_interval' => MINUTE_IN_SECONDS,
			)
		);
		WPCCSS()->init();
		$this->assertTrue( WPCCSS()->get_cache_manager()->update_cache_fragment( array( 'test' ), true ) );
		$this->assertTrue( WPCCSS()->get_cache_manager()->get_cache_fragment( array( 'test' ) ) );
	}

	public function test_delete_cache_branch() {
		WPCCSS()->update_settings(
			array(
				'template_cache'     => 'off',
				'web_check_interval' => MINUTE_IN_SECONDS,
			)
		);
		WPCCSS()->init();
		WPCCSS()->get_cache_manager()->update_cache_fragment( array( 'test' ), true );
		$this->assertTrue( WPCCSS()->get_cache_manager()->delete_cache_branch() );
		$this->assertFalse( WPCCSS()->get_cache_manager()->get_cache_fragment( array() ) );
	}

	public function test_delete_cache_leaf() {
		WPCCSS()->update_settings(
			array(
				'template_cache'     => 'off',
				'web_check_interval' => MINUTE_IN_SECONDS,
			)
		);
		WPCCSS()->init();
		WPCCSS()->get_cache_manager()->update_cache_fragment( array( 'test' ), true );
		$this->assertTrue( WPCCSS()->get_cache_manager()->delete_cache_leaf( array( 'test' ) ) );
		$this->assertFalse( WPCCSS()->get_cache_manager()->get_cache_fragment( array( 'test' ) ) );
	}

	public function test_get_cache_fragment() {
		WPCCSS()->update_settings(
			array(
				'template_cache'     => 'off',
				'web_check_interval' => MINUTE_IN_SECONDS,
			)
		);
		WPCCSS()->init();
		WPCCSS()->get_cache_manager()->update_cache_fragment( array( 'test' ), true );
		$this->assertTrue( WPCCSS()->get_cache_manager()->get_cache_fragment( array( 'test' ) ) );
	}

	public function test_get_expire_period() {
		WPCCSS()->update_settings(
			array(
				'web_check_interval' => MINUTE_IN_SECONDS,
			)
		);
		WPCCSS()->init();
		$this->assertEquals( MINUTE_IN_SECONDS, WPCCSS()->get_cache_manager()->get_expire_period() );
	}
}
