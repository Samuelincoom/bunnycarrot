<?php
if (!defined('ABSPATH')) {exit;}
/**
 * Sync Data
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.8
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */

class carrot_bunnycdn_incoom_plugin_Sync {

	/**
     * @var string
     */

    protected $provider = null;
    protected $client = null;
    protected $region = null;
    protected $bucket = null;
    protected $objects = null;
    protected $bucket_url = null;

    protected $provider_backup = null;
    protected $client_backup = null;
    protected $region_backup = null;
    protected $bucket_backup = null;
    protected $objects_backup = null;
    protected $bucket_url_backup = null;


    public function __construct() {

        $provider  = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_provider_from');
        $settings = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_settings_from');
        $Bucket_Selected = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_bucket_from');
		
        $this->provider = carrot_bunnycdn_incoom_plugin_whichtype($provider, $settings);

		if($this->provider::identifier() == 'google'){
			$this->bucket = $Bucket_Selected;
		}else{

			$Array_Bucket_Selected = explode( "_incoom_wc_as3s_separator_", $Bucket_Selected );

	        if ( count( $Array_Bucket_Selected ) == 2 ){
	            $this->bucket = $Array_Bucket_Selected[0];
	            $this->region = $Array_Bucket_Selected[1];
	        }
	        else{
	            $this->bucket = 'none';
	            $this->region = 'none';
	        }

	    }
        $this->bucket_url = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_bucket_base_url_from');
        $this->client = $this->provider->setClient($this->region);

        $this->setBackupData();

    }

    private function setBackupData(){
        $type = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_type');
        $Bucket_bk_Selected = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_bucket_to');

        if($type == 'cloud'){
            $provider  = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_provider_to');
            $settings = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_settings_to');
    		$this->provider_backup = carrot_bunnycdn_incoom_plugin_whichtype($provider, $settings);
        }else{
            $this->provider_backup = $this->provider;
        }

        if($this->provider_backup::identifier() == 'google'){
            $this->bucket_backup = $Bucket_bk_Selected;
        }else{

            $Array_Bucket_Selected = explode( "_incoom_wc_as3s_separator_", $Bucket_bk_Selected );

            if ( count( $Array_Bucket_Selected ) == 2 ){
                $this->bucket_backup = $Array_Bucket_Selected[0];
                $this->region_backup = $Array_Bucket_Selected[1];
            }
            else{
                $this->bucket_backup = 'none';
                $this->region_backup = 'none';
            }

        }
        $this->client_backup = $this->provider_backup->setClient($this->region_backup);

        if($type == 'cloud'){
            $this->bucket_url_backup = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_bucket_base_url_to');
        }else{
            $this->bucket_url_backup = str_replace($this->bucket, $this->bucket_backup, $this->bucket_url);
        }
    }

    public function getProvider(){
    	return $this->provider;
    }

    public function getProviderBackup(){
    	return $this->provider_backup;
    }

    public function getBucket(){
    	return $this->bucket;
    }

    public function getBucketBackup(){
    	return $this->bucket_backup;
    }

    public function setObjects(){
    	$this->objects = $this->provider->get_all_objects($this->bucket, $this->region);
    }

    public function setObjectsBackup(){
    	$this->objects_backup = $this->provider_backup->get_all_objects($this->bucket_backup, $this->region_backup);
    }

    public function getObjects(){
    	return $this->objects;
    }

    public function getObjectsBackup(){
    	return $this->objects_backup;
    }

    public function getCacheKey(){
        $provider 	= $this->provider::identifier();
        $providerBK = $this->provider_backup::identifier();
        $bucket 	= $this->bucket;
        $bucketBK 	= $this->bucket_backup;
        $path 		= "{$provider}_{$bucket}_{$providerBK}_{$bucketBK}";
        return "carrot_sync_data_".wp_hash($path);
    }

    public function getCacheData(){
        return carrot_bunnycdn_incoom_plugin_get_sync_objects($this->getCacheKey());
    }

    private function get_post_id($key){
        return carrot_bunnycdn_incoom_plugin_get_post_id($key);
    }

