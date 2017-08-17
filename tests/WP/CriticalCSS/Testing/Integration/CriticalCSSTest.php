<?php

namespace WP\Testing\Integration;

use WP\CriticalCSS;
use WP\CriticalCSS\Testing\Integration\TestCase;

class CriticalCSSTest extends TestCase {

	public function test_wp_criticalcss() {
		$instance = wp_criticalcss();

		$this->assertInstanceOf( '\WP\CriticalCSS', $instance );
	}

	public function test_wp_head_with_nocache() {
		wp_criticalcss()->init();
		wp_criticalcss()->get_request()->add_rewrite_rules();
		flush_rewrite_rules();
		$this->go_to( home_url( '/nocache' ) );
		ob_start();
		wp_criticalcss()->get_frontend()->wp_head();
		$result = ob_get_clean();
		$this->assertEquals( '<meta name="robots" content="noindex, nofollow"/>', trim( $result ) );
	}

	public function test_wp_head_without_nocache() {
		wp_criticalcss()->init();
		wp_criticalcss()->get_request()->add_rewrite_rules();
		$this->go_to( home_url() );
		ob_start();
		wp_criticalcss()->get_frontend()->wp_head();
		$result = ob_get_clean();
		$this->assertEmpty( $result );
	}

	public function test_redirect_canonical_with_nocache_query_var() {
		wp_criticalcss()->init();
		wp_criticalcss()->get_request()->add_rewrite_rules();
		flush_rewrite_rules();
		$this->go_to( home_url( '/nocache' ) );
		$this->assertFalse( wp_criticalcss()->get_request()->redirect_canonical( home_url() ) );
	}

	public function test_parse_request() {
		wp_criticalcss()->init();
		$wp                        = new \WP();
		$wp->query_vars['nocache'] = true;
		wp_criticalcss()->get_request()->parse_request( $wp );
		$this->assertArrayNotHasKey( 'nocache', $wp->query_vars );
		$this->assertTrue( wp_criticalcss()->get_request()->is_no_cache() );
	}

	public function test_query_vars() {
		$this->assertContains( 'nocache', wp_criticalcss()->get_request()->query_vars( [] ) );
	}

	public function test_get_settings() {
		update_option( CriticalCSS::OPTIONNAME, [ 'version' => CriticalCSS::VERSION ] );
		$result = wp_criticalcss()->get_settings_manager()->get_settings();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( 'array', $result );
	}

	public function test_get_settings_empty() {
		wp_criticalcss()->get_settings_manager()->update_settings( [] );
		$this->assertEmpty( array_filter( wp_criticalcss()->get_settings_manager()->get_settings() ) );
	}

	public function test_get_settings_multisite() {
		update_site_option( CriticalCSS::OPTIONNAME, [ 'version' => CriticalCSS::VERSION ] );
		$result = wp_criticalcss()->get_settings_manager()->get_settings();
		$this->assertInternalType( 'array', $result );
		$this->assertNotEmpty( 'array', $result );
	}

	public function test_update_settings() {
		$this->require_normal();
		$settings = [ 'version' => CriticalCSS::VERSION ];
		delete_option( CriticalCSS::OPTIONNAME );
		wp_criticalcss()->get_settings_manager()->update_settings( $settings );
		$this->assertEquals( $settings, get_option( CriticalCSS::OPTIONNAME ) );
	}


	public function test_update_settings_multisite() {
		$this->require_multisite();
		$settings = [ 'version' => CriticalCSS::VERSION ];
		delete_site_option( CriticalCSS::OPTIONNAME );
		wp_criticalcss()->get_settings_manager()->update_settings( $settings );
		$this->assertEquals( $settings, get_site_option( CriticalCSS::OPTIONNAME ) );
	}


	public function test_init_template_cache_on() {
		wp_criticalcss()->get_settings_manager()->update_settings( [ 'template_cache' => 'on' ] );
		wp_criticalcss()->init();
		$this->assertEquals(
			PHP_INT_MAX, has_action(
				'template_include', [
					wp_criticalcss()->get_request(),
					'template_include',
				]
			)
		);
	}

	public function test_get_permalink_post() {
		$post      = $this->factory->post->create_and_get();
		$permalink = wp_criticalcss()->get_permalink( [
			'type'      => 'post',
			'object_id' => $post->ID,
		] );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_permalink_term() {
		$term      = $this->factory->term->create_and_get();
		$permalink = wp_criticalcss()->get_permalink( [
			'type'      => 'term',
			'object_id' => $term->term_id,
		] );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_permalink_author() {
		$permalink = wp_criticalcss()->get_permalink( [
			'type'      => 'author',
			'object_id' => 1,
		] );
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

	public function test_get_permalink_url() {
		$permalink = wp_criticalcss()->get_permalink(
			[
				'type' => 'url',
				'url'  => home_url( '/testabc/testabc/testabc/' ),
			]
		);
		$this->assertNotFalse( $permalink );
		$this->assertContains( 'nocache/', $permalink );
	}

}
