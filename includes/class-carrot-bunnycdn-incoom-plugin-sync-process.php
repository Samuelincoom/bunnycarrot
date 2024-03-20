<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Sync Background Process
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      2.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Sync_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		add_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_sync_between_cloud', [ $this, 'task' ] );
	}
	
	public function task() {

		if(!carrot_bunnycdn_incoom_plugin_cronjob_timed()){
			return false;
		}

		$status = get_option('incoom_carrot_bunnycdn_incoom_plugin_synced_status', 0);
		if($status == 0){
			try {
				carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_sync_between_cloud' );
			} catch (\Throwable $th) {
				error_log($th->getMessage());
			}
			return false;
		}

		$sync = new carrot_bunnycdn_incoom_plugin_Sync();
		$cacheKey = $sync->getCacheKey();
		$cacheData = $sync->getCacheData();
		if(count($cacheData) == 0){
			$this->complete();
		}

		$items = $this->get_cache_objects("{$cacheKey}_copy");
		if(!is_array($items)){
			return false;
		}

		if(count($items) == 0){
			$this->complete();
		}

		$item = $items[0];

		if(!is_array($item)){
			return false;
		}

		if(!isset($item['source']) || !isset($item['key'])){
			return false;
		}

		error_log("Start sync data {$item['source']}");

		array_splice($items, 0, 1);
		$this->set_cache_objects("{$cacheKey}_copy", $items);

		$total_synced = $this->get_cache_objects("{$cacheKey}_synced_data");
		if(!is_array($total_synced)){
			$total_synced = [];
		}

		$url = $sync->sync($item);

		error_log("End sync data {$item['source']}");

		if(!in_array($item['key'], $total_synced)){
			$total_synced[] = $item['key'];
			$this->set_cache_objects("{$cacheKey}_synced_data", $total_synced);
		}

		if(count($items) == 0){
			$this->complete();
		}
		
		return false;
	}

	public function complete() {
		
		update_option('incoom_carrot_bunnycdn_incoom_plugin_sync_data', 1);
		update_option('incoom_carrot_bunnycdn_incoom_plugin_synced_status', 0);

		try{
			$sendEmail = get_option('incoom_carrot_bunnycdn_incoom_plugin_send_email_task', 'on');
			if(!empty($sendEmail)){
				wp_mail( 
					get_option('admin_email'), 
					esc_html__('carrot-bunnycdn-incoom-plugin Synchronize', 'carrot-bunnycdn-incoom-plugin'), 
					esc_html__('carrot-bunnycdn-incoom-plugin Synchronize: process has been completed.', 'carrot-bunnycdn-incoom-plugin') 
				);
			}
		} catch (Exception $e){
			error_log('wp_mail send failed.');
		}

		try {
			if ( function_exists( 'carrot_bunnycdn_incoom_plugin_unschedule_action' ) ) {
				carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_sync_between_cloud' );
			}
		} catch (\Throwable $th) {}

		return false;
	}

	private function get_cache_objects($key)
	{
		return carrot_bunnycdn_incoom_plugin_get_sync_objects($key);
	}

	private function set_cache_objects($key, $objects)
	{
		return carrot_bunnycdn_incoom_plugin_set_sync_objects($key, $objects);
	}
}
?>