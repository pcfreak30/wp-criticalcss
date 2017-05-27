<?php

class Test_WP_CriticalCSS extends WP_UnitTestCase {
	private $home_url;

	public function setUp() {
		parent::setUp();
		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies();
		flush_rewrite_rules();

		$this->home_url = get_option( 'home' );
	}

	public function tearDown() {
		global $wp_rewrite;
		$wp_rewrite->init();

		update_option( 'home', $this->home_url );
		WP_CriticalCSS_Test::reset();
		parent::tearDown();
	}

	public function test_wpccss() {
		$instance = WPCCSS();

		$this->assertInstanceOf( 'WP_CriticalCSS', $instance );
	}

	/**
	 *
	 */
	public function test_get_instance() {
		$instance = WP_CriticalCSS::get_instance();

		$this->assertInstanceOf( 'WP_CriticalCSS', $instance );
	}

	public function test_wp_head_with_nocache() {
		WPCCSS()->init();
		WPCCSS()->add_rewrite_rules();
		flush_rewrite_rules();
		$this->go_to( home_url( '/nocache' ) );
		ob_start();
		WPCCSS()->wp_head();
		$result = ob_get_clean();
		$this->assertEquals( '<meta name="robots" content="noindex, nofollow"/>', trim( $result ) );
	}

	public function test_wp_head_without_nocache() {
		$this->go_to( home_url() );
		ob_start();
		WPCCSS()->wp_head();
		$result = ob_get_clean();
		$this->assertEmpty( $result );
	}

	public function test_redirect_canonical_with_nocache_query_var() {
		WPCCSS()->init();
		WPCCSS()->add_rewrite_rules();
		flush_rewrite_rules();
		$this->go_to( home_url( '/nocache' ) );
		$this->assertFalse( WPCCSS()->redirect_canonical( home_url() ) );
	}

	public function test_parse_request() {
		$wp                        = new WP();
		$wp->query_vars['nocache'] = true;
		WPCCSS()->parse_request( $wp );
		$this->assertArrayNotHasKey( 'nocache', $wp->query_vars );
		$this->assertTrue( WPCCSS()->get_no_cache() );
	}

	public function test_query_vars() {
		$this->assertContains( 'nocache', WPCCSS()->query_vars( array() ) );
	}

	public function test_get_settings() {
		update_option( WP_CriticalCSS::OPTIONNAME, array( 'version' => WP_CriticalCSS::VERSION ) );
		$result = WPCCSS()->get_settings();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( 'array', $result );
	}

	public function test_get_settings_empty() {
		$this->assertEmpty( WPCCSS()->get_settings() );
	}

	public function test_get_settings_multisite() {
		update_site_option( WP_CriticalCSS::OPTIONNAME, array( 'version' => WP_CriticalCSS::VERSION ) );
		$result = WPCCSS()->get_settings();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( 'array', $result );
	}

	public function test_update_settings() {
		$this->require_normal();
		$settings = array( 'version' => WP_CriticalCSS::VERSION );
		delete_option( WP_CriticalCSS::OPTIONNAME );
		WPCCSS()->update_settings( $settings );
		$this->assertEquals( $settings, get_option( WP_CriticalCSS::OPTIONNAME ) );
	}

