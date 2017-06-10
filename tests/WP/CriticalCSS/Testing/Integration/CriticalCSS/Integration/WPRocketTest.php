<?php

namespace WP\CriticalCSS\Testing\Integration\CriticalCSS\Integration;

use WP\CriticalCSS\Testing\Integration\TestCase;

/**
 * @group wp-rocket
 * @runTestsInSeparateProcesses
 */
class WPRocketTest extends TestCase {

	public function test_enable() {
		global $wp;
		WPCCSS()->init();
		do_action_ref_array( 'wp', [ &$wp ] );
		$this->assertEquals(
			10, has_action(
				'after_rocket_clean_domain', [
					WPCCSS(),
					'reset_web_check_transients',
				]
			)
		);
	}

	public function test_disable() {
		global $wp;
		$this->assertFalse(
			has_action(
				'after_rocket_clean_domain', [
					WPCCSS(),
					'reset_web_check_transients',
				]
			)
		);
		WPCCSS()->init();
		do_action_ref_array( 'wp', [ &$wp ] );
		$this->assertEquals(
			10, has_action(
				'after_rocket_clean_domain', [
					WPCCSS(),
					'reset_web_check_transients',
				]
			)
		);
		WPCCSS()->get_integration_manager()->disable_integrations();
		$this->assertFalse(
			has_action(
				'after_rocket_clean_domain', [
					WPCCSS(),
					'reset_web_check_transients',
				]
			)
		);
	}
}