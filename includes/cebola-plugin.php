<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

global $wpdb;

$cebola_plugin = $wpdb->get_var( 'SELECT value FROM cebola_meta WHERE name = "plugin"' );

if ( empty( $cebola_plugin ) || ! function_exists( 'uopz_allow_exit' ) ) {
	return;
}

define( 'CEBOLA_TESTING_PLUGIN', '/var/www/html/wp-content/plugins/' . $cebola_plugin );

uopz_allow_exit( true );

require __DIR__ . '/cebola/cebola.php';
