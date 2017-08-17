<?php


namespace WP\CriticalCSS\Testing\Unit\CriticalCSS\Background;


use WP\CriticalCSS\Testing\Unit\TestCase;

class ProcessTest extends TestCase {
	public function test_create_table() {
		global $wpdb;
		$output = null;
		\WP_Mock::userFunction( 'dbDelta', [
			'times'  => 1,
			'return' => function ( $input ) use ( &$output ) {
				$output = $input;
			},
		] );
		\WP_Mock::userFunction( 'is_multisite', [ 'return' => false ] );
		$wpdb = \Mockery::mock( 'wpdb' );
		$wpdb->shouldReceive( 'get_charset_collate' )->andSet( 'prefix', 'wp_' )->andReturn( 'DEFAULT CHARACTER SET utf8mb4' );
		$instance = new ProcessMock();
		$instance->create_table();
		$this->assertContains( 'wp_test_queue', $output );
	}

	public function test_create_table_multisite() {
		global $wpdb;
		$output = null;
		\WP_Mock::userFunction( 'dbDelta', [
			'times'  => 1,
			'return' => function ( $input ) use ( &$output ) {
				$output = $input;
			},
		] );
		\WP_Mock::userFunction( 'is_multisite', [ 'return' => true ] );
		$wpdb = \Mockery::mock( 'wpdb' );
		$wpdb->shouldReceive( 'get_charset_collate' )->andSet( 'base_prefix', 'wp_' )->andSet( 'prefix', 'wp_1' )->andReturn( 'DEFAULT CHARACTER SET utf8mb4' );
		$instance = new ProcessMock();
		$instance->create_table();
		$this->assertContains( 'wp_test_queue', $output );
	}
}