<?php

namespace Cebola\Classes;

class PostTypes {

	public function __construct() {
		add_action( 'init', array( $this, 'cebola_functions_post_type' ) );
	}

	// Register custom post type
	public function cebola_functions_post_type() {

		// Set up labels
		$labels = array(
			'name'               => 'CEBOLA Functions',
			'singular_name'      => 'CEBOLA Function',
			'add_new_item'       => 'Add New CEBOLA Function',
			'edit_item'          => 'Edit CEBOLA Function',
			'new_item'           => 'New CEBOLA Function',
			'view_item'          => 'View CEBOLA Function',
			'search_items'       => 'Search CEBOLA Functions',
			'not_found'          => 'No CEBOLA Functions found',
			'not_found_in_trash' => 'No CEBOLA Functions found in Trash',
		);

		// Set up args
		$args = array(
			'labels'                => $labels,
			'public'                => true,
			'has_archive'           => true,
			'menu_icon'             => 'dashicons-hammer',
			'supports'              => array( 'title', 'editor' ),
			'rewrite'               => array( 'slug' => 'cebola-functions' ),
			'capability_type'       => 'post',
			'show_in_rest'          => true,
			'rest_base'             => 'cebola-functions',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'register_meta_box_cb'  => 'add_cebola_functions_meta_boxes',
		);

		// Register post type
		register_post_type( 'cebola_functions', $args );

	}

}
