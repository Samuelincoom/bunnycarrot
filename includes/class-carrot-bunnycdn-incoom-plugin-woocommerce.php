<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Woocommerce Downloads Integration
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.2
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Woocommerce extends carrot_bunnycdn_incoom_plugin_Download {

	/**
	 * Register the compatibility hooks for the plugin.
	 */
	function compatibility_init() {

		if(incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
			add_action( 'woocommerce_process_product_meta', array( $this, 'woocommerce_save_download' ), 40, 2 );
			add_filter( 'woocommerce_file_download_path', array( $this, 'filter_download_files' ) );	
		}
	}

	public function woocommerce_save_download($post_id, $post){
		$files = get_post_meta($post_id, '_downloadable_files', true);
		$fileData = [];
		if(!empty($files)){
			foreach ($files as $key => $file) {	

				$fileData[$key] = $file;
				$fileData[$key]['name'] = basename(carrot_bunnycdn_incoom_plugin_get_file_name_from_download_url($file['file']));

				$attachment_id = $this->get_post_id($file['file']);
				if($attachment_id){
					$is_permission = get_post_meta($attachment_id, 'carrot_downloadable_file_permission', true);
					if($is_permission != 'yes'){
						list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $attachment_id );
						$aws_s3_client->set_object_permission( $Bucket, $Region, $array_files, 'private' );
						update_post_meta($attachment_id, 'carrot_downloadable_file_permission', 'yes');
					}	
				}
			}
			
			update_post_meta($post_id, '_downloadable_files', $fileData);
		}
	}

	public function filter_download_files($value){

		if ( empty( $value ) || is_admin() ) {
			return $value;
		}

		if (strpos($value, 'carrot_bunnycdn_incoom_plugin_storage') !== false) {
			return do_shortcode($value);
		}

		$bucket_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
		if (strpos($value, $bucket_url) === false) {
			return $value;
		}
		
		$attachment_id = $this->get_post_id($value);
		if($attachment_id){
			$key = get_post_meta($attachment_id, '_wp_attached_file', true);
			if($key){
				list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $attachment_id );
				$url = $aws_s3_client->Get_Presigned_URL($Bucket, $Region, $key);
				if ( $url ) {
					$value = $url;
				}
			}	
		}

		return $value;
	}
}