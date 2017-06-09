<?php
define( 'ABSPATH', '/tmp/wordpress/' );
// Initialize composer
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

WP_Mock::bootstrap();

require dirname( __DIR__ ) . '/wp-criticalcss.php';
wpccss_container( 'unit_test' );