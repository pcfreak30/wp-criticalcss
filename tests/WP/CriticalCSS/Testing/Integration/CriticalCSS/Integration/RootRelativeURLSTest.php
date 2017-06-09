<?php

namespace WP\CriticalCSS\Testing\Integration\CriticalCSS\Integration;

use WP\CriticalCSS\Testing\Integration\TestCase;

/**
 * @group root-relative-urls
 * @runInSeparateProcess
 */
class RootRelativeURLSTest extends TestCase {

	public function test_enable() {
		global $wp;
		$this->assertEquals( 1, has_action( 'post_link', array(
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		) ) );
		WPCCSS()->init();
		do_action_ref_array( 'wp', array( &$wp ) );
		$this->assertFalse( has_action( 'post_link', array(
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		) ) );
	}

	public function test_disable() {
		global $wp;
		$this->assertEquals( 1, has_action( 'post_link', array(
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		) ) );
		WPCCSS()->init();
		do_action_ref_array( 'wp', array( &$wp ) );
		$this->assertFalse( has_action( 'post_link', array(
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		) ) );
		WPCCSS()->get_integration_manager()->disable_integrations();
		$this->assertEquals( 1, has_action( 'post_link', array(
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		) ) );
	}
}