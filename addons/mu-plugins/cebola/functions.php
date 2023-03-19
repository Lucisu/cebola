<?php

function is_cebola_plugin( $hook_name = '', $callback = '' ) {
	$ignore_hooks     = array();
	$ignore_callbacks = array( '_future_post_hook', '_wp_ajax_add_hierarchical_term' );

	if ( in_array( $hook_name, $ignore_hooks, true ) || in_array( $callback, $ignore_callbacks, true ) ) {
		return;
	}

	$debug = debug_backtrace();

	$checks = 10;
	foreach ( $debug as $key => $value ) {
		if ( 0 === $checks ) {
			break;
		}
		if ( ! empty( $value['file'] ) && str_starts_with( $value['file'], CEBOLA_TESTING_PLUGIN ) ) {
			return $value;
		}
		$checks--;
	}
	return false;
}
