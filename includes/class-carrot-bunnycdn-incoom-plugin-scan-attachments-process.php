<?php
if (!defined('ABSPATH')) {exit;}

/**

 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.22
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Scan_Attachments_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		add_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_scan_attachments', [ $this, 'task' ] );
	}

	public function unschedule(){
		try {
			carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_scan_attachments' );
		} catch (\Throwable $th) {}
		return false;
	}

	public function completed(){

		try {
			carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_scan_attachments' );
		} catch (\Throwable $th) {}

		carrot_bunnycdn_incoom_plugin_scan_attachments_completed();
	}

	/**
	 * Task
	 *
	 * @return mixed
	 */
	public function task() {

		$status = get_option('incoom_carrot_bunnycdn_incoom_plugin_step_scan_attachments', 0);
		if($status != 1){
			$this->unschedule();
		}

		$scanned = get_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_attachments');
		if(!is_array($scanned)){
			$scanned = [];
		}

		$args = array( 
			'fields'        	=> 'ids',
			'post_type' 		=> 'attachment',
			'post_status' 		=> 'inherit',
			'posts_per_page' 	=> 100,
			'meta_query' 		=> [
				[
					'key'     => 'incoom_carrot_bunnycdn_scanned_status',
					'value'   => '1',
					'compare' => 'NOT EXISTS',
				],
			]
		);
		$query = new WP_Query($args);
		$found_posts = $query->found_posts;
		
		if($found_posts == 0){
			
			$this->completed();

			update_option('incoom_carrot_bunnycdn_incoom_plugin_lasted_scan_attachments', strtotime("today"));
			update_option('incoom_carrot_bunnycdn_incoom_plugin_step_scan_attachments', 2);

			$total = count($scanned);
			error_log("Scan completed: {$total} attachments.");
			return false;
		}

		foreach ( carrot_bunnycdn_incoom_plugin_lazy_loop($query) as $post ) {
			ini_set("memory_limit", -1);
			set_time_limit(0);
			$id = get_the_ID();
			$scanned[] = $id;
			update_post_meta($id, 'incoom_carrot_bunnycdn_scanned_status', 1);
		}
	
		update_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_attachments', array_unique($scanned));
		update_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_attachments_copy', array_unique($scanned));

		$scaned = get_option('incoom_carrot_bunnycdn_incoom_plugin_scaned_pages_attachments');
		update_option('incoom_carrot_bunnycdn_incoom_plugin_scaned_pages_attachments', ($scaned + 1) );
		update_option('incoom_carrot_bunnycdn_incoom_plugin_page_scaned_attachments', ($scaned + 1));

		return false;
	}
}
?>