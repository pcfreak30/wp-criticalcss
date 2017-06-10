<?php

namespace WP\Testing\Unit\CriticalCSS;

class RequestTest extends \PHPUnit_Framework_TestCase {
	public function test_get_current_page_type_home_page_for_posts_set() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_option', [ 'args' => 'page_for_posts', 'times' => 1, 'return' => 1 ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		$this->assertEquals( [ 'object_id' => 1, 'type' => 'post' ], WPCCSS()->get_request()->get_current_page_type() );
	}

	public function test_get_current_page_type_home_page_for_posts_not_set() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_option', [ 'args' => 'page_for_posts', 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'site_url', [ 'times' => 1, 'return' => 'http://example.org' ] );
		$GLOBALS['wp'] = (object) [ 'request' => '' ];
		$this->assertEquals(
			[
				'url'  => 'http://example.org',
				'type' => 'url',
			], WPCCSS()->get_request()->get_current_page_type()
		);
	}

	public function test_get_current_page_type_is_front_page_page_on_front_set() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_front_page', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_option', [ 'args' => 'page_on_front', 'times' => 1, 'return' => 1 ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		$this->assertEquals( [ 'object_id' => 1, 'type' => 'post' ], WPCCSS()->get_request()->get_current_page_type() );
	}

	public function test_get_current_page_type_is_front_page_page_on_front_not_set() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_front_page', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_option', [ 'args' => 'page_on_front', 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'site_url', [ 'times' => 1, 'return' => 'http://example.org' ] );
		$GLOBALS['wp'] = (object) [ 'request' => '' ];
		$this->assertEquals(
			[
				'url'  => 'http://example.org',
				'type' => 'url',
			], WPCCSS()->get_request()->get_current_page_type()
		);
	}

	public function test_get_current_page_type_is_singular() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_front_page', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_singular', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_the_ID', [ 'times' => 1, 'return' => 1 ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		$this->assertEquals( [ 'object_id' => 1, 'type' => 'post' ], WPCCSS()->get_request()->get_current_page_type() );
	}

	public function test_get_current_page_type_is_tax() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_front_page', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_singular', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_tax', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_queried_object', [ 'times' => 1, 'return' => (object) [ 'term_id' => 1 ] ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		$this->assertEquals( [ 'object_id' => 1, 'type' => 'term' ], WPCCSS()->get_request()->get_current_page_type() );
	}

	public function test_get_current_page_type_is_category() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_front_page', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_singular', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_tax', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_category', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_queried_object', [ 'times' => 1, 'return' => (object) [ 'term_id' => 1 ] ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		$this->assertEquals( [ 'object_id' => 1, 'type' => 'term' ], WPCCSS()->get_request()->get_current_page_type() );
	}

	public function test_get_current_page_type_is_tag() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_front_page', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_singular', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_tax', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_category', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_tag', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_queried_object', [ 'times' => 1, 'return' => (object) [ 'term_id' => 1 ] ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		$this->assertEquals( [ 'object_id' => 1, 'type' => 'term' ], WPCCSS()->get_request()->get_current_page_type() );
	}

	public function test_get_current_page_type_is_author() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_front_page', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_singular', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_tax', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_category', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_tag', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_author', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_the_author_meta', [ 'args' => 'ID', 'times' => 1, 'return' => 1 ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		$this->assertEquals(
			[
				'object_id' => 1,
				'type'      => 'author',
			], WPCCSS()->get_request()->get_current_page_type()
		);
	}

	public function test_get_current_page_type_url() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_front_page', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_singular', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_tax', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_category', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_tag', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_author', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		\WP_Mock::userFunction( 'site_url', [ 'times' => 1, 'return' => 'http://example.org' ] );
		$GLOBALS['wp'] = (object) [ 'request' => '' ];
		$this->assertEquals(
			[
				'url'  => 'http://example.org',
				'type' => 'url',
			], WPCCSS()->get_request()->get_current_page_type()
		);
	}

	public function test_get_current_page_type_posts_template_cache() {
		WPCCSS()->set_settings( [ 'template_cache' => 'on' ] );
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_option', [ 'args' => 'page_for_posts', 'times' => 1, 'return' => 1 ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => false ] );
		WPCCSS()->get_request()->set_template( 'index.php' );
		WPCCSS()->get_request()->init();
		$this->assertEquals(
			[
				'object_id' => 1,
				'type'      => 'post',
				'template'  => 'index.php',
			], WPCCSS()->get_request()->get_current_page_type()
		);
	}

	public function test_get_current_page_type_posts_multisite() {
		WPCCSS()->set_settings( [ 'template_cache' => 'off' ] );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction( 'is_home', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_option', [ 'args' => 'page_for_posts', 'times' => 1, 'return' => 1 ] );
		\WP_Mock::userFunction( 'is_multisite', [ 'times' => 1, 'return' => true ] );
		\WP_Mock::userFunction( 'get_current_blog_id', [ 'times' => 1, 'return' => 1 ] );
		$this->assertEquals(
			[
				'object_id' => 1,
				'type'      => 'post',
				'blog_id'   => 1,
			], WPCCSS()->get_request()->get_current_page_type()
		);
	}

	protected function setUp() {
		\WP_Mock::setUp();
		parent::setUp();
		WPCCSS()->set_settings( [] );
		WPCCSS()->get_request()->set_template( null );
		WPCCSS()->get_request()->init();
		\WP_Mock::userFunction(
			'absint', [
				'return' => function ( $maybeint ) {
					return abs( intval( $maybeint ) );
				},
			]
		);
		\WP_Mock::userFunction(
			'trailingslashit', [
				'return' => function ( $string ) {
					return untrailingslashit( $string ) . '/';
				},
			]
		);
		\WP_Mock::userFunction(
			'untrailingslashit', [
				'return' => function ( $string ) {
					return rtrim( $string, '/\\' );
				},
			]
		);
	}

	protected function tearDown() {
		\WP_Mock::tearDown();
		parent::tearDown();
	}
}
