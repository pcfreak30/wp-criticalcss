<?php


namespace WP\CriticalCSS;

use ComposePress\Core\Abstracts\Component;

/**
 * Class Installer
 *
 * @package WP\CriticalCSS
 * @property \WP\CriticalCSS $plugin
 */
class Installer extends Component {

	/**
	 *
	 */
	public function init() {

	}

	/**
	 *
	 */
	public function activate() {
		$wpdb        = $this->wpdb;
		$settings    = $this->plugin->settings_manager->get_settings();
		$no_version  = ( ! empty( $settings ) && empty( $settings['version'] ) ) || empty( $settings );
		$version_0_3 = false;
		$version_0_4 = false;
		$version_0_5 = false;
		$version_0_7 = false;
		if ( ! $no_version ) {
			$version       = $settings['version'];
			$version_0_3   = version_compare( '0.3.0', $version ) === 1;
			$version_0_4   = version_compare( '0.4.0', $version ) === 1;
			$version_0_5   = version_compare( '0.5.0', $version ) === 1;
			$version_0_7   = version_compare( '0.7.0', $version ) === 1;
			$version_0_7_1 = version_compare( '0.7.1', $version ) === 1;
		}
		if ( $no_version || $version_0_3 || $version_0_4 ) {
			remove_action(
				'update_option_criticalcss', [
					$this,
					'after_options_updated',
				]
			);
			if ( isset( $settings['disable_autopurge'] ) ) {
				unset( $settings['disable_autopurge'] );
				$this->plugin->settings_manager->update_settings( $settings );
			}
			if ( isset( $settings['expire'] ) ) {
				unset( $settings['expire'] );
				$this->plugin->settings_manager->update_settings( $settings );
			}
		}
		if ( $no_version || $version_0_3 || $version_0_4 || $version_0_5 ) {
			$wpdb->get_results( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_criticalcss_%', '_transient_timeout_criticalcss_%' ) );
		}

		if ( $version_0_7 ) {
			$items = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key like %s ", "{$wpdb->esc_like( 'criticalcss' ) }%" ) );
			foreach ( $items as $item ) {
				$new_item = "wp_{$item}";
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s ", $new_item, $item ) );
			}
			$items = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->termmeta} WHERE meta_key like %s ", "{$wpdb->esc_like( 'criticalcss' ) }%" ) );
			foreach ( $items as $item ) {
				$new_item = "wp_{$item}";
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->termmeta} SET meta_key = %s WHERE meta_key = %s ", $new_item, $item ) );
			}
		}

		if ( $version_0_7_1 ) {
			if ( is_multisite() ) {
				$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->base_prefix}{$this->plugin->get_safe_slug()}_processed_items" );
			} else {
				$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$this->plugin->get_safe_slug()}" );

			}
		}

		if ( is_multisite() ) {
			foreach (
				get_sites(
					[
						'fields'       => 'ids',
						'site__not_in' => [ 1 ],
					]
				) as $blog_id
			) {
				switch_to_blog( $blog_id );
				$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}_wp_criticalcss_web_check_queue" );
				$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}_wp_criticalcss_api_queue" );
				restore_current_blog();
			}
		}

		$this->plugin->settings_manager->update_settings(
			array_merge(
				[
					'web_check_interval' => DAY_IN_SECONDS,
					'template_cache'     => 'off',
				], $this->plugin->settings_manager->get_settings(), [
					'version' => $this->plugin->get_version(),
				]
			)
		);

		$this->plugin->request->add_rewrite_rules();

		$this->plugin->web_check_queue->create_table();
		$this->plugin->api_queue->create_table();
		$this->plugin->log->create_table();
		$this->plugin->template_log->create_table();

		flush_rewrite_rules();
	}

	/**
	 *
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}
}