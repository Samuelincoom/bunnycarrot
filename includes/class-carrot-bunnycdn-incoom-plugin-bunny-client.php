<?php
if (!defined('ABSPATH')) {exit;}
/**
 * Bunny CDN Client
 *
 * @since      2.0.3
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */

class carrot_bunnycdn_incoom_plugin_Bunny_Client extends carrot_bunnycdn_incoom_plugin_Storage {
    
    private $_storage_key;
    private $_storage_path;

    /**
	 * Used in filters and settings.
	 *
	 * @var string
	 */
	protected static $provider_key_name = 'bunnycdn';

    public $dir = '';

    /**
     * instancia Bunny CDN
     * Bunny CDN constructor.
     */
    public function __construct( $key, $storage_key, $storage_path ) {

        $this->_key = $key;
        $this->_storage_key = $storage_key;
        $this->_storage_path = $storage_path;
        $this->get_bucket_base_url();
    }

    public static function identifier() {
        return 'bunnycdn';
    }

    public static function name() {
        return esc_html__('Bunny CDN', 'carrot-bunnycdn-incoom-plugin');
    }

    public function Init_S3_Client( $Region='', $Version='', $key='', $Secret='' ) {
        return new carrot_bunnycdn_incoom_plugin_BunnyCDN();
    }

    public function Load_Regions() {

        $this->_array_regions = [];
    }

    public function Checking_Credentials() {

        try {

            $S3_Client = $this->Init_S3_Client( '', '', $this->_key );

            $buckets = $S3_Client->Account($this->_key)->GetZoneList();

            update_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_success', 1 );

        } catch ( Exception $e ) {

            update_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_success', 0 );

            $buckets = 0;

        }

        return $buckets;

    }

    /**
     * obtiene todos los objetos de un bucket
     * @return \Guzzle\Service\Resource\ResourceIteratorInterface|mixed
     */
    public function Show_Buckets($Bucket_Selected='') {

        ob_start();

        if(empty($Bucket_Selected)){
            $Bucket_Selected = ( get_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select' ) ? get_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select' ) : '' );
        }

        try {

            // Instantiate the S3 client with your AWS credentials
            $S3_Client = $this->Init_S3_Client( '', '', $this->_key );
            $bucketsStorage = $S3_Client->Account($this->_key)->listStorageZones();
            
            echo "<option value='0'>" . esc_html__( 'Choose a bucket', 'carrot-bunnycdn-incoom-plugin' ) . "</option>";

            foreach($bucketsStorage['storages'] as $storage){
                               
                if(!isset($storage['Password'])){
                    continue;
                }

                if($storage['Password'] != $this->_storage_key){
                    continue;
                }

                update_option('incoom_carrot_bunnycdn_incoom_plugin_bunny_region', strtolower($storage['Region']));
                update_option('incoom_carrot_bunnycdn_incoom_plugin_bunny_zone_list', $storage['PullZones']);

                foreach ( $storage['PullZones'] as $bucket ) {
                    $selected = ( ( $Bucket_Selected == $bucket['Id'] ) ? 'selected="selected"' : '' );
                    ?>
<option <?php echo $selected; ?> value="<?php echo esc_attr($bucket['Id']); ?>">
    <?php echo esc_html($bucket['Name']); ?> </option>
<?php

                }
            }

        } catch ( Exception $e ) {
            error_log($e->getMessage());
            //
        }

