<?php
namespace Cebola;

// error_reporting( E_ALL );
// ini_set( 'display_errors', 1 );

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/classes/class-parser.php';
require __DIR__ . '/classes/class-injections.php';
require __DIR__ . '/classes/class-functions.php';
require __DIR__ . '/classes/class-endpoints.php';
require __DIR__ . '/classes/class-report-list-table.php';
require __DIR__ . '/classes/class-admin-menu.php';


new Classes\Functions();
new Classes\Injections();
new Classes\Endpoints();
new Classes\Admin_Menu();

function admin_header() {
	$page = ( isset( $_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
	if ( 'cebola-functions-list' != $page ) {
		return;
	}

	echo '<style type="text/css">';
	echo '.wp-list-table .column-type-hook { width: 25%; }';
	echo '.wp-list-table .column-callback { width: 15%; }';
	echo '.wp-list-table .column-priority { width: 5%; }';
	echo '.wp-list-table .column-arguments { width: 5%; }';
	echo '.wp-list-table .column-file { width: 10%; }';
	echo '.wp-list-table .column-line { width: 5%; }';
	echo '.wp-list-table .column-attention { width: 5%; }';
	echo '</style>';
}

add_action( 'admin_head', __NAMESPACE__ . '\admin_header' );
