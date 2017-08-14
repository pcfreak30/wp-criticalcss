<?php
define( 'ABSPATH', '/tmp/wordpress/' );
// Initialize composer
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

WP_Mock::bootstrap();
\WP_Mock::userFunction( 'register_activation_hook' );
\WP_Mock::userFunction( 'register_deactivation_hook' );

require dirname( __DIR__ ) . '/wp-criticalcss.php';
wp_criticalcss_container( 'unit_test' );