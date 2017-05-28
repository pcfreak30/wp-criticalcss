<?php

class Test_WP_CriticalCSS_Cache_Manager extends WP_CriticalCSS_TestCase {


	public function test_update_cache_fragment() {
		WPCCSS()->set_settings( array(
			'template_cache'     => 'off',
			'web_check_interval' => MINUTE_IN_SECONDS,
		) );
		$post = $this->factory->post->create_and_get();
		$this->go_to( get_permalink( $post->ID ) );
		$type = WPCCSS()->get_current_page_type();
		$hash = WPCCSS()->get_item_hash( $type );
		WPCCSS()->update_cache_fragment( array( $hash ), true );
		$this->assertTrue( WPCCSS()->get_cache_fragment( array( $hash ) ) );
	}
}
