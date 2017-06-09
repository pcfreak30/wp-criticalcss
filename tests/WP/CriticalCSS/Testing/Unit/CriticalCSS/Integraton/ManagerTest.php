<?php

namespace WP\CriticalCSS\Testing\Integration;

class ManagerTest extends \WP\CriticalCSS\Testing\Unit\TestCase {
	public function test_init() {
		WPCCSS()->get_integration_manager()->init();
		foreach ( WPCCSS()->get_integration_manager()->get_integrations() as $integration ) {
			$this->assertInstanceOf( '\\WP\\CriticalCSS\\Integration\\IntegrationAbstract', $integration );
		}
	}
}
