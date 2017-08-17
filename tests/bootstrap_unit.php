<?php
define( 'ABSPATH', '/tmp/wordpress/' );
// Initialize composer
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

WP_Mock::bootstrap();
require_once ABSPATH . 'wp-includes/class-wp-error.php';
function wp_set_lang_dir() {
	if ( ! defined( 'WP_LANG_DIR' ) ) {
		if ( file_exists( WP_CONTENT_DIR . '/languages' ) && @is_dir( WP_CONTENT_DIR . '/languages' ) || ! @is_dir( ABSPATH . WPINC . '/languages' ) ) {
			/**
			 * Server path of the language directory.
			 *
			 * No leading slash, no trailing slash, full path, not relative to ABSPATH
			 *
			 * @since 2.1.0
			 */
			define( 'WP_LANG_DIR', WP_CONTENT_DIR . '/languages' );
			if ( ! defined( 'LANGDIR' ) ) {
				// Old static relative path maintained for limited backward compatibility - won't work in some cases.
				define( 'LANGDIR', 'wp-content/languages' );
			}
		} else {
			/**
			 * Server path of the language directory.
			 *
			 * No leading slash, no trailing slash, full path, not relative to `ABSPATH`.
			 *
			 * @since 2.1.0
			 */
			define( 'WP_LANG_DIR', ABSPATH . WPINC . '/languages' );
			if ( ! defined( 'LANGDIR' ) ) {
				// Old relative path maintained for backward compatibility.
				define( 'LANGDIR', WPINC . '/languages' );
			}
		}
	}
}

function is_wp_error( $thing ) {
	return ( $thing instanceof WP_Error );
}

wp_set_lang_dir();
\WP_Mock::userFunction( 'register_activation_hook' );
\WP_Mock::userFunction( 'register_deactivation_hook' );

require dirname( __DIR__ ) . '/wp-criticalcss.php';
wp_criticalcss_container( 'unit_test' );