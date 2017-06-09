<?php


namespace WP\CriticalCSS\Testing\Integration\CriticalCSS\Integration;


use WP\CriticalCSS\Integration\IntegrationAbstract;

class Manager extends \WP\CriticalCSS\Integration\Manager {
	public function reset() {
		$integrations = array();
		foreach ( $this->integrations as $integration ) {
			if ( $integration instanceof IntegrationAbstract ) {
				$integration = get_class( $integration );
			}
			$integrations[] = $integration;
		}
		$this->integrations = $integrations;
	}
}