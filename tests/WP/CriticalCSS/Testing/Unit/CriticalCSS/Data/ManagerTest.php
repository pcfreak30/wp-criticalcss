<?php

namespace WP\CriticalCSS\Testing\Data;

use WP\CriticalCSS\Testing\Unit\TestCase;

class ManagerTest extends TestCase {
	public function test_set_item_data_post() {
		\WP_Mock::userFunction( 'is_home', [
			'times'  => 1,
			'return' => true,
		] );
		\WP_Mock::userFunction( 'get_option', [
			'args'   => 'page_for_posts',
			'times'  => 1,
			'return' => 1,
		] );
		\WP_Mock::userFunction(
			'update_post_meta', [
				'args'   => [
					1,
					'criticalcss_test',
					true,
				],
				'times'  => 1,
				'return' => true,
			]
		);
		$instance = WPCCSS();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$instance->init();
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
	}

	public function test_set_item_data_term() {
		\WP_Mock::userFunction( 'is_home', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_front_page', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_singular', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_tax', [
			'times'  => 1,
			'return' => true,
		] );
		\WP_Mock::userFunction( 'get_queried_object', [
			'times'  => 1,
			'return' => (object) [ 'term_id' => 1 ],
		] );
		\WP_Mock::userFunction(
			'update_term_meta', [
				'args'   => [
					1,
					'criticalcss_test',
					true,
				],
				'times'  => 1,
				'return' => true,
			]
		);
		$instance = WPCCSS();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$instance->init();
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
	}

	public function test_set_item_data_author() {

		\WP_Mock::userFunction( 'is_home', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_front_page', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_singular', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_tax', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_category', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_tag', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_author', [
			'times'  => 1,
			'return' => true,
		] );
		\WP_Mock::userFunction( 'get_the_author_meta', [
			'args'   => 'ID',
			'times'  => 1,
			'return' => 1,
		] );
		\WP_Mock::userFunction(
			'update_user_meta', [
				'args'   => [
					1,
					'criticalcss_test',
					true,
				],
				'times'  => 1,
				'return' => true,
			]
		);
		$instance = WPCCSS();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$instance->init();
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
	}

	public function test_set_item_data_url() {
		\WP_Mock::userFunction( 'is_home', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_front_page', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_singular', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_tax', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_category', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_tag', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'is_author', [
			'times'  => 1,
			'return' => false,
		] );
		\WP_Mock::userFunction( 'site_url', [
			'times'  => 1,
			'return' => 'http://example.org',
		] );
		\WP_Mock::userFunction(
			'set_transient', [
				'args'   => [
					'criticalcss_url_test_' . md5( 'http://example.org' ),
					true,
					0,
				],
				'times'  => 1,
				'return' => true,
			]
		);
		$GLOBALS['wp'] = (object) [ 'request' => '' ];
		$instance      = WPCCSS();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$instance->init();
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
	}

	public function test_set_item_data_template() {
		\WP_Mock::userFunction( 'is_home', [
			'times'  => 1,
			'return' => true,
		] );
		\WP_Mock::userFunction( 'get_option', [
			'args'   => 'page_for_posts',
			'times'  => 1,
			'return' => 1,
		] );
		\WP_Mock::userFunction(
			'set_transient', [
				'args'   => [
					'criticalcss_test_' . md5( 'index.php' ),
					true,
					0,
				],
				'times'  => 1,
				'return' => true,
			]
		);
		$GLOBALS['wp'] = (object) [ 'request' => '' ];

		$instance = WPCCSS();
		$instance->set_settings( array( 'template_cache' => 'on' ) );
		$instance->get_request()->init();
		$instance->get_data_manager()->init();
		$instance->get_request()->set_template( 'index.php' );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
	}

	protected function setUp() {
		parent::setUp();
		\WP_Mock::userFunction( 'is_multisite', [ 'return' => false ] );
		\WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );
		WPCCSS()->set_settings( [] );
	}
}
