<?php

use pcfreak30\WordPress\Cache\Store;

class StoreTest extends PHPUnit_Framework_TestCase {
	public function test_update_cache_fragment() {

		$store = new Store( 'test_prefix', 60, 50 );

		$this->assertTrue( $store->update_cache_fragment( [ 'test' ], true ) );
		$this->assertTrue( $store->get_cache_fragment( [ 'test' ] ) );
	}

	public function test_delete_cache_branch() {
		$store = new Store( 'test_prefix', 60, 50 );

		$store->update_cache_fragment( [ 'test' ], true );
		$this->assertTrue( $store->delete_cache_branch() );
		$this->assertFalse( $store->get_cache_fragment( [] ) );
	}

	public function test_delete_cache_leaf() {
		$store = new Store( 'test_prefix', 60, 50 );

		$store->update_cache_fragment( [ 'test' ], true );
		$this->assertTrue( $store->delete_cache_leaf( [ 'test' ] ) );
		$this->assertFalse( $store->get_cache_fragment( [ 'test' ] ) );
	}

	public function test_get_cache_fragment() {
		$store = new Store( 'test_prefix', 60, 50 );

		$store->update_cache_fragment( [ 'test' ], true );
		$this->assertTrue( $store->get_cache_fragment( [ 'test' ] ) );
	}

	protected function setUp() {
		parent::setUp();
	}
}
