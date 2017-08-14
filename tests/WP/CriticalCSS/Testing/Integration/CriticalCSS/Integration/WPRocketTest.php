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
		wp_criticalcss()->init();
		do_action_ref_array( 'wp', [ &$wp ] );
		$this->assertEquals(
			10, has_action(
				'after_rocket_clean_domain', [
					wp_criticalcss(),
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
					wp_criticalcss(),
					'reset_web_check_transients',
				]
			)
		);
		wp_criticalcss()->init();
		do_action_ref_array( 'wp', [ &$wp ] );
		$this->assertEquals(
			10, has_action(
				'after_rocket_clean_domain', [
					wp_criticalcss(),
					'reset_web_check_transients',
				]
			)
		);
		wp_criticalcss()->get_integration_manager()->disable_integrations();
		$this->assertFalse(
			has_action(
				'after_rocket_clean_domain', [
					wp_criticalcss(),
					'reset_web_check_transients',
				]
			)
		);
	}
}