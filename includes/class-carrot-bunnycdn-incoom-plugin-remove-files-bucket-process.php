<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Remove files bucket Background Process
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      2.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Remove_Files_Bucket_Process extends carrot_bunnycdn_incoom_plugin_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'remove_files_from_bucket';

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		if(!$this->check_action()){
			$this->unschedule();
		}else{
			add_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_remove_files_from_bucket', [ $this, 'task' ] );
		}
	}

	public function check_action(){
		$action_scan = get_option('incoom_carrot_bunnycdn_incoom_plugin_action');
		if($action_scan == 'remove_files_from_bucket'){
			return true;
		}

		return false;
	}

	public function unschedule(){
		if ( function_exists( 'as_next_scheduled_action' ) ) {
			carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_remove_files_from_bucket' );
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
				carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_download_files_from_bucket' );
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
			$this->handle_item( $source_type, $source_id, $blog_id );
		}

		return $processed;
	}

	/**
	 * Remove the item to provider.
	 *
	 * @param string $source_type
	 * @param int    $source_id
	 * @param int    $blog_id
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function handle_item( $source_type, $source_id, $blog_id ) {
		$results = false;

		switch($source_type){
			case "media-library":
				$results = carrot_bunnycdn_incoom_plugin_remove_from_s3_function( $source_id );
				break;
			case "bboss-user-avatar":
			case "bboss-user-cover":
			case "bboss-group-avatar":
			case "bboss-group-cover":
				if ( incoom_carrot_bunnycdn_incoom_plugin_is_bb_activate() ) {
					$source_types 	= carrot_bunnycdn_incoom_plugin_Buddyboss::get_resource_type();
					$class 			= $source_types[$source_type]['class'];
					$carrot_item 	= $class::get_by_source_id( $source_id );

					try {
						if(empty( $carrot_item->id() )){
							$carrot_item = $class::create_from_source_id( $source_id );
						}
					} catch( Exception $e){}

					$remove_provider_handler = carrot_bunnycdn_incoom_plugin_get_item_handler( carrot_bunnycdn_incoom_plugin_Remove_Provider_Handler::get_item_handler_key_name() );
					$results = $remove_provider_handler->handle( $carrot_item );

					if ( is_wp_error( $results ) ) {
						return false;
					}
				}
				break;
			default:
				break;
		}
		
		return $results;
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
		if($media_count['offloaded'] == 0){
			$this->complete();
		}

		$this->lock_process();

		try {
			$blog_id = get_current_blog_id();
			$source_type_classes = carrot_bunnycdn_incoom_plugin_get_source_type_classes();
			foreach($source_type_classes as $source_type => $class){
				$items = $class::get_source_ids(null, $this->limit, false);
				$chunks = array_chunk( $items, $this->chunk );
				foreach ( $chunks as $chunk ) {
					try {
						$this->process_items_chunk($source_type, $chunk, $blog_id);
					} catch (\Throwable $th) {
						error_log("Error remove_files_from_bucket: ". $th->getMessage());
					}
				}
			}	
		} catch (\Throwable $th) {
			error_log("Error remove_files_from_bucket: ". $th->getMessage());
		}

		$this->unlock_process();

		return false;
	}
}
?>