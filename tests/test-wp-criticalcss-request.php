<?php

class Test_WP_CriticalCSS_Request extends WP_CriticalCSS_TestCase {
	public function test_add_rewrite_rules() {
		$tax = rand_str();
		register_taxonomy( $tax, 'post' );
		WPCCSS()->get_request()->add_rewrite_rules();
		$this->assertContains( 'nocache', $GLOBALS['wp']->public_query_vars );
		$this->assertArrayHasKey( 'nocache/?$', $GLOBALS['wp_rewrite']->extra_rules_top );
		$this->assertArrayHasKey( $tax . '/(.+?)/nocache/?$', $GLOBALS['wp_rewrite']->extra_rules_top );
	}

	public function test_fix_rewrites() {
		$tax = rand_str();
		register_taxonomy( $tax, 'post' );
		WPCCSS()->init();
		WPCCSS()->get_request()->add_rewrite_rules();
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

	public function test_get_current_page_type_url() {
		$url = home_url();
		$this->go_to( $url );
		WPCCSS()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WPCCSS()->get_current_page_type();
		$this->assertEquals( 'url', $type['type'] );
		$this->assertEquals( $url, $type['url'] );
	}

	public function test_get_current_page_type_home_post_page_set() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $post->ID );
		$this->go_to( get_permalink( $post->ID ) );
		WPCCSS()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WPCCSS()->get_current_page_type();
		$this->assertEquals( 'post', $type['type'] );
		$this->assertEquals( $post->ID, $type['object_id'] );
	}

	public function test_get_current_page_type_front_post_page_set() {
		WPCCSS()->init();
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $post->ID );
		$this->go_to( get_permalink( $post->ID ) );
		WPCCSS()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WPCCSS()->get_current_page_type();
		$this->assertEquals( 'post', $type['type'] );
		$this->assertEquals( $post->ID, $type['object_id'] );
	}

	public function test_get_current_page_type_post() {
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		WPCCSS()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WPCCSS()->get_current_page_type();
		$this->assertEquals( 'post', $type['type'] );
		$this->assertEquals( $post->ID, $type['object_id'] );
	}

	public function test_get_current_page_type_tag() {
		$term = $this->factory->term->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		WPCCSS()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WPCCSS()->get_current_page_type();
		$this->assertEquals( 'term', $type['type'] );
		$this->assertEquals( $term->term_id, $type['object_id'] );
	}

	public function test_get_current_page_type_category() {
		$term = $this->factory->category->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		WPCCSS()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WPCCSS()->get_current_page_type();
		$this->assertEquals( 'term', $type['type'] );
		$this->assertEquals( $term->term_id, $type['object_id'] );
	}

	public function test_get_current_page_type_author() {
		$this->factory->post->create( array( 'post_author' => 1 ) );
		$this->go_to( get_author_posts_url( 1 ) );
		WPCCSS()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WPCCSS()->get_current_page_type();
		$this->assertEquals( 'author', $type['type'] );
		$this->assertEquals( 1, $type['object_id'] );
	}

	public function test_get_current_page_type_template() {
		$template = locate_template( 'index.php' );
		WPCCSS()->set_settings( array( 'template_cache' => 'on' ) );
		WPCCSS()->template_include( $template );
		$type = WPCCSS()->get_current_page_type();
		$this->assertEquals( str_replace( trailingslashit( WP_CONTENT_DIR ), '', $template ), $type['template'] );
	}

	public function test_get_current_page_type_multisite() {
		$this->require_multisite();

		WPCCSS()->set_settings( array( 'template_cache' => 'off' ) );
		$type = WPCCSS()->get_current_page_type();
		$this->assertEquals( 1, $type['blog_id'] );
	}


}
