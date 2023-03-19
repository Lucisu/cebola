<?php
namespace Cebola;
error_reporting(E_ALL);ini_set('display_errors', 1);

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/classes/class-parser.php';
require __DIR__ . '/classes/class-injections.php';
require __DIR__ . '/classes/class-functions.php';

new Classes\Functions();
new Classes\Injections();
