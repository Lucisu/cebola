<?php
namespace Cebola\Classes;

class Injections {

	public $injections = array(
		'__CEBOLA__"`'
	);

	public function __construct() {
		$this->set_hooks();
		$this->add_mocks();
		add_action( 'init', array( $this, 'add_permissions' ), 1 );
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

	public function add_mocks() {
		// $_REQUEST = array_merge( $_GET, $_POST );
		// $_POST    = new VariableMock( $_POST );
		// $_GET     = new VariableMock( $_GET );
		// $_REQUEST = new VariableMock( $_REQUEST );
	}

	public function add_permissions() {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		
		global $wpdb;
		
		$running = $wpdb->get_var( 'SELECT value FROM `cebola_meta` WHERE name = "xsstrike" ORDER BY id DESC LIMIT 1' );
		if ( ! empty( $running ) && (int) $running > 1 ) {
			define( 'CEBOLA_RUNNING_XSSTRIKE', $running );
			wp_clear_auth_cookie();
			wp_set_current_user ( 1 );
			wp_set_auth_cookie  ( 1 );
		}
	}

	public function add_report(  ) {

	}
}
