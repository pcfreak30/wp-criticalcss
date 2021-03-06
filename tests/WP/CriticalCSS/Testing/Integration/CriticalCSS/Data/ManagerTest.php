<?php

namespace WP\CriticalCSS\Testing\Integration\CriticalCSS\Data;

use WP\CriticalCSS\Testing\Integration\TestCase;

class ManagerTest extends TestCase {
	public function test_set_item_data_post() {
		$instance = wp_criticalcss();
		$instance->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		$instance->init();
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_post_meta( $post->ID, 'criticalcss_test', true ) );
	}

	public function test_set_item_data_term() {
		$instance = wp_criticalcss();
		$instance->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		$instance->init();
		$term = $this->factory->term->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_term_meta( $term->term_id, 'criticalcss_test', true ) );
	}

	public function test_set_item_data_author() {
		$instance = wp_criticalcss();
		$instance->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		$instance->init();
		$this->factory->post->create( [ 'post_author' => 1 ] );
		$this->go_to( get_author_posts_url( 1 ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_user_meta( 1, 'criticalcss_test', true ) );
	}

	public function test_set_item_data_url() {
		$instance = wp_criticalcss();
		$instance->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		$instance->init();
		$url = home_url();
		$this->go_to( $url );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_transient( 'criticalcss_url_test_' . md5( $url ) ) );
	}

	public function test_set_item_data_template() {
		$instance = wp_criticalcss();
		$template = locate_template( 'index.php' );
		wp_criticalcss()->get_settings_manager()->update_settings( [ 'template_cache' => 'on' ] );
		$instance->init();
		wp_criticalcss()->get_request()->template_include( $template );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) get_transient( 'criticalcss_test_' . md5( $template ) ) );
	}

	public function test_get_item_data_post() {
		$instance = wp_criticalcss();
		$instance->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		$instance->init();
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_term() {
		$instance = wp_criticalcss();
		$instance->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		$instance->init();
		$term = $this->factory->term->create_and_get();
		$this->go_to( get_term_link( $term->term_id ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_author() {
		$instance = wp_criticalcss();
		$instance->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		$instance->init();
		$this->factory->post->create( [ 'post_author' => 1 ] );
		$this->go_to( get_author_posts_url( 1 ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_url() {
		$instance = wp_criticalcss();
		$instance->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		$instance->init();
		$url = home_url();
		$this->go_to( $url );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_data_template() {
		$instance = wp_criticalcss();
		$template = locate_template( 'index.php' );
		wp_criticalcss()->get_settings_manager()->update_settings( [ 'template_cache' => 'on' ] );
		$instance->init();
		wp_criticalcss()->get_request()->template_include( $template );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$instance->get_data_manager()->set_item_data( $instance->get_request()->get_current_page_type(), 'test', true );
		$this->assertTrue( (bool) $instance->get_data_manager()->get_item_data( $instance->get_request()->get_current_page_type(), 'test' ) );
	}

	public function test_get_item_hash_object() {
		wp_criticalcss()->get_settings_manager()->update_settings( [ 'template_cache' => 'off' ] );
		wp_criticalcss()->init();
		$this->assertEquals(
			'2c4eebcf19a6fa7b1d5e085ee453571b', wp_criticalcss()->get_data_manager()->get_item_hash(
			[
				'object_id' => 1,
				'type'      => 'post',
			]
		)
		);
	}

	public function test_get_item_hash_template() {
		wp_criticalcss()->get_settings_manager()->update_settings( [ 'template_cache' => 'on' ] );
		wp_criticalcss()->init();
		$template = locate_template( 'index.php' );
		$this->assertEquals(
			'ccdec35a9d8b3fd7e5a079b4512d3c08', wp_criticalcss()->get_data_manager()->get_item_hash(
			[
				'template'  => $template,
				'object_id' => 1,
				'type'      => 'post',
			]
		)
		);
	}
}