    private function maybe_update_post_meta($key){
        $post_id = $this->get_post_id($key);
        if($post_id){

            $info = get_post_meta( $post_id, '_incoom_carrot_bunnycdn_amazonS3_info', true );
            $info['provider'] = $this->provider_backup::identifier();
            $info['region'] = $this->region_backup;
            $info['bucket'] = $this->bucket_backup;
            update_post_meta( $post_id, '_incoom_carrot_bunnycdn_amazonS3_info', $info );

            $path = get_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_s3_path', true );
            $new_path = str_replace($this->bucket_url, $this->bucket_url_backup, $path);
            update_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_s3_path', $new_path );
        }
        return true;
    }

    private function maybe_update_assets($key){

        $type = substr(strrchr($key, '.'), 1);
        if(!in_array($type, ['css', 'js'])){
            return false;
        }

        $uploaded = get_option('incoom_carrot_bunnycdn_incoom_plugin_uploaded_assets');
        if(!empty($uploaded) && is_array($uploaded)){
            foreach ($uploaded as $k => $src) {
                if(strpos($src, $key) !== false){
                    $new_src = str_replace($this->bucket_url, $this->bucket_url_backup, $src);
                    $uploaded[$k] = carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url($new_src, $this->bucket_url_backup);
                }
            }
            update_option('incoom_carrot_bunnycdn_incoom_plugin_uploaded_assets', $uploaded);
        }
        return true;
    }

    private function maybe_update_content_css($path, $content){
        $type = substr(strrchr($path, '.'), 1);
        if($type == 'css'){
            return str_replace($this->bucket_url, $this->bucket_url_backup, $content);
        }

        return $content;
    }

    private function maybe_update_permission($data){
        $key = $data['key'];
        $acl = $this->provider->Get_Access_of_Object($this->bucket, $this->region, $key);
        $data['acl'] = $acl;
        $array_aux = explode( '/', $key );
        $main_file = array_pop( $array_aux );
        $array_files[] = implode( "/", $array_aux );
        $array_files[] = $main_file;

        $this->provider_backup->set_object_permission($this->bucket_backup, $this->region_backup, $array_files, $acl);

        if($this->provider_backup::identifier() == 'google'){
            return [];
        }

        return $data;
    }

    public function sync($data){
        try{
            $old_content = $this->getFileContentBackup($data);
            if($old_content){
                $this->putFileContent($this->provider_backup->dir.$this->bucket_backup.'/'.$data['key'], $old_content);

                // $this->maybe_update_post_meta($data['key']);
                // $this->maybe_update_assets($data['key']);
                $new_data = $this->maybe_update_permission($data);
                $this->provider_backup->updateMetadaObject($this->bucket_backup, $this->region_backup, $new_data);

                return true;
            }
            return false;
        } catch (Exception $e){
            return false;
        }
    }

    private function getFileContentBackup($data){
        $source = $data['source'];
        $SourceFile = $this->provider->dir.$source;
        $type = substr(strrchr($data['key'], '.'), 1);

        try {
            $this->client->registerStreamWrapper();
            if(file_exists($SourceFile) && is_readable($SourceFile)){
                try{
                    if ( $this->provider->should_gzip_file( $SourceFile, $type ) && false !== ( $gzip_body = gzencode( file_get_contents( $SourceFile ) ) ) ) {
                        return $gzip_body;
                    }
                    
                    return file_get_contents($SourceFile);
                } catch (Exception $e){
                    error_log($SourceFile);
                }
            }
        } catch (\Throwable $th) {error_log($th->getMessage());}

        return false;
    }

    private function putFileContent($path, $content){
        $new_content = $this->maybe_update_content_css($path, $content);
        return $this->provider_backup->putFileContent($this->bucket_backup, $this->region_backup, $path, $new_content);
    }

    private function syncBucket($data){
        try{
            $data['bucket'] = $this->bucket;
            $this->provider_backup->copyObjectFromBucket($this->bucket_backup, $this->region_backup, $data);
            return true;
        } catch (Exception $e){
            return false;
        }
    }
}
