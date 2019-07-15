<?php


namespace WP\CriticalCSS\Integration;


class Kinsta extends IntegrationAbstract {

	private $kinsta_cache;

	/**
	 * Kinsta constructor.
	 */
	public function init() {
		if ( class_exists( '\Kinsta\Cache' ) ) {
			add_action( 'kinsta_cache_init', [ $this, 'set_kinsta_cache' ] );
			parent::init();
		}
	}

	/**
	 *
	 */
	public function enable() {
		add_action( 'wp_criticalcss_purge_cache', [
			$this,
			'purge_cache',
		], 10, 3 );
		add_action( 'wp_criticalcss_nocache', [
			$this,
			'disable_cache',
		] );
	}

	/**
	 * @return void
	 */
	public function disable() {

	}

	/**
	 * @SuppressWarnings(PHPMD)
	 * @param null $type
	 * @param null $object_id
	 * @param null $url
	 */
	public function purge_cache( $type = null, $object_id = null, $url = null ) {
		if ( empty( $type ) ) {
			/** @noinspection PhpUndefinedClassInspection */
			$this->kinsta_cache->kinsta_cache_purge->purge_complete_caches();
		} elseif ( 'post' === $type ) {
			/** @noinspection PhpUndefinedClassInspection */
			$this->kinsta_cache->kinsta_cache_purge->initiate_purge( $object_id, 'post' );
		} else {
			$this->purge_url( $url );
		}
		sleep( 1 );
	}

	private function purge_url( $url ) {
		$purge = [
			'single' => [ 'custom|0' => trailingslashit( $url ) ],
		];
		$purge = $this->kinsta_cache->kinsta_cache_purge->convert_purge_list_to_request( $purge );
		wp_remote_post(
			$this->kinsta_cache->config['immediate_path'],
			array(
				'sslverify' => false,
				'timeout'   => 5,
				'body'      => $purge,
			)
		);
	}

	public function disable_cache() {
		$permalink = $this->plugin->get_permalink( $this->plugin->request->get_current_page_type() );
		$this->purge_url( $permalink );
	}

	/**
	 * @param mixed $kinsta_cache
	 */
	public function set_kinsta_cache( $kinsta_cache ) {
		$this->kinsta_cache = $kinsta_cache;
	}
}
