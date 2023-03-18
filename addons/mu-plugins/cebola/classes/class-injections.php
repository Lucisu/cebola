<?php
namespace Cebola\Classes;

class Injections {

	public $injections = array(
		'__CEBOLA__"`'
	);

	public function __construct() {
		// Needed to manually access the API location http://localhost:8000/index.php?rest_route=/
		$this->set_hooks();
	}

	private function set_hooks() {
		$class = $this;
		uopz_set_hook(
			'wpdb',
			'query',
			function( $sql ) use ( $class ) {
				foreach ( $class->injections as $key => $value ) {
					if ( str_contains( $sql, $value ) ) {
						$class->add_report();
					}
				}
			}
		);
	}

	public function add_report(  ) {

	}
}
