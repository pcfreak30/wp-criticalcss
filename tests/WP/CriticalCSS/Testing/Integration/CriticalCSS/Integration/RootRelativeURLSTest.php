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
		WPCCSS()->get_integration_manager()->disable_integrations();
		$this->assertEquals(
			1, has_action(
				'post_link', [
					'MP_WP_Root_Relative_URLS',
					'proper_root_relative_url',
				]
			)
		);
		WPCCSS()->get_integration_manager()->enable_integrations();
		$this->assertFalse(
			has_action(
				'post_link', [
					'MP_WP_Root_Relative_URLS',
					'proper_root_relative_url',
				]
			)
		);
	}

	public function test_disable() {
		global $wp;
		$this->assertFalse( has_action(
				'post_link', [
					'MP_WP_Root_Relative_URLS',
					'proper_root_relative_url',
				]
			)
		);
		WPCCSS()->get_integration_manager()->disable_integrations();
		$this->assertEquals(
			1, has_action(
				'post_link', [
					'MP_WP_Root_Relative_URLS',
					'proper_root_relative_url',
				]
			)
		);
	}
}