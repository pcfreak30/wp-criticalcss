<?php

class Test_WP_CriticalCSS_Data_Manager extends WP_CriticalCSS_TestCase {
	public function test_set_item_data_post() {
		$instance = WPCCSS();
		$instance->update_settings( array( 'template_cache' => 'off' ) );
		$instance->setup_components();
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_post_meta( $post->ID, 'criticalcss_test', true ) );
	}

	public function test_set_item_data_term() {
		$instance = WPCCSS();
		$instance->update_settings( array( 'template_cache' => 'off' ) );
		$instance->setup_components();
		$term = $this->factory->term->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_term_meta( $term->term_id, 'criticalcss_test', true ) );
	}

	public function test_set_item_data_author() {
		$instance = WPCCSS();
		$instance->update_settings( array( 'template_cache' => 'off' ) );
		$instance->setup_components();
		$this->factory->post->create( array( 'post_author' => 1 ) );
		$this->go_to( get_author_posts_url( 1 ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_user_meta( 1, 'criticalcss_test', true ) );
	}

	public function test_set_item_data_url() {
		$instance = WPCCSS();
		$instance->update_settings( array( 'template_cache' => 'off' ) );
		$instance->setup_components();
		$url = home_url();
		$this->go_to( $url );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_transient( 'criticalcss_url_test_' . md5( $url ) ) );
	}

	public function test_set_item_data_template() {
		$instance = WPCCSS();
		$template = locate_template( 'index.php' );
		WPCCSS()->update_settings( array( 'template_cache' => 'on' ) );
		$instance->setup_components();
		WPCCSS()->get_request()->template_include( $template );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_transient( 'criticalcss_test_' . md5( $template ) ) );
	}

	public function test_get_item_data_post() {
		$instance = WPCCSS();
		$instance->update_settings( array( 'template_cache' => 'off' ) );
		$instance->setup_components();
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_term() {
		$instance = WPCCSS();
		$instance->update_settings( array( 'template_cache' => 'off' ) );
		$instance->setup_components();
		$term = $this->factory->term->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_author() {
		$instance = WPCCSS();
		$instance->update_settings( array( 'template_cache' => 'off' ) );
		$instance->setup_components();
		$this->factory->post->create( array( 'post_author' => 1 ) );
		$this->go_to( get_author_posts_url( 1 ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_url() {
		$instance = WPCCSS();
		$instance->update_settings( array( 'template_cache' => 'off' ) );
		$instance->setup_components();
		$url = home_url();
		$this->go_to( $url );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_template() {
		$instance = WPCCSS();
		$template = locate_template( 'index.php' );
		WPCCSS()->update_settings( array( 'template_cache' => 'on' ) );
		$instance->setup_components();
		WPCCSS()->get_request()->template_include( $template );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_hash_object() {
		WPCCSS()->update_settings( array( 'template_cache' => 'off' ) );
		WPCCSS()->setup_components();
		$this->assertEquals( '2c4eebcf19a6fa7b1d5e085ee453571b', WPCCSS()->get_item_hash( array(
			'object_id' => 1,
			'type'      => 'post',
		) ) );
	}

	public function test_get_item_hash_template() {
		WPCCSS()->update_settings( array( 'template_cache' => 'on' ) );
		WPCCSS()->setup_components();
		$template = locate_template( 'index.php' );
		$this->assertEquals( '998ff5ceec6be52c857c2f418933bef0', WPCCSS()->get_item_hash( array(
			'template'  => $template,
			'object_id' => 1,
			'type'      => 'post',
		) ) );
	}
}