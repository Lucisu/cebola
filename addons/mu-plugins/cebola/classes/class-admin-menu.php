<?php

namespace Cebola\Classes;

use Cebola\Classes\Cebola_Functions_List_Table;

class Admin_Menu {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'cebola_functions_submenu_page' ) );
	}

	public function cebola_functions_submenu_page() {
		add_submenu_page(
			'tools.php',
			'Cebola Functions',
			'Cebola Functions',
			'manage_options',
			'cebola-functions-list',
			array( $this, 'cebola_functions_list_table_render' )
		);
	}

	// Callback function to render the list table
	public function cebola_functions_list_table_render() {

		echo '<h1>Cebola Functions</h1><div style="padding-right: 20px;">';

		$functions_list_table = new Cebola_Functions_List_Table();
		$functions_list_table->prepare_items();
		$functions_list_table->display();

		echo '</div>';
	}

}
