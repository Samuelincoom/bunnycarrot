<?php

/**
 * Easy Digital Downloads Integration
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.2
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Edd extends carrot_bunnycdn_incoom_plugin_Download {

	/**
	 * Register the compatibility hooks for the plugin.
	 */
	function compatibility_init() {

		if(incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
			add_filter( 'edd_file_download_method', array( $this, 'set_download_method' ) );
			add_filter( 'edd_symlink_file_downloads', array( $this, 'disable_symlink_file_downloads' ) );
			add_action( 'edd_save_download', array( $this, 'edd_save_download' ), 10, 2 );
			add_filter( 'edd_requested_file', array( $this, 'edd_requested_file' ), 10, 2 );

			if(is_admin()){
				add_filter( 'fes_sanitize_multiple_pricing_field', array( $this, 'sanitize_multiple_pricing_field' ), 10, 4 );
				add_filter( 'fes_sanitize_file_upload_field', array( $this, 'sanitize_file_upload_field' ), 10, 4 );
				add_action( 'fes_save_submission_form_values_after_save', array( $this, 'submission_form_values_after_save' ), 10, 3);
			}
		}
	}

	/**
	 * Set download method
	 *
	 * @param string $method
	 *
	 * @return string
	 */
	public function set_download_method( $method ) {
		return 'redirect';
	}

	/**
	 * Disable symlink file downloads
	 *
	 * @param bool $use_symlink
	 *
	 * @return bool
	 */
	public function disable_symlink_file_downloads( $use_symlink ) {
		return false;
	}

	private function save_edd_download_files($post_id, $data){
		$files = get_post_meta($post_id, 'edd_download_files', true);
		if(!empty($files)){
			$new_key = $this->get_key_from_url($data['file']);
			foreach ($files as $file_arr) {
				$key = $this->get_key_from_url($file_arr['file']);
				if($key != $new_key){
					array_push($files, $data);
				}
			}
			update_post_meta($post_id, 'edd_download_files', $files);
		}else{
			update_post_meta($post_id, 'edd_download_files', [$data]);
		}
	}

	private function save_files_fes($save_id, $data){
		$option_name = 'incoom_carrot_bunnycdn_incoom_plugin_edd_fes_'.$save_id;
		$old_files = get_option($option_name);
		if(!empty($old_files)){
			update_option($option_name, array_unique(array_merge($data, $old_files)));
		}else{
			update_option($option_name, $data);
		}
	}

	private function set_attachment_permission($attachment_id){
		try {
			$is_permission = get_post_meta($attachment_id, 'carrot_downloadable_file_permission', true);
			if($is_permission != 'yes'){
				list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $attachment_id );
				$result = $aws_s3_client->set_object_permission( $Bucket, $Region, $array_files, 'private' );
				if($result){
					update_post_meta($attachment_id, 'carrot_downloadable_file_permission', 'yes');
				}
			}
		} catch (Exception $e) {
			//
		}
	}

	public function sanitize_multiple_pricing_field($values, $name, $save_id, $user_id){
		if(isset($values[$name])){
			if(isset($values[$name]['files'])){
				$new_files = [];
				$base_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
				$upload_dir = wp_upload_dir();
				$site_url = $upload_dir['baseurl'];
				$files = (array) $values[$name]['files'];
				foreach ($files as $file) {
					$new_file = str_replace($base_url, $site_url, $file);
					if(!in_array($new_file, $new_files)){
						$new_files[] = $new_file;
					}
				}

				if(!empty($new_files)){
					$values[$name]['files'] = $new_files;
					$this->save_files_fes($save_id, $files);
				}
			}
		}
		return $values;
	}

	public function submission_form_values_after_save($form, $user_id, $save_id){
		$option_name = 'incoom_carrot_bunnycdn_incoom_plugin_edd_fes_'.$save_id;
		$files = get_option($option_name);
		if(!empty($files)){
			foreach ($files as $key => $file) {

				$bucket_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
				if (strpos($file, $bucket_url) !== false) {
					$file = str_replace($bucket_url, '', $file);
					$file = ltrim($file, '/');
				}

				$cname_url = carrot_bunnycdn_incoom_plugin_get_cname_url();
				if (strpos($file, $cname_url) !== false) {
					$file = str_replace($cname_url, '', $file);
					$file = ltrim($file, '/');
				}

				$attachment_id = $this->get_post_id($file);

				if($attachment_id){
					$edd_download_files = [
						'index' 			=> $key,
						'thumbnail_size' 	=> 'full',
						'name' 				=> '',
						'attachment_id' 	=> $attachment_id,
						'file'				=> $file,
						'condition' 		=> 'all'
					];
					$this->save_edd_download_files($save_id, $edd_download_files);
					$this->set_attachment_permission($attachment_id);
				}
			}
		}
	}

	public function sanitize_file_upload_field($values, $name, $save_id, $user_id){
		if(isset($values[$name])){
			$new_files = [];
			$base_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
			$upload_dir = wp_upload_dir();
			$site_url = $upload_dir['baseurl'];
			$files = (array) $values[$name];
			foreach ($files as $file) {
				$new_file = str_replace($base_url, $site_url, $file);
				if(!in_array($new_file, $new_files)){
					$new_files[] = $new_file;
				}
			}

			if(!empty($new_files)){
				$values[$name] = $new_files;
				$this->save_files_fes($save_id, $files);
			}
		}
		return $values;
	}

	public function edd_requested_file($requested_file, $download_files){
		if (strpos($requested_file, 'carrot_bunnycdn_incoom_plugin_storage') !== false) {
			return do_shortcode($requested_file);
		}
		
		$attachment_id = $this->get_post_id($requested_file);
		if($attachment_id){
			$key = get_post_meta($attachment_id, '_wp_attached_file', true);
			if($key){
				list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $attachment_id );
				$url = $aws_s3_client->Get_Presigned_URL($Bucket, $Region, $key);
				if ( $url ) {
					return $url;
				}
			}
		}
		return $requested_file;	
	}

	public function edd_save_download($post_id, $post){
		$files = get_post_meta($post_id, 'edd_download_files', true);
		if(!empty($files)){
			foreach ($files as $file) {
				$attachment_id = false;

				if(isset($file['attachment_id']) && $file['attachment_id'] > 0){
					$attachment_id = $file['attachment_id'];
				}else{
					$attachment_id = $this->get_post_id($file['file']);
				}

				if($attachment_id){
					$this->set_attachment_permission($attachment_id);
				}
			}
		}
	}
}