	protected function require_normal() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'In multisite' );
		}
	}

	public function test_update_settings_multisite() {
		$this->require_multisite();
		$settings = array( 'version' => WP_CriticalCSS::VERSION );
		delete_site_option( WP_CriticalCSS::OPTIONNAME );
		WPCCSS()->update_settings( $settings );
		$this->assertEquals( $settings, get_site_option( WP_CriticalCSS::OPTIONNAME ) );
	}

	protected function require_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not in multisite' );
		}
	}

	public function test_init_print_styles_hook() {
		WPCCSS()->init();
		$this->assertEquals( 7, has_action( 'wp_print_styles', array( WPCCSS(), 'print_styles' ) ) );
	}

	public function test_init_print_styles_hook_admin() {
		define( 'WP_ADMIN', true );
		WPCCSS()->init();
		$this->assertFalse( has_action( 'wp_print_styles', array( WPCCSS(), 'print_styles' ) ) );
	}

	public function test_init_template_cache_on() {
		WPCCSS()->update_settings( array( 'template_cache' => 'on' ) );
		WPCCSS()->init();
		$this->assertEquals( PHP_INT_MAX, has_action( 'template_include', array( WPCCSS(), 'template_include' ) ) );
	}

	public function test_init_template_cache_off() {
		WPCCSS()->init();
		$this->assertEquals( 10, has_action( 'post_updated', array(
			WPCCSS(),
			'reset_web_check_post_transient',
		) ) );
		$this->assertEquals( 10, has_action( 'edited_term', array(
			WPCCSS(),
			'reset_web_check_term_transient',
		) ) );
	}

	public function test_add_rewrite_rules() {
		$tax = rand_str();
		register_taxonomy( $tax, 'post' );
		WPCCSS()->add_rewrite_rules();
		$this->assertContains( 'nocache', $GLOBALS['wp']->public_query_vars );
		$this->assertArrayHasKey( 'nocache/?$', $GLOBALS['wp_rewrite']->extra_rules_top );
		$this->assertArrayHasKey( $tax . '/(.+?)/nocache/?$', $GLOBALS['wp_rewrite']->extra_rules_top );
	}

	public function test_fix_rewrites() {
		$tax = rand_str();
		register_taxonomy( $tax, 'post' );
		WPCCSS()->init();
		WPCCSS()->add_rewrite_rules();
		flush_rewrite_rules();
		$tokens = get_taxonomies( array(
			'public'   => true,
			'_builtin' => false,
		) );
		$rules  = $GLOBALS['wp_rewrite']->rules;

		$count = 0;
		$found = false;
		foreach ( $rules as $match => $query ) {
			if ( false !== strpos( $match, 'nocache' ) && preg_match( '/' . implode( '|', $tokens ) . '/', $query ) ) {
				$found = true;
			} else {
				if ( $found ) {
					$count ++;
					$found = false;
				}
				$this->assertLessThan( 2, $count );
			}
		}
	}

	public function test_get_permalink_post() {
		$post      = $this->factory->post->create_and_get();
		$permalink = WPCCSS()->get_permalink( array( 'type' => 'post', 'object_id' => $post->ID ) );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_permalink_term() {
		$term      = $this->factory->term->create_and_get();
		$permalink = WPCCSS()->get_permalink( array( 'type' => 'term', 'object_id' => $term->term_id ) );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_permalink_author() {
		$permalink = WPCCSS()->get_permalink( array( 'type' => 'author', 'object_id' => 1 ) );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_permalink_url() {
		$permalink = WPCCSS()->get_permalink( array(
			'type' => 'url',
			'url'  => home_url( '/testabc/testabc/testabc/' ),
		) );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_current_page_type_url() {
		$url = home_url();
		$this->go_to( $url );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$this->assertEquals( 'url', $type['type'] );
		$this->assertEquals( $url, $type['url'] );
	}

	public function test_get_current_page_type_home_post_page_set() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $post->ID );
		$this->go_to( get_permalink( $post->ID ) );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$this->assertEquals( 'post', $type['type'] );
		$this->assertEquals( $post->ID, $type['object_id'] );
	}

	public function test_get_current_page_type_front_post_page_set() {
		WPCCSS()->init();
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $post->ID );
		$this->go_to( get_permalink( $post->ID ) );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$this->assertEquals( 'post', $type['type'] );
		$this->assertEquals( $post->ID, $type['object_id'] );
	}

	public function test_get_current_page_type_post() {
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$this->assertEquals( 'post', $type['type'] );
		$this->assertEquals( $post->ID, $type['object_id'] );
	}

	public function test_get_current_page_type_tag() {
		$term = $this->factory->term->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$this->assertEquals( 'term', $type['type'] );
		$this->assertEquals( $term->term_id, $type['object_id'] );
	}

	public function test_get_current_page_type_category() {
		$term = $this->factory->category->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$this->assertEquals( 'term', $type['type'] );
		$this->assertEquals( $term->term_id, $type['object_id'] );
	}

	public function test_get_current_page_type_author() {
		$this->factory->post->create( array( 'post_author' => 1 ) );
		$this->go_to( get_author_posts_url( 1 ) );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$this->assertEquals( 'author', $type['type'] );
		$this->assertEquals( 1, $type['object_id'] );
	}

	public function test_get_current_page_type_template() {
		$template = locate_template( 'index.php' );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'on' ) );
		WP_CriticalCSS_Test::get_instance()->template_include( $template );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$this->assertEquals( str_replace( trailingslashit( WP_CONTENT_DIR ), '', $template ), $type['template'] );
	}

	public function test_get_current_page_type_multisite() {
		$this->require_multisite();

		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$this->assertEquals( 1, $type['blog_id'] );
	}

	public function test_update_cache_fragment() {
		WP_CriticalCSS_Test::get_instance()->set_settings( array(
			'template_cache'     => 'off',
			'web_check_interval' => MINUTE_IN_SECONDS,
		) );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$type = WP_CriticalCSS_Test::get_instance()->get_current_page_type();
		$hash = WP_CriticalCSS_Test::get_instance()->get_item_hash( $type );
		WP_CriticalCSS_Test::get_instance()->update_cache_fragment( array( $hash ), true );
		$this->assertTrue( WP_CriticalCSS_Test::get_instance()->get_cache_fragment( array( $hash ) ) );
	}

	public function test_set_item_data_post() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_post_meta( $post->ID, 'criticalcss_test', true ) );
	}

	public function test_set_item_data_term() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$term = $this->factory->term->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_term_meta( $term->term_id, 'criticalcss_test', true ) );
	}

	public function test_set_item_data_author() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$this->factory->post->create( array( 'post_author' => 1 ) );
		$this->go_to( get_author_posts_url( 1 ) );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_user_meta( 1, 'criticalcss_test', true ) );
	}

	public function test_set_item_data_url() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$url = home_url();
		$this->go_to( $url );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_transient( 'criticalcss_url_test_' . md5( $url ) ) );
	}

	public function test_set_item_data_template() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$template = locate_template( 'index.php' );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'on' ) );
		WP_CriticalCSS_Test::get_instance()->template_include( $template );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_transient( 'criticalcss_test_' . md5( $template ) ) );
	}

	public function test_get_item_data_post() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_item_data( $instance->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_term() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$term = $this->factory->term->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_item_data( $instance->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_author() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$this->factory->post->create( array( 'post_author' => 1 ) );
		$this->go_to( get_author_posts_url( 1 ) );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_item_data( $instance->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_url() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$instance->set_settings( array( 'template_cache' => 'off' ) );
		$url = home_url();
		$this->go_to( $url );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_item_data( $instance->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_template() {
		$instance = WP_CriticalCSS_Test::get_instance();
		$template = locate_template( 'index.php' );
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'on' ) );
		WP_CriticalCSS_Test::get_instance()->template_include( $template );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->set_item_data( $instance->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_item_data( $instance->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_hash_object() {
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'off' ) );
		$this->assertEquals( '2c4eebcf19a6fa7b1d5e085ee453571b', WPCCSS()->get_item_hash( array(
			'object_id' => 1,
			'type'      => 'post',
		) ) );
	}

	public function test_get_item_hash_template() {
		WP_CriticalCSS_Test::get_instance()->set_settings( array( 'template_cache' => 'on' ) );
		$template = locate_template( 'index.php' );
		$this->assertEquals( '998ff5ceec6be52c857c2f418933bef0', WPCCSS()->get_item_hash( array(
			'template'  => $template,
			'object_id' => 1,
			'type'      => 'post',
		) ) );
	}
}