        return ob_get_clean();

    }

    public function get_base_url($bucket, $Region='', $Keyname=''){

        $zoneList = get_option('incoom_carrot_bunnycdn_incoom_plugin_bunny_zone_list');
        $url = '';
        $Bucket_Selected = get_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select', '' );

        foreach($zoneList as $zone){
            if($zone['Id'] == $Bucket_Selected){
                $hostname = $zone['Hostnames'][0]['Value'];
                $url = "https://{$hostname}";
                break;
            }
        }
        
        return $url;
    }

    public function getObjectUrl( $Bucket = '', $Region = '', $File_Name = '' ) {
        $File_Name = ltrim($File_Name, '/');
        $url = $this->_base_url;
        return "{$url}/{$File_Name}";
    }

    public static function docs_link_credentials(){
        return 'https://bunnycdn.com/dashboard/account';
    }

    public static function docs_link_create_bucket(){
        return 'https://bunnycdn.com/dashboard/storagezones';
    }

    /**
	 * Delete multiple objects from bucket.
	 *
	 * @param array $args
	 */
	public function delete_objects( $Bucket, $Region, array $args ) {
        
		if ( ! isset( $args['Delete'] ) && isset( $args['Objects'] ) ) {
			$args['Delete']['Objects'] = $args['Objects'];
			unset( $args['Objects'] );
		}

        $S3_Client = $this->Init_S3_Client( '', '', $this->_key );
        $bucket = $S3_Client->Storage($this->_storage_key, get_option('incoom_carrot_bunnycdn_incoom_plugin_bunny_region', 'de'));

        foreach ( $args['Delete']['Objects'] as $file ) {

            $Key = $file['Key'];

            try {
                $result = $bucket->DeleteFile($this->_storage_path.$this->rebuild_key($Key));
            } catch (\Throwable $th) {
                error_log($th->getMessage());
            }
            
            if(carrot_bunnycdn_incoom_plugin_enable_webp()){
                try {
                    if(strpos($Key, '.png') !== false || strpos($Key, '.jpg') !== false || strpos($Key, '.jpeg') !== false){
                        $filename = basename($Key);
				        $newKey = str_replace($filename, sanitize_title($filename), $Key);
                        $bucket->DeleteFile($this->_storage_path.$this->rebuild_key($newKey.'.webp'));
                    }
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }

        }
	}

    /**
	 * Upload file to bucket.
	 *
	 * @param array $args
	 */
	public function upload_object( array $args ) {
		$result = '';

        $cacheControl = get_option('incoom_carrot_bunnycdn_incoom_plugin_cache_control', 'public, max-age=31536000');

        $S3_Client = $this->Init_S3_Client( '', '', $this->_key );

        $radio_private_or_public = get_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', 'public');
        /*== We check if the file is going to be private or public ==*/
        $private_or_public = ( $radio_private_or_public == 'private' ? $radio_private_or_public : 'public-read' );

        $bucket = $S3_Client->Storage($this->_storage_key, get_option('incoom_carrot_bunnycdn_incoom_plugin_bunny_region', 'de'));
        $base_url = $this->_base_url;

        if(file_exists($args['SourceFile'])){

            $args = array(
                'Bucket'     => $args['Bucket'],
                'Key'        => $this->rebuild_key($args['Key'], ''),
                'SourceFile' => $args['SourceFile'],
                'ACL'        => $private_or_public,
                'CacheControl' => $cacheControl
            );

            $object = $bucket->PutFile($args['SourceFile'], $this->_storage_path, $this->rebuild_key($args['Key'], ''));
            if($object['status'] === 'success'){
                $result = $base_url.'/'.$this->rebuild_key($args['Key'], '');
            }else{
                $msg = $object['msg']->Message;
                carrot_bunnycdn_incoom_plugin_Messages::add_error($msg);
            }
        }

        return $result;
	}

    public function Upload_Media_File( $Bucket = '', $Region = '', $array_files = [], $basedir_absolute = '', $private_or_public = 'public', $prefix = '', $attachment_id = 0 ) {

        $settings = carrot_bunnycdn_incoom_plugin_whichtype_settings();
		$provider = isset($settings['provider']) ? $settings['provider'] : 'aws';
        
        $base_folder = array_shift( $array_files );

		$files_to_remove = array();

        $S3_Client = $this->Init_S3_Client( '', '', $this->_key );

        $File_Name = array_shift( $array_files );

        if ( $base_folder != '' ) {
            $Key = $base_folder . "/" . $File_Name;
        } else {
            $Key = $File_Name;
        }

        if ( $base_folder != '' ) {
            $SourceFile = $basedir_absolute . "/" . $base_folder . "/" . $File_Name;
        } else {
            $SourceFile = $basedir_absolute . "/" . $File_Name;
        }

        /*== We check if the file is going to be private or public ==*/
        $private_or_public = ( $private_or_public == 'private' ? 'authenticatedRead' : 'publicRead' );

        $base_url = $this->_base_url;
        $result = false; 
        $bucket = $S3_Client->Storage($this->_storage_key, get_option('incoom_carrot_bunnycdn_incoom_plugin_bunny_region', 'de'));

        try {
            if(file_exists($SourceFile)){
                $object = $bucket->PutFile($SourceFile, $this->_storage_path, $this->rebuild_key($Key, $prefix));
                if($object['status'] === 'success'){
                    $result = $base_url.'/'.$this->rebuild_key($Key, $prefix);
                    $files_to_remove[] = $SourceFile;
                }else{
                    $msg = $object['msg']->Message;
                    carrot_bunnycdn_incoom_plugin_Messages::add_error($msg);
                }
            }
        } catch (\Exception $ex) {
            error_log( $ex->getMessage() );
        }

        $itemId = null;
        $original_path = $Key;

        foreach ( $array_files as $File_Name ) {

            if ( $base_folder != '' ) {
                $Key = $base_folder . "/" . $File_Name;
            } else {
                $Key = $File_Name;
            }

            if ( $base_folder != '' ) {
                $SourceFile = $basedir_absolute . "/" . $base_folder . "/" . $File_Name;
            } else {
                $SourceFile = $basedir_absolute . "/" . $File_Name;
            }

            try {
                if(file_exists($SourceFile)){
                    $object = $bucket->PutFile($SourceFile, $this->_storage_path, $this->rebuild_key($Key, $prefix));
                    if($object['status'] === 'success'){
                        $files_to_remove[] = $SourceFile;
                    }
                }
            } catch (\Exception $ex) {
                error_log( $ex->getMessage() );
            }
        }

        $files_to_remove = array_unique( $files_to_remove );
        $this->remove_local_files($files_to_remove, $attachment_id);
        
        try {
            $item = new carrot_bunnycdn_incoom_plugin_Item( 
                $provider, 
                $Region, 
                $bucket, 
                $Key, 
                false, 
                $attachment_id, 
                $SourceFile, 
                null, 
                array( 'private_prefix' => '' ), 
                null 
            );
            $item->save();
        } catch (\Throwable $th) {}
        
        return $result;

    }

    /**
     * elimina un objeto de un bucket
     *
     * @param $key
     */
    public function deleteObject_nou( $Bucket='', $Region='', $array_files = [] ) {

        $base_folder = array_shift( $array_files );

        $S3_Client = $this->Init_S3_Client( '', '', $this->_key );

        $result = 0;
        $bucket = $S3_Client->Storage($this->_storage_key, get_option('incoom_carrot_bunnycdn_incoom_plugin_bunny_region', 'de'));

        foreach ( $array_files as $File_Name ) {

            if ( $base_folder != '' ) {
                $Key = $base_folder . "/" . $File_Name;
            } else {
                $Key = $File_Name;
            }

            try {
                $result = $bucket->DeleteFile($this->_storage_path.$this->rebuild_key($Key));
            } catch (\Throwable $th) {
                error_log($th->getMessage());
            }
            
            if(carrot_bunnycdn_incoom_plugin_enable_webp()){
                try {
                    if(strpos($Key, '.png') !== false || strpos($Key, '.jpg') !== false || strpos($Key, '.jpeg') !== false){
                        $filename = basename($Key);
				        $newKey = str_replace($filename, sanitize_title($filename), $Key);
                        $bucket->DeleteFile($this->_storage_path.$this->rebuild_key($newKey.'.webp'));
                    }
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }

        }

        return $result;
    }

    /**
     * @param $key
     *
     * @return \Guzzle\Service\Resource\Model
     */
    public function getObject( $Bucket = '', $Region = '', $key = '', $expires = null ) {
        
        $S3_Client = $this->Init_S3_Client( '', '', $this->_key );
        $bucket = $S3_Client->Storage($this->_storage_key, get_option('incoom_carrot_bunnycdn_incoom_plugin_bunny_region', 'de'));

        $data = array(
            'Bucket' => $Bucket,
            'Key'    => $this->rebuild_key($key)
        );
        
        return [];
    }

    /**
     * download files
     *
     * @param $key
     * @param $filename
     */
    public function download_file( $Bucket, $Region, $array_files, $basedir_absolute ) {
        $result = false;

        $base_folder = array_shift( $array_files );
        foreach ( $array_files as $File_Name ) {

            if ( $base_folder != '' ) {
                $Key = $base_folder . "/" . $File_Name;
            } else {
                $Key = $File_Name;
            }

            if ( $base_folder != '' ) {
                $SaveAs = $basedir_absolute . "/" . $base_folder . "/" . $File_Name;
            } else {
                $SaveAs = $basedir_absolute . "/" . $File_Name;
            }

            try{
                $dir = dirname( $SaveAs );
                if ( ! wp_mkdir_p( $dir ) ) {
                    $error_message = sprintf( __( 'The local directory %s does not exist and could not be created.', 'carrot-bunnycdn-incoom-plugin' ), $dir );
                    error_log( sprintf( __( 'There was an error attempting to download the file %s from the bucket: %s', 'carrot-bunnycdn-incoom-plugin' ), $File_Name, $error_message ) );

                    return false;
                }
                
                $result = $this->downloadObject( array(
                    'Key'    => $this->rebuild_key($Key),
                    'SaveAs' => $SaveAs
                ) );
            } catch(Exception $e) {
                error_log($e->getMessage());
            }

        }

        return $result;

    }

    public function registerStreamWrapper()
    {
        return;
    }

    public function update_permission()
    {
        return;
    }

    public function set_object_permission($Bucket, $Region, $array_files, $acl='public-read')
    {
        return;
    }

    public function putFileContent($Bucket = '', $Region = '', $path = '', $content = ''){
        return file_put_contents($path, $content);
    }

    public function uploadSingleFile($Bucket = '', $Region = '', $SourceFile = '', $Key = ''){
        $S3_Client = $this->Init_S3_Client( '', '', $this->_key );
        $base_url = $this->_base_url;
        $result = false; 
        $bucket = $S3_Client->Storage($this->_storage_key, get_option('incoom_carrot_bunnycdn_incoom_plugin_bunny_region', 'de'));
        try {
            if(file_exists($SourceFile)){
                $object = $bucket->PutFile($SourceFile, $this->_storage_path, $this->rebuild_key($Key));
                return $base_url.'/'.$this->rebuild_key($Key, '');
            }
        } catch (\Exception $ex) {
            error_log( $ex->getMessage() );
        }

        return $result;
    }

    public function putHostingContent($region='', $bucket='')
    {
        return null;
    }

    public function putBucketPolicy($region='', $bucket='')
    {
        return null;
    }

    public function Get_Presigned_URL( $Bucket, $Region, $Key ) {
        $S3_Client = $this->Init_S3_Client( '', '', $this->_key );
        $path = $this->rebuild_key($Key);
        return (string) $S3_Client->SecureLink($path);
    }

	/**
	 * Check whether key exists in bucket.
	 *
	 * @param string $bucket
	 * @param string $Region
	 * @param string $key
	 * @param array  $options
	 *
	 * @return bool
	 */
	public function does_object_exist( $Bucket, $Region, $key, array $options = array() ) {
        $url = $this->getObjectUrl('', '', $this->rebuild_key($key));
        $request = wp_safe_remote_get($url);

        if ( is_wp_error( $request ) ) {
            return false;
        }

        try {
            if($request['response']['code'] === 200){
                return true;
            }
        } catch (\Throwable $th) {}

        return false;
	}
}