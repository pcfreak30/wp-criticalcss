<?php


namespace WP\CriticalCSS\Integration;


class Kinsta extends IntegrationAbstract {

	/**
	 * Kinsta constructor.
	 */
	public function init() {
		if ( isset( $_SERVER['KINSTA_CACHE_ZONE'] ) ) {
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
			$url = trailingslashit( $url ) . 'kinsta-clear-cache/';
			wp_remote_get( $url, array(
				'blocking' => false,
				'timeout'  => 0.01,
			) );
		}
		sleep( 1 );
	}

	public function disable_cache() {
		$permalink = $this->plugin->get_permalink( $this->plugin->request->get_current_page_type() );
		$permalink = trailingslashit( $permalink ) . 'kinsta-clear-cache/';
		wp_remote_get( $permalink, array(
			'blocking' => false,
			'timeout'  => 0.01,
		) );
	}
}
