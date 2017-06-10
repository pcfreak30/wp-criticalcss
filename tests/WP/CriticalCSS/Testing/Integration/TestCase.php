<?php

namespace WP\CriticalCSS\Testing\Integration;

use WP\CriticalCSS;

class TestCase extends \WP_UnitTestCase {
	protected $home_url;

	public function setUp() {
		parent::setUp();
		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies();
		flush_rewrite_rules();

		$this->home_url = get_option( 'home' );
		WPCCSS()->get_integration_manager()->reset();
		WPCCSS()->get_request()->set_nocache( null );
		WPCCSS()->get_settings_manager()->update_settings( [ 'version' => CriticalCSS::VERSION ] );
		\WP_Mock::setUp();
	}

	public function tearDown() {
		global $wp_rewrite;
		$wp_rewrite->init();

		update_option( 'home', $this->home_url );
		\WP_Mock::tearDown();
		parent::tearDown();
	}

	protected function require_normal() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'In multisite' );
		}
	}

	protected function require_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not in multisite' );
		}
	}
}