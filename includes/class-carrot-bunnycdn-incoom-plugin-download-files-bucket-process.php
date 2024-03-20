<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Download files bucket Background Process
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      2.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Download_Files_Bucket_Process extends carrot_bunnycdn_incoom_plugin_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'download_files_from_bucket';

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		if(!$this->check_action()){
			$this->unschedule();
		}else{
			add_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_download_files_from_bucket', [ $this, 'task' ] );
		}
	}

	public function check_action(){
		$action_scan = get_option('incoom_carrot_bunnycdn_incoom_plugin_action');
		if($action_scan == 'download_files_from_bucket'){
			return true;
		}

		return false;
	}

	public function unschedule(){
		if ( function_exists( 'as_next_scheduled_action' ) ) {
			carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_download_files_from_bucket' );
		}

		return false;
	}

	public function clearScheduled()
	{
		try {
			if ( function_exists( 'as_next_scheduled_action' ) ) {
				carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_scan_attachments' );
			}
		} catch (\Throwable $th) {}

		try {
			if ( function_exists( 'as_next_scheduled_action' ) ) {
				carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_remove_files_from_server' );
			}
		} catch (\Throwable $th) {}

		try {
			if ( function_exists( 'as_next_scheduled_action' ) ) {
				carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_copy_attachments_to_cloud' );
			}
		} catch (\Throwable $th) {}

		try {
			if ( function_exists( 'as_next_scheduled_action' ) ) {
				carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_remove_files_from_bucket' );
			}
		} catch (\Throwable $th) {}
	}

	/**
	 * Process items chunk.
	 *
	 * @param string $source_type
	 * @param array  $source_ids
	 * @param int    $blog_id
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	protected function process_items_chunk( $source_type, $source_ids, $blog_id ) {
		$processed = [];

		foreach ( $source_ids as $source_id ) {
			carrot_bunnycdn_incoom_plugin_copy_to_server_from_s3_function($source_id);
		}

		return $processed;
	}

	/**
	 * Task
	 * 
	 * @return mixed
	 */
	public function task() {

		$this->clearScheduled();

		if( !$this->can_run() ){
			return false;
		}

		$media_count = carrot_bunnycdn_incoom_plugin_get_media_counts();
		if($media_count['copy_from_cloud'] >= $media_count['total']){
			$this->complete();
		}

		$this->lock_process();

		try {
			$blog_id = get_current_blog_id();
			$source_type_classes = carrot_bunnycdn_incoom_plugin_get_source_type_classes();
			foreach($source_type_classes as $source_type => $class){

				$args = array( 
					'fields'        	=> 'ids',
					'post_type' 		=> 'attachment',
					'post_status' 		=> 'inherit',
					'posts_per_page' 	=> $this->limit,
					'meta_query' 		=> [
						'relation' => 'OR',
						[
							'key'     => '_wp_incoom_carrot_bunnycdn_copy_to_server',
							'value'   => '1',
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => '_wp_incoom_carrot_bunnycdn_copy_to_server',
							'value'   => '1',
							'compare' => '!=',
						],
					]
				);
				$query = new WP_Query($args);
				$found_posts = $query->found_posts;
				if($found_posts > 0){
					$items = [];
					foreach ( carrot_bunnycdn_incoom_plugin_lazy_loop($query) as $post ) {
						$items[] = get_the_ID();
					}
					if(count($items) > 0){
						$chunks = array_chunk( $items, $this->chunk );
						foreach ( $chunks as $chunk ) {
							try {
								$this->process_items_chunk($source_type, $chunk, $blog_id);
							} catch (\Throwable $th) {
								error_log("Error copy_attachments_to_cloud: ". $th->getMessage());
							}
						}
					}
				}
			}
		} catch (\Throwable $th) {
			error_log("Error copy_attachments_to_cloud: ". $th->getMessage());
		}

		$this->unlock_process();

		return false;
	}
}
?>