<?php

namespace WP\Testing\Unit\CriticalCSS\Queue;

use WP\CriticalCSS\Testing\Unit\TestCase;

class ListTableTest extends TestCase {

	public function test_get_columns_no_multisite() {
		$keys = [
			'url',
			'template',
			'status',
			'queue_position',
		];
		\WP_Mock::userFunction( 'add_screen_option', [ 'times' => 1 ] );
		\WP_Mock::userFunction( 'is_multisite', [
			'times'  => count( $keys ),
			'return' => function () {
				return false;
			},
		] );
		WPCCSS()->get_admin_ui()->screen_option();
		foreach (
			$keys as $key
		) {
			$this->assertArrayHasKey( $key, WPCCSS()->get_admin_ui()->get_queue_table()->get_columns() );
		}
	}

	public function test_get_columns_multisite() {
		$keys = [
			'url',
			'template',
			'status',
			'queue_position',
			'blog_id',
		];
		\WP_Mock::userFunction( 'add_screen_option', [ 'times' => 1 ] );
		\WP_Mock::userFunction( 'is_multisite', [
			'times'  => count( $keys ),
			'return' => function () {
				return true;
			},
		] );
		WPCCSS()->get_admin_ui()->screen_option();
		foreach (
			$keys as $key
		) {
			$this->assertArrayHasKey( $key, WPCCSS()->get_admin_ui()->get_queue_table()->get_columns() );
		}
	}
}
