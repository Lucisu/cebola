<?php

namespace Cebola\Classes;

class Logger {

	public $is_fresh = true;
	public $conn;

	public static function print( $message = '', $color = '' ) {
		if ( false === defined('CEBOLA_VERBOSE') || CEBOLA_VERBOSE < 1 ) {
			return;
		}
		if ( empty( $color ) ) {
			echo $message . "\n";
		} else {
			echo "$color$message \033[0m";
		}
	}

	public static function success( $message = '' ) {
		self::print( '[Success]', "\033[32m" );
		self::print( $message );
	}

	public static function error( $message = '', $die = true ) {
		self::print( '[Error]', "\033[31m" );
		self::print( $message );
		if ( $die ) {
			die;
		}
	}

	public static function warning( $message = '' ) {
		if ( CEBOLA_VERBOSE < 3 ) {
			return;
		}
		self::print( '[Warning]', "\033[33m" );
		self::print( $message );
	}

	public static function info( $message = '' ) {
		if ( CEBOLA_VERBOSE < 2 ) {
			return;
		}
		self::print( '[Info]', "\033[36m" );
		self::print( $message );
	}
}
