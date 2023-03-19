<?php

namespace Cebola\Classes;

// Include the necessary WordPress core files
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Cebola_Functions_List_Table extends \WP_List_Table {

	// Define constructor
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'cebola_function',
				'plural'   => 'cebola_functions',
				'ajax'     => false,
			)
		);
	}

	// Define columns
	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox" />',
			'id'        => __( 'ID', 'mylisttable' ),
			'type'      => __( 'Type', 'mylisttable' ),
			'hook'      => __( 'Hook', 'mylisttable' ),
			'callback'  => __( 'Callback', 'mylisttable' ),
			'priority'  => __( 'Priority', 'mylisttable' ),
			'arguments' => __( 'Arguments', 'mylisttable' ),
			'file'      => __( 'File', 'mylisttable' ),
			'line'      => __( 'Line', 'mylisttable' ),
			'attention' => __( 'Attention', 'mylisttable' ),
		);
	}

	// Define sortable columns
	public function get_sortable_columns() {
		return array(
			'attention' => array( 'attention', false ),
		);
	}

	// Define bulk actions
	public function get_bulk_actions() {
		return array(
			'delete' => 'Delete',
		);
	}

	// Define table data
	public function prepare_items() {
		global $wpdb;

		// Set table name and per page options
		$table_name = 'cebola_functions';
		$per_page   = 10;

		// Define columns
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Define bulk actions
		$this->process_bulk_action();

		// Define data
		$data = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table_name ORDER BY id DESC LIMIT %d" . '', $per_page ),
		);

		// Define current page
		$current_page = $this->get_pagenum();

		// Create new instance of WP_List_Table
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
		$total_items           = count( $data );

		// Define pagination
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
		$data        = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->items = $data;
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'type':
			case 'hook':
			case 'callback':
			case 'priority':
			case 'arguments':
			case 'file':
			case 'line':
			case 'attention':
				return $item->$column_name;
			default:
				return print_r( $item, true );

		}
	}
}
