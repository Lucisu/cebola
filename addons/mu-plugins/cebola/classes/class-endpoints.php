<?php

namespace Cebola\Classes;

class Endpoints {

	public function __construct() {
		add_action(
			'rest_api_init',
			array( $this, 'register_routes' )
		);
	}

	public function register_routes() {
		register_rest_route(
			'cebola/v1',
			'/functions',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_cebola_functions' ),
			)
		);
	}

	public function get_cebola_functions() {
		global $wpdb;
		$results = $wpdb->get_results( 'SELECT * FROM cebola_functions', OBJECT );
		return $results;
	}

}
