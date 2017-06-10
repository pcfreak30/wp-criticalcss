<?php

namespace WP\Testing\Unit;

use WP\CriticalCSS;

class CriticalCSSTest extends CriticalCSS\Testing\Unit\TestCase {
	public function test_wpccss() {
		$instance = \WPCCSS();

		$this->assertInstanceOf( '\WP\CriticalCSS', $instance );
	}

	public function test_wp_head_with_nocache() {
		\WP_Mock::userFunction( 'get_query_var', [
			'args'   => 'nocache',
			'times'  => 1,
			'return' => true,
		] );
		ob_start();
		WPCCSS()->get_frontend()->wp_head();
		$result = ob_get_clean();
		$this->assertEquals( '<meta name="robots" content="noindex, nofollow"/>', trim( $result ) );
	}

	public function test_wp_head_without_nocache() {
		\WP_Mock::userFunction( 'get_query_var', [
			'args'   => 'nocache',
			'times'  => 1,
			'return' => false,
		] );
		ob_start();
		WPCCSS()->get_frontend()->wp_head();
		$result = ob_get_clean();
		$this->assertEmpty( $result );
	}

	public function test_redirect_canonical_with_nocache_query_var() {
		$GLOBALS['wp_query'] = (object) [
			'query' => [
				'nocache' => true,
				'test'    => true,
			],
		];
		\WP_Mock::userFunction( 'get_query_var', [
			'args'   => 'nocache',
			'times'  => 1,
			'return' => true,
		] );
		\WP_Mock::userFunction( 'home_url', [
			'times'  => 1,
			'return' => 'http://example.org',
		] );
		$this->assertFalse( WPCCSS()->get_request()->redirect_canonical( home_url() ) );
	}

	public function test_query_vars() {
		$this->assertContains( 'nocache', WPCCSS()->get_request()->query_vars( [] ) );
	}

	public function test_get_settings() {
		WPCCSS()->get_settings_manager()->update_settings( [ 'version' => CriticalCSS::VERSION ] );
		$result = WPCCSS()->get_settings();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( 'array', $result );
	}

	public function test_get_settings_empty() {
		$this->assertEmpty( WPCCSS()->get_settings() );
	}

	public function test_init_print_styles_hook_admin() {
		\WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );
		WPCCSS()->init();
		\WP_Mock::expectActionNotAdded( 'wp_print_styles', [
			WPCCSS(),
			'print_styles',
		] );
	}

	public function test_init_template_cache_on() {
		\WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );
		WPCCSS()->get_settings_manager()->update_settings( [ 'template_cache' => 'on' ] );
		\WP_Mock::expectActionAdded( 'template_include', [
			WPCCSS()->get_request(),
			'template_include',
		], PHP_INT_MAX );
		WPCCSS()->get_frontend()->init();
		WPCCSS()->get_request()->init();
	}

	public function test_init_template_cache_off() {
		WPCCSS()->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		\WP_Mock::userFunction( 'is_admin', [ 'return' => false ] );
		\WP_Mock::expectActionAdded( 'post_updated', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_post_transient',
		] );
		\WP_Mock::expectActionAdded( 'edited_term', [
			WPCCSS()->get_cache_manager(),
			'reset_web_check_term_transient',
		] );
		WPCCSS()->get_cache_manager()->init();
	}

	public function test_get_permalink_post() {
		\WP_Mock::userFunction( 'get_permalink', [
			'args'   => 1,
			'return' => 'http://example.org/nocache/',
		] );
		$permalink = WPCCSS()->get_permalink( [
			'type'      => 'post',
			'object_id' => 1,
		] );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_permalink_term() {
		\WP_Mock::userFunction( 'get_term_link', [
			'args'   => 1,
			'return' => 'http://example.org/tag/test/nocache/',
		] );
		$permalink = WPCCSS()->get_permalink( [
			'type'      => 'term',
			'object_id' => 1,
		] );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_permalink_author() {
		\WP_Mock::userFunction(
			'get_author_posts_url', [
				'args'   => 1,
				'return' => 'http://example.org/author/admin/nocache/',
			]
		);
		$permalink = WPCCSS()->get_permalink( [
			'type'      => 'author',
			'object_id' => 1,
		] );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_permalink_url() {
		\WP_Mock::userFunction(
			'home_url', [
				'times'  => 1,
				'return' => function ( $input ) {
					return 'http://example.org' . $input;
				},
			]
		);
		$permalink = WPCCSS()->get_permalink(
			[
				'type' => 'url',
				'url'  => home_url( '/testabc/testabc/testabc/' ),
			]
		);
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	protected function setUp() {
		parent::setUp();
		WPCCSS()->set_settings( [] );
	}

}
