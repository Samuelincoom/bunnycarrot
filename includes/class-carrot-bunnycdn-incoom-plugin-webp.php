<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Webp generate
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.2
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */
use WebPConvert\WebPConvert;

class carrot_bunnycdn_incoom_plugin_Webp {

	protected $post_id = null;
	protected $provider = null;
    protected $client = null;
    protected $region = null;
    protected $bucket = null;
    protected $bucket_url = null;
    protected $base_folder = null;
    protected $basedir_absolute = null;
    protected $objects = [];

	function __construct($post_id) {

		list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $post_id );

		$this->post_id 			= $post_id;
		$this->provider 		= $aws_s3_client;
		$this->bucket 			= $Bucket;
	    $this->region 			= $Region;
        $this->client 			= $this->provider->setClient($this->region);
        $this->bucket_url 		= carrot_bunnycdn_incoom_plugin_get_bucket_url();
        $this->basedir_absolute = $basedir_absolute;
        $this->base_folder 		= array_shift( $array_files );

        foreach ( $array_files as $key ) {
			
			if ( $this->base_folder != '' ) {
                $new_key = $this->base_folder . "/" . $key;
            } else {
                $new_key = $key;
			}
			
			if($this->should_convert($new_key)){
				$source = $this->basedir_absolute. '/' . $new_key;
				if(file_exists($source) && is_readable($source)){
					$this->objects[] = $new_key;
				}
			}
		}
	}

	public function get_key_from_url($old_url){
		return carrot_bunnycdn_incoom_plugin_Utils::get_key_from_url($old_url);
	}

	private function should_convert($source){
		if(strpos($source, '.png') !== false || strpos($source, '.jpg') !== false || strpos($source, '.jpeg') !== false){
			return true;
		}
		return false;
	}

	private function build_key($key, $with_bucket=true){
		
		if($this->provider::identifier() == 'bunnycdn'){
			$with_bucket = false;
		}

		$key = $this->provider->getBucketMainFolder().$key;
		if(!$with_bucket){
			return $key;
		}
		return $this->bucket.'/'.$key;
	}

    private function update_permission($key){
        $array_aux = explode( '/', $key );
        $main_file = array_pop( $array_aux );
        $array_files[] = implode( "/", $array_aux );
        $array_files[] = $main_file;
        $data = $this->provider->set_object_permission($this->bucket, $this->region, $array_files);
    }

	private function convert($keys, $options=[]){
		$key = $keys['key'];
		$sanitizeKey = $keys['newKey'];

		$data = get_post_meta($this->post_id, '_incoom_carrot_bunnycdn_webp_info', true);

		$upload_dir = wp_upload_dir();
		$basedir_absolute = $upload_dir['basedir'];
		$success = false;
        $msg = '';
        $new_key = $this->build_key($key);
		$destination = $this->basedir_absolute. '/' . $sanitizeKey . '.webp';
		$source = $this->basedir_absolute. '/' . $key;
		try {
			$content = file_get_contents($source);
			$file = $key . '.webp';
			$temp = carrot_bunnycdn_incoom_plugin_tempnam( $file );
			
			if ( ! $temphandle = @fopen( $temp, 'w+' ) ) {
				@unlink( $temp );
			}else{
				
				carrot_bunnycdn_incoom_plugin_Utils::put_contents($temp, $content);

				$buildKey = $this->build_key($sanitizeKey, false);
				
				try {
					WebPConvert::convert($temp, $destination, $options);
					
					if(file_exists( $destination )){
						error_log("Completed convert webp: {$destination}");
						// Here, upload file to cloud
						
						$this->provider->uploadSingleFile($this->bucket, $this->region, $destination, $buildKey . '.webp');

						try {
							if($this->provider::identifier() != 'bunnycdn'){
								$this->update_permission($buildKey . '.webp');
							}
						} catch (\Throwable $th) {}
						
						// then remove file from local
						@unlink( $destination );
						@unlink( $temp );
						
						// save data to DB
						$url = $this->bucket_url . '/' . $buildKey . '.webp';
						
						$newData = [
							'key' => $key,
							'url' => $url
						];

						// error_log(print_r($newData, true));

						if(!is_array($data)){
							$data = [];
						}

						$data[$key] = $url;

						update_post_meta($this->post_id, '_incoom_carrot_bunnycdn_webp_info', $data);

						$success = true;

					}else{
						error_log("Failed convert webp: {$destination}");
					}
				} catch (Exception $e) {
					$msg = $e->getMessage();
					error_log("{$source} Failed convert webp: {$msg}");
				}
			}

		} catch (\Throwable $th) {}

		return [
            'success' 	=> $success,
            'msg' 		=> $msg,
            'file' 		=> $destination
        ];
	}

	public function do_converts(){

		error_log("Start convert webp: {$this->post_id} - {$this->base_folder}");

		update_post_meta($this->post_id, '_incoom_carrot_bunnycdn_webp_info', []);

		try {
			$this->client->registerStreamWrapper();
		} catch (\Throwable $th) {}
		
		try {
			foreach ($this->objects as $key) {
				$msg = $this->convert([
					'key' => $key,
					'newKey' => $key
				]);
			}
		} catch (Exception $e) {
			error_log($e->getMessage());
		}

		error_log("End convert webp: {$this->post_id}");
	}
}
