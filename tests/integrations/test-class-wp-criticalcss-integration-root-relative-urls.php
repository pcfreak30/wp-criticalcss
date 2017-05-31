<?php

/**
 * @group root-relative-urls
 * @runInSeparateProcess
 */
class Test_WP_CriticalCSS_Integration_Root_Relative_URLS extends WP_CriticalCSS_TestCase {

	public function test_enable() {
		global $wp;
		$this->assertEquals( 1, has_action( 'post_link', array(
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		) ) );
		WPCCSS()->init();
		WPCCSS()->setup_integrations( true );
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
		WPCCSS()->setup_integrations( true );
		do_action_ref_array( 'wp', array( &$wp ) );
		$this->assertFalse( has_action( 'post_link', array(
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		) ) );
		WPCCSS()->disable_integrations();
		$this->assertEquals( 1, has_action( 'post_link', array(
			'MP_WP_Root_Relative_URLS',
			'proper_root_relative_url',
		) ) );
	}
}