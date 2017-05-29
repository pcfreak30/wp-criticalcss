<?php

/**
 * @group wp-rocket
 * @runInSeparateProcess
 */
class Test_WP_CriticalCSS_Integration_WP_Rocket extends WP_CriticalCSS_TestCase {

	public function test_enable() {
		global $wp;
		WPCCSS()->init();
		WPCCSS()->setup_integrations( true );
		do_action_ref_array( 'wp', array( &$wp ) );

		$this->assertEquals( 10, has_action( 'after_rocket_clean_domain', array(
			WPCCSS(),
			'reset_web_check_transients',
		) ) );
	}

	public function test_disable() {
		global $wp;
		$this->assertFalse( 10, has_action( 'after_rocket_clean_domain', array(
			WPCCSS(),
			'reset_web_check_transients',
		) ) );
		WPCCSS()->init();
		WPCCSS()->setup_integrations( true );
		do_action_ref_array( 'wp', array( &$wp ) );
		$this->assertEquals( 10, has_action( 'after_rocket_clean_domain', array(
			WPCCSS(),
			'reset_web_check_transients',
		) ) );
		WPCCSS()->disable_integrations();
		$this->assertFalse( 10, has_action( 'after_rocket_clean_domain', array(
			WPCCSS(),
			'reset_web_check_transients',
		) ) );
	}
}