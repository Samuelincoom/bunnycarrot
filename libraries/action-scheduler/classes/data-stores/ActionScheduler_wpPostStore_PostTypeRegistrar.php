<?php

/**
 * Class ActionScheduler_wpPostStore_PostTypeRegistrar
 * @codeCoverageIgnore
 */
class ActionScheduler_wpPostStore_PostTypeRegistrar {
	public function register() {
		register_post_type( ActionScheduler_wpPostStore::POST_TYPE, $this->post_type_args() );
	}

	/**
	 * Build the args array for the post type definition
	 *
	 * @return array
	 */
	protected function post_type_args() {
		$args = array(
			'label' => __( 'Scheduled Actions','carrot-bunnycdn-incoom-plugin' ),
			'description' => __( 'Scheduled actions are hooks triggered on a cetain date and time.','carrot-bunnycdn-incoom-plugin' ),
			'public' => false,
			'map_meta_cap' => true,
			'hierarchical' => false,
			'supports' => array('title', 'editor','comments'),
			'rewrite' => false,
			'query_var' => false,
			'can_export' => true,
			'ep_mask' => EP_NONE,
			'labels' => array(
				'name' => __( 'Scheduled Actions','carrot-bunnycdn-incoom-plugin' ),
				'singular_name' => __( 'Scheduled Action','carrot-bunnycdn-incoom-plugin' ),
				'menu_name' => _x( 'Scheduled Actions', 'Admin menu name','carrot-bunnycdn-incoom-plugin' ),
				'add_new' => __( 'Add','carrot-bunnycdn-incoom-plugin' ),
				'add_new_item' => __( 'Add New Scheduled Action','carrot-bunnycdn-incoom-plugin' ),
				'edit' => __( 'Edit','carrot-bunnycdn-incoom-plugin' ),
				'edit_item' => __( 'Edit Scheduled Action','carrot-bunnycdn-incoom-plugin' ),
				'new_item' => __( 'New Scheduled Action','carrot-bunnycdn-incoom-plugin' ),
				'view' => __( 'View Action','carrot-bunnycdn-incoom-plugin' ),
				'view_item' => __( 'View Action','carrot-bunnycdn-incoom-plugin' ),
				'search_items' => __( 'Search Scheduled Actions','carrot-bunnycdn-incoom-plugin' ),
				'not_found' => __( 'No actions found','carrot-bunnycdn-incoom-plugin' ),
				'not_found_in_trash' => __( 'No actions found in trash','carrot-bunnycdn-incoom-plugin' ),
			),
		);

		$args = apply_filters('action_scheduler_post_type_args', $args);
		return $args;
	}
}
 