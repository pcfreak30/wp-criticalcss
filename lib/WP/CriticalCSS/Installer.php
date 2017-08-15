<?php


namespace WP\CriticalCSS;

use pcfreak30\WordPress\Plugin\Framework\ComponentAbstract;

class Installer extends ComponentAbstract {

	/**
	 *
	 */
	public function init() {

	}

	public function activate() {
		$wpdb        = $this->wpdb;
		$settings    = $this->plugin->settings_manager->get_settings();
		$no_version  = ( ! empty( $settings ) && empty( $settings['version'] ) ) || empty( $settings );
		$version_0_3 = false;
		$version_0_4 = false;
		$version_0_5 = false;
		if ( ! $no_version ) {
			$version     = $settings['version'];
			$version_0_3 = version_compare( '0.3.0', $version ) === 1;
			$version_0_4 = version_compare( '0.4.0', $version ) === 1;
			$version_0_5 = version_compare( '0.5.0', $version ) === 1;
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
				$wpdb->query( "DROP TABLE {$wpdb->prefix}_wp_criticalcss_web_check_queue IF EXISTS" );
				$wpdb->query( "DROP TABLE {$wpdb->prefix}_wp_criticalcss_api_queue IF EXISTS" );
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
		$this->create_processed_table();

		flush_rewrite_rules();
	}

	private function create_processed_table() {
		$wpdb = $this->wpdb;
		if ( ! function_exists( 'dbDelta' ) ) {
			include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();
		if ( is_multisite() ) {
			$table = "{$wpdb->base_prefix}{$this->plugin->get_safe_slug()}_processed_items";
		} else {
			$table = "{$wpdb->prefix}{$this->plugin->get_safe_slug()}";
		}
		$sql = "CREATE TABLE $table (
  id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
  template  VARCHAR(255),
  object_id  BIGINT(10),
  type VARCHAR (10),
  url TEXT,";
		if ( is_multisite() ) {
			$sql .= "\n" . 'blog_id BIGINT(20),';
		}
		dbDelta( "$sql\nPRIMARY KEY  (id)\n) {$charset_collate};" );
	}

	public function deactivate() {
		flush_rewrite_rules();
	}
}