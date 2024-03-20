<?php
if (!defined('ABSPATH')) {exit;}
/**
 * Storege
 *
 * @since      1.0.2
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */
use Aws\S3\S3Client;
use Aws\CommandPool;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\AwsException;

class carrot_bunnycdn_incoom_plugin_Storage {
    /**
     * @var string
     */

    protected $_region = null;

    public $_array_regions = null;

    public $dir = 's3://';

    /**
     * @var string
     */
    protected $_version = 'latest';

    /**
     * @var string
     */
    protected $bucket = null;

    /**
     * @var string
     */
    protected $directory = '';

    /**
     * @var string
     */
    protected $_key = null;

    /**
     * @var string
     */
    protected $_secret = null;

    /**
     * @var S3Client|null
     */
    public $s3_client = null; //instancia de s3

    protected static $_instance = null;

    /**
	 * Used in filters and settings.
	 *
	 * @var string
	 */
	protected static $provider_key_name = 'aws';

    public $_base_url = '';

    /**
     * instancia de s3
     * AwsS3 constructor.
     */
    public function __construct( $key, $secret ) {

        $this->_key    = $key;
        $this->_secret = $secret;
        $this->Load_Regions();
        $this->get_bucket_base_url();
    }

    public function get_bucket_base_url() {
        $this->_base_url = get_option('incoom_carrot_bunnycdn_incoom_plugin_aws_connection_bucket_base_url');
    }

    /**
	 * Returns the key friendly name for the provider.
	 *
	 * @return string
	 */
	public static function get_provider_key_name() {
		return static::$provider_key_name;
	}

    public static function identifier() {
        return 's3';
    }

    public static function name() {
        return esc_html__('Amazon S3', 'carrot-bunnycdn-incoom-plugin');
    }

    public function Init_S3_Client( $Region = '', $Version = 'latest', $key = '', $Secret = '' ) {

        $args = array(
            'version'     => 'latest',
            'credentials' => array(
                'key'    => $key,
                'secret' => $Secret,
            )
        );

        if ( empty( $Region ) ) {
			return $s3Client = new \Aws\S3\S3MultiRegionClient($args);
		} else {
            $args['region'] = $Region;
			return (new Aws\Sdk($args))->createS3();
        }

    }

    public function Load_Regions() {

        $this->_array_regions = array(
            '0'  => array( 'us-east-1', 'US East (N. Virginia)' ),
            '1'  => array( 'us-east-2', 'US East (Ohio)' ),
            '2'  => array( 'us-west-1', 'US West (N. California)' ),
            '3'  => array( 'us-west-2', 'US West (Oregon)' ),
            '4'  => array( 'ca-central-1', 'Canada (Central)' ),
            '5'  => array( 'ap-south-1', 'Asia Pacific (Mumbai)' ),
            '6'  => array( 'ap-northeast-2', 'Asia Pacific (Seoul)' ),
            '7'  => array( 'ap-southeast-1', 'Asia Pacific (Singapore)' ),
            '8'  => array( 'ap-southeast-2', 'Asia Pacific (Sydney)' ),
            '9'  => array( 'ap-northeast-1', 'Asia Pacific (Tokyo)' ),
            '10' => array( 'eu-central-1', 'EU (Frankfurt)' ),
            '11' => array( 'eu-west-1', 'EU (Ireland)' ),
            '12' => array( 'eu-west-2', 'EU (London)' ),
            '13' => array( 'sa-east-1', 'South America (São Paulo)' ),
            '14' => array( 'ap-east-1', 'Asia Pacific (Hong Kong)' ),
            '15' => array( 'af-south-1', 'Africa (Cape Town)' ),
            '16' => array( 'eu-south-1', 'Europe (Milan)' ),
            '17' => array( 'eu-west-3', 'Europe (Paris)' ),
            '18' => array( 'eu-north-1', 'Europe (Stockholm)' ),
            '19' => array( 'ap-northeast-3', 'Asia Pacific (Osaka-Local)' ),
            '20' => array( 'cn-north-1', 'China (Beijing)' ),
            '21' => array( 'cn-northwest-1', 'China (Ningxia)' ),
            '22' => array( 'me-south-1', 'Middle East (Bahrain)' ),
            '23' => array( 'ap-south-2', 'Asia Pacific (Hyderabad)' ),
            '24' => array( 'ap-southeast-3', 'Asia Pacific (Jakarta)' ),
            '25' => array( 'eu-south-2', 'Europe (Spain)' ),
            '26' => array( 'eu-central-2', 'Europe (Zurich)' ),
            '27' => array( 'me-central-1', 'Middle East (UAE)' ),
            '28' => array( 'us-gov-east-1', 'AWS GovCloud (US-East)' ),
            '29' => array( 'us-gov-west-1', 'AWS GovCloud (US-West)' ),
        );
    }

    public function handler_response($response){
        $response = json_decode($response, true);
        return isset($response['error']) ? $response['error'] : ['code' => '400', 'message' => esc_html__('Error, please try again.', 'carrot-bunnycdn-incoom-plugin')];
    }

    public function Get_Regions() {
        $regions = array();
        foreach ($this->_array_regions as $key => $region) {
            $regions[] = $region[0];
        }
        return $regions;
    }

    public static function get_instance() {
        $self = __CLASS__ . ( class_exists( __CLASS__ . '_Premium' ) ? '_Premium' : '' );

        if ( is_null( $self::$_instance ) ) {
            $self::$_instance = new $self;
        }

        return $self::$_instance;
    }

    public function Checking_Credentials() {

        try {

            // Instantiate the S3 client with your AWS credentials
            $S3_Client = $this->Init_S3_Client( $this->_array_regions[0][0], $this->_version, $this->_key, $this->_secret );

            $buckets = $S3_Client->listBuckets();

            update_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_success', 1 );

        } catch ( Exception $e ) {

            update_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_success', 0 );

            $buckets = 0;

        }

        return $buckets;

    }

    public function setClient($Region){
        return $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
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
            $S3_Client = $this->Init_S3_Client( '', $this->_version, $this->_key, $this->_secret );

            $buckets = $S3_Client->listBuckets();
            
            $regions = $this->Get_Regions();
            
            echo "<option value='0'>" . esc_html__( 'Choose a bucket', 'carrot-bunnycdn-incoom-plugin' ) . "</option>";

            foreach ( $buckets['Buckets'] as $bucket ) {

                try {
                    $result = $S3_Client->getBucketLocation(array(
                        'Bucket' => $bucket['Name'],
                    ));
                } catch ( S3Exception $e ) {
                    error_log($e->getMessage());
                    $result = false;
                }
                
                if ( $result ){
                    if(in_array($result['LocationConstraint'], $regions)){
                        $selected = ( ( $Bucket_Selected == $bucket['Name'] . "_incoom_wc_as3s_separator_" . $result['LocationConstraint'] ) ? 'selected="selected"' : '' );

                        ?>
<option <?php echo $selected; ?>
    value="<?php echo esc_attr($bucket['Name'] . "_incoom_wc_as3s_separator_" . $result['LocationConstraint']); ?>">
    <?php echo esc_html($bucket['Name'] . " - " . $result['LocationConstraint']); ?> </option>
<?php
                    }    

                }

            }

        } catch ( Exception $e ) {
            error_log($e->getMessage());
            //
        }

        return ob_get_clean();

    }

    public function rebuild_key($Key, $custom_prefix=''){
        return carrot_bunnycdn_incoom_plugin_rebuild_key($Key, $custom_prefix);
    }

    public function set_object_permission($Bucket, $Region, $array_files, $acl='public-read') {

        $base_folder = array_shift( $array_files );

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        $result = 0;
        foreach ( $array_files as $File_Name ) {

            if ( $base_folder != '' ) {
                $Key = $base_folder . "/" . $File_Name;
            } else {
                $Key = $File_Name;
            }

            try {
                $result = $S3_Client->putObjectAcl(['Bucket' => $Bucket, 'Key' => $this->rebuild_key($Key), 'ACL' => $acl]);
            } catch ( Exception $e ) {
                //
                error_log($e);
            }
            

        }

        return $result;
    }

    public function Get_Presigned_URL( $Bucket, $Region, $Key ) {

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
        $cmd = $S3_Client->getCommand( 'GetObject', [
            'Bucket' => $Bucket,
            'Key'    => $this->rebuild_key($Key)
        ] );

        $valid_time = ( get_option( 'incoom_carrot_bunnycdn_incoom_plugin_time_valid_number' ) ? get_option( 'incoom_carrot_bunnycdn_incoom_plugin_time_valid_number' ) : '5' );

        $request = $S3_Client->createPresignedRequest( $cmd, '+'. $valid_time . ' minutes' );

        // Get the actual presigned-url
        return (string) $request->getUri();
    }

    public function Get_Access_of_Object( $Bucket, $Region, $Key ) {

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
        $Access = 'private';
        try {
            // Get an objectAcl
            $result = $S3_Client->getObjectAcl( array(
                'Bucket' => $Bucket,
                'Key'    => $this->rebuild_key($Key)
            ));
            
            if ( isset( $result['Grants'][1] ) )
                if ( $result['Grants'][1]['Permission'] == 'READ' )
                    $Access = 'public';
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $Access;

    }

    public function build_filemanager_view_type($Current_Folder, $Region){
        $type = isset($_SESSION['carrot_bunnycdn_incoom_plugin_view_type']) ? $_SESSION['carrot_bunnycdn_incoom_plugin_view_type'] : 'list';
        ?>
<div class="view-switch filemanager-display">
    <a href="" <?php echo 'data-region="'.esc_attr($Region).'"';?>
        <?php echo 'data-current_folder="'.esc_attr($Current_Folder).'"';?>
        class="view view-list <?php if($type == 'list'){echo 'current';}?>">
        <span class="screen-reader-text">List View</span>
    </a>
    <a href="" <?php echo 'data-region="'.esc_attr($Region).'"';?>
        <?php echo 'data-current_folder="'.esc_attr($Current_Folder).'"';?>
        class="view view-grid <?php if($type == 'grid'){echo 'current';}?>">
        <span class="screen-reader-text">Grid View</span>
    </a>
    <a href="" data-type="shortcode" class="use view-shortcode"
        title="<?php esc_html_e('Use shortcode', 'carrot-bunnycdn-incoom-plugin');?>">
        <span class="screen-reader-text">shortcode View</span>
    </a>
    <a href="" data-type="url" class="use view-url current"
        title="<?php esc_html_e('Use full URL', 'carrot-bunnycdn-incoom-plugin');?>">
        <span class="screen-reader-text">URL View</span>
    </a>
</div>
<?php
    }

    public function Show_Keys_of_a_Folder_Bucket( $Current_Folder, $Region, $File_Selected = 'none' ) {

        ob_start();

        $Array_Current_Folder = explode( "/", $Current_Folder );

        $Bucket = array_shift( $Array_Current_Folder );

        $Top_Folder = array_pop( $Array_Current_Folder );

        $Path_S3_image = esc_url(carrot_bunnycdn_incoom_plugin_PLUGIN_URI.'admin/images/s3.png');
        $type = isset($_SESSION['carrot_bunnycdn_incoom_plugin_view_type']) ? $_SESSION['carrot_bunnycdn_incoom_plugin_view_type'] : 'list';
        ?>

<div class="filemanager">
    <?php $this->build_filemanager_view_type($Current_Folder, $Region);?>
    <div class="breadcrumbs">

        <?php echo "<span class='folderName'><a href='".esc_url($Bucket)."' class='select-folder' data-region='".esc_attr($Region)."' data-current_folder='".esc_attr($Bucket)."'>/</a></span>";?>

        <?php

                $Current_Folder_Index = $Bucket;
                foreach ( $Array_Current_Folder as $Folder ) {
                    $Current_Folder_Index = $Current_Folder_Index . "/" . $Folder;
                    echo "<span class='folderName'><a href='".esc_url($Folder)."' class='select-folder' data-region='".esc_attr($Region)."' data-current_folder='".esc_attr($Current_Folder_Index)."'>".esc_html($Folder)."</a></span> <span class='folderName'>/</span>";
                }


                echo '<span class="folderName">' . $Top_Folder . '</span>';

                ?>

    </div>

    <ul class="data animated incoom_carrot_bunnycdn_ul_File_Manager <?php echo $type;?>">

        <?php

                $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

                // Register the stream wrapper from an S3Client object
                $S3_Client->registerStreamWrapper();

                if ( is_dir( "s3://" . $Current_Folder ) && ( $dh = opendir( "s3://" . $Current_Folder ) ) ) {

                    while ( ( $object = readdir( $dh ) ) !== false ) {

                        if ( is_dir( "s3://" . $Current_Folder . "/" . $object ) ) {

                            ?>
        <li class="folders">
            <a href="#" class="select-folder" data-region='<?php echo $Region; ?>'
                data-current_folder='<?php echo $Current_Folder . "/" . $object; ?>'>
                <span class="icon folder full"></span>
                <span class="name"><?php echo $object; ?></span>
            </a>
        </li>
        <?php

                        } else {

                            $Key = $Current_Folder . "/" . $object;
                            $Key = str_replace( $Bucket . "/", "", $Key );
                            $type = substr(strrchr($object, '.'), 1);
                            $acl = $this->Get_Access_of_Object($Bucket, $Region, $Key);
                            ?>
        <li class="files incoom_carrot_bunnycdn_ul_File_Manager_li_File">
            <a href="#" title="<?php echo $object; ?>" data-value="<?php echo $object; ?>"
                data-original="<?php echo $Key;?>"
                data-key="<?php echo carrot_bunnycdn_incoom_plugin_get_url_from_key($Key); ?>">
                <span class="icon file f-<?php echo $type;?>"><?php echo $type;?></span>
                <span class="name"><?php echo $object; ?></span>
                <label class="switch">
                    <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox"
                        <?php checked($acl, 'public');?>>
                    <label class="onoffswitch-label"
                        for="myonoffswitch"><?php esc_html_e('Public', 'carrot-bunnycdn-incoom-plugin');?></label>
                </label>
            </a>
        </li>
        <?php

                        }

                    }

                    closedir( $dh );

                }

                ?>

    </ul>

</div>

<?php

        return ob_get_clean();

    }

    public function getObjectUrl( $Bucket, $Region, $File_Name ) {

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        return $S3_Client->getObjectUrl( $Bucket, $File_Name );

    }

    public function get_base_url( $Bucket, $Region, $Keyname ) {

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        try{
            $result = $S3_Client->putObject( array(
                'Bucket'     => $Bucket,
                'Key'        => $Keyname,
                'Body'   => 'carrot-bunnycdn-incoom-plugin -> getting the base url',
                'ACL'    => 'public-read'
            ) );

            if ( ! $result ) {
                error_log( print_r( 'Error when uploading result_of_array', true ) );
                $base_url = 0;
            }
            else
                $base_url = str_replace( "/" .$Keyname , "", $result[ 'ObjectURL' ] );

            return $base_url;
        } catch(Exception $e){
            return 0;
        }

    }

    public function getBucketMainFolder(){
        return carrot_bunnycdn_incoom_plugin_get_bucket_main_folder();
    }

    public function Upload_Media_File( $Bucket, $Region, $array_files, $basedir_absolute, $private_or_public = 'public', $prefix='', $attachment_id = 0 ) {

        $settings = carrot_bunnycdn_incoom_plugin_whichtype_settings();
		$provider = isset($settings['provider']) ? $settings['provider'] : 'aws';

        $params = [];
        $options = [];
        $result = '';

		$files_to_remove = array();

        $cacheControl = get_option('incoom_carrot_bunnycdn_incoom_plugin_cache_control', 'public, max-age=31536000');

        $base_folder = array_shift( $array_files );

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

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
        $private_or_public = ( $private_or_public == 'private' ? $private_or_public : 'public-read' );

        $itemId = null;
        $original_path = $Key;

        if(file_exists($SourceFile)){
            $args = array(
                'Bucket'     => $Bucket,
                'Key'        => $this->rebuild_key($Key, $prefix),
                'SourceFile' => $SourceFile,
                'ACL'        => $private_or_public,
                'CacheControl' => $cacheControl
            );
            $type = substr(strrchr($Key, '.'), 1);
            if ( $this->should_gzip_file( $SourceFile, $type ) && false !== ( $gzip_body = gzencode( file_get_contents( $SourceFile ) ) ) ) {
                unset( $args['SourceFile'] );
                $args['Body']            = $gzip_body;
                $args['ContentEncoding'] = 'gzip';

                $mime_types = $this->get_mime_types_to_gzip( true );
                $mimes = array_keys($mime_types);
                if(in_array($type, $mimes)){
                    $args['ContentType'] = $mime_types[$type];
                }
            }

            try {
                $result = $S3_Client->putObject( $args );
            } catch (Exception $e) {
                error_log($e->getMessage());
                $result = false;
            }

            $files_to_remove[] = $SourceFile;

            try {
                $item = new carrot_bunnycdn_incoom_plugin_Item( 
                    $provider, 
                    $Region, 
                    $Bucket, 
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
        }

        if ( ! $result ) {
            error_log( 'Error when uploading SourceFile: '.$SourceFile );
        }

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
            
            if(file_exists($SourceFile)){
                $args = array(
                    'Bucket'     => $Bucket,
                    'Key'        => $this->rebuild_key($Key, $prefix),
                    'SourceFile' => $SourceFile,
                    'ACL'        => $private_or_public,
                    'CacheControl' => $cacheControl
                );

                $type = substr(strrchr($Key, '.'), 1);
                if ( $this->should_gzip_file( $SourceFile, $type ) && false !== ( $gzip_body = gzencode( file_get_contents( $SourceFile ) ) ) ) {
                    unset( $args['SourceFile'] );
                    $args['Body']            = $gzip_body;
                    $args['ContentEncoding'] = 'gzip';

                    $mime_types = $this->get_mime_types_to_gzip( true );
                    $mimes = array_keys($mime_types);
                    if(in_array($type, $mimes)){
                        $args['ContentType'] = $mime_types[$type];
                    }
                }

                try {
                    $result_of_array = $S3_Client->putObject( $args );
                } catch (Exception $e) {
                    error_log($e->getMessage());
                    $result_of_array = false;
                }

                $files_to_remove[] = $SourceFile;

                if ( ! $result_of_array ) {
                    error_log( 'Error when uploading result_of_array: '.$SourceFile );
                }
            }    

        }

        $files_to_remove = array_unique( $files_to_remove );
        $this->remove_local_files($files_to_remove, $attachment_id);
        
        return $result;

    }

    /**
	 * Remove files from the local site.
	 *
	 * @param array $files_to_remove     Files to remove.
	 * @param int   $attachment_id  Optional, if supplied filesize metadata recorded.
	 */
	public function remove_local_files( $files_to_remove, $attachment_id = 0 ) {
		return apply_filters( 'incoom_carrot_bunnycdn_incoom_plugin_remove_local_files', $files_to_remove, $attachment_id );
    }

    /**
     * Should gzip file
     *
     * @param string $file_path
     * @param string $type
     *
     * @return bool
     */
    public function should_gzip_file( $file_path, $type ) {
        return carrot_bunnycdn_incoom_plugin_should_gzip_file( $file_path, $type );
    }

    /**
     * Get mime types to gzip
     *
     * @param bool $media_library
     *
     * @return array
     */
    protected function get_mime_types_to_gzip( $media_library = false ) {
        return carrot_bunnycdn_incoom_plugin_get_mime_types_to_gzip($media_library);
    }

    /**
     * Get mime types all
     *
     * @param bool $media_library
     *
     * @return array
     */
    protected function get_allowed_mime_types() {
        return carrot_bunnycdn_incoom_plugin_get_allowed_mime_types();
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

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        foreach ( $args['Delete']['Objects'] as $file ) {

            $newKey = $this->rebuild_key($file['Key']);

            try {
                $result = $S3_Client->deleteObject([
                    'Bucket' => $Bucket,
                    'Key'    => $newKey
                ]);
            } catch (\Throwable $th) {
                error_log($th->getMessage());
            }

        }
	}

    public function delete_Objects_no_base_folder_nou( $Bucket, $Region, $array_files ) {

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        $result = 0;
        foreach ( $array_files as $Key ) {

            try{
                $result = $S3_Client->deleteObject( array(
                    'Bucket' => $Bucket,
                    'Key'    => $this->rebuild_key($Key)
                ) );
            } catch(Exception $e){
                //
            }

        }

        return $result;
    }

    /**
     * elimina un objeto de un bucket
     *
     * @param $key
     */
    public function deleteObject_nou( $Bucket, $Region, $array_files ) {

        $base_folder = array_shift( $array_files );

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        $result = 0;
        foreach ( $array_files as $File_Name ) {

            if ( $base_folder != '' ) {
                $Key = $base_folder . "/" . $File_Name;
            } else {
                $Key = $File_Name;
            }

            $newKey = $this->rebuild_key($Key);

            try {
                $result = $S3_Client->deleteObject([
                    'Bucket' => $Bucket,
                    'Key'    => $newKey
                ]);
            } catch (\Throwable $th) {
                error_log($th->getMessage());
            }

        }

        return $result;
    }

    /**
     * @param $key
     *
     * @return \Guzzle\Service\Resource\Model
     */
    public function create_Bucket( $Bucket, $Region='us-east-1' ) {

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        try {
            $result = $S3_Client->createBucket( [
                'Bucket' => $Bucket,
                'ACL' => 'public-read',
                'CreateBucketConfiguration' => [
                    'LocationConstraint' => $Region,
                ],
            ] );
            update_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select', $Bucket.'_incoom_wc_as3s_separator_'.$Region);
            carrot_bunnycdn_incoom_plugin_bucket_base_url();

        } catch ( AwsException $e ) {
            $result = ['message' => esc_html__('Access Denied to Bucket — Looks like we don\'t have write access to this bucket. It\'s likely that the user you\'ve provided credentials for hasn\'t been granted the correct permissions.', 'carrot-bunnycdn-incoom-plugin'), 'code' => '400'];
        }

        return $result;

    }

    /**
     * download files
     *
     * @param $key
     * @param $filename
     */
    public function download_file( $Bucket, $Region, $array_files, $basedir_absolute ) {
        $result = false;
        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

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

            try {
                $dir = dirname( $SaveAs );
                if ( ! wp_mkdir_p( $dir ) ) {
                    $error_message = sprintf( __( 'The local directory %s does not exist and could not be created.', 'carrot-bunnycdn-incoom-plugin' ), $dir );
                    error_log( sprintf( __( 'There was an error attempting to download the file %s from the bucket: %s', 'carrot-bunnycdn-incoom-plugin' ), $File_Name, $error_message ) );
                    return false;
                }
            } catch (\Throwable $th) {}

            try{
                $result = $S3_Client->getObject( array(
                    'Bucket' => $Bucket,
                    'Key'    => $this->rebuild_key($Key),
                    'SaveAs' => $SaveAs
                ) );
            } catch(Exception $e) {
                error_log($e->getMessage());
            }

        }

        return $result;

    }

    /**
     * download original file
     *
     * @param $key
     * @param $filename
     */
    public function download_original_file( $Bucket, $Region, $array_files, $basedir_absolute ) {
        $result = false;
        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        $base_folder = array_shift( $array_files );
        $File_Name = $array_files[0];
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
                
            $results = $S3_Client->getObject( array(
                'Bucket' => $Bucket,
                'Key'    => $this->rebuild_key($Key),
                'SaveAs' => $SaveAs
            ) );
            $result = $SaveAs;
        } catch(Exception $e) {
            error_log($e->getMessage());
        }

        return $result;

    }

    /**
     * @param $key
     *
     * @return \Guzzle\Service\Resource\Model
     */
    public function getObject( $Bucket, $Region, $key, $expires = null ) {
        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        $data = array(
            'Bucket' => $Bucket,
            'Key'    => $this->rebuild_key($key)
        );
        
        if ( !is_null( $expires ) ) {
            $data['ResponseExpires'] = $expires;
        }

        $object = $S3_Client->getObject($data);

        return $object->toArray();
    }

    /**
     * @param Update CORS
     * 
     * @since      1.0.4
     * @return \Guzzle\Service\Resource\Model
     */
    public function putBucketCors( $Bucket, $Region, $origin=array('*'), $allowed_methods=array('GET', 'HEAD'), $max_age_seconds='3600' ) {
        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
        
        try{

            $cors = array(
                array(
                    'AllowedOrigins' => $origin,
                    'AllowedMethods' => $allowed_methods,
                    'MaxAgeSeconds' => $max_age_seconds,
                    'AllowedHeaders' => array('*')
                ),
            );
            $result = $S3_Client->putBucketCors(
                array(
                    'Bucket' => $Bucket,
                    'CORSConfiguration' => array('CORSRules' => $cors)
                )
            );
           return $result;
        } catch ( AwsException $e ) {
            //
        }
    }

    public function update_cache_control_objects( $Bucket, $Region='' ) {
        set_time_limit(0);
        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
        $radio_private_or_public = get_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', 'public');
        $cacheControl = get_option('incoom_carrot_bunnycdn_incoom_plugin_cache_control', 'public, max-age=31536000');
        $private_or_public = ( $radio_private_or_public == 'private' ? $radio_private_or_public : 'public-read' );


        $S3_Client->registerStreamWrapper();
        $Current_Folder = $Bucket;
        $dir = array();
        try{
            if ( is_dir( "s3://" . $Current_Folder ) && ( $dh = opendir( "s3://" . $Current_Folder ) ) ) {

                while ( ( $object = readdir( $dh ) ) !== false ) {

                    if ( is_dir( "s3://" . $Current_Folder . "/" . $object ) ) {
                        $dir[] = $object;
                    } else {
                        try{
                            $Key = $Current_Folder . "/" . $object;
                            $Key = str_replace( $Bucket . "/", "", $Key );
                            $args = array(
                                'Bucket'                => $Bucket,
                                'CopySource'            => $Bucket . '/' . $Key,
                                'Key'                   => $Key,
                                'ACL'                   => $private_or_public,
                                'CacheControl'          => $cacheControl,
                                'MetadataDirective'     => 'COPY'
                            );
                            $this->copyObject($Region, $args);
                        } catch (Exception $e){
                            //
                        }
                    }

                }

                closedir( $dh );

            }
        }catch(Exception $e){

        }

        if(!empty($dir)){
            foreach ($dir as $prefix) {
                try{
                    $results = $S3_Client->getPaginator('ListObjects', [
                        'Bucket' => $Bucket,
                        "Prefix" => $prefix.'/'
                    ]);
                    foreach ($results as $result) {
                        foreach ($result['Contents'] as $object) {
                            $key = $object['Key'];
                            $args = array(
                                'Bucket'                => $Bucket,
                                'CopySource'            => $Bucket . '/' . $key,
                                'Key'                   => $key,
                                'ACL'                   => $private_or_public,
                                'CacheControl'          => $cacheControl,
                                'MetadataDirective'     => 'COPY'
                            );
                            $this->copyObject($Region, $args);
                        }
                    }
                } catch (Exception $e){
                    //
                }
            }
        }
        return '';
    }

    public function get_all_objects( $Bucket, $Region='' ){
        set_time_limit(0);
        
        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
        $S3_Client->registerStreamWrapper();
        $Current_Folder = $Bucket;
        $dir = array();
        $files = array();

        try{
            if ( is_dir( $this->dir . $Current_Folder ) && ( $dh = opendir( $this->dir . $Current_Folder ) ) ) {

                while ( ( $object = readdir( $dh ) ) !== false ) {

                    if ( is_dir( $this->dir . $Current_Folder . "/" . $object ) && !in_array($object, $dir) ) {
                        $dir[] = $object;
                    } else {
                        $Key = $Current_Folder . "/" . $object;
                        $Key = str_replace( $Bucket . "/", "", $Key );
                        if(!isset($files[$Bucket . '/' . $Key])){
                            $files[$Bucket . '/' . $Key] = $Key;
                        }    
                    }

                }

                closedir( $dh );

            }
        }catch(Exception $e){

        }

        if(!empty($dir)){
            foreach ($dir as $prefix) {
                try{
                    $results = $S3_Client->getPaginator('ListObjects', [
                        'Bucket' => $Bucket,
                        "Prefix" => $prefix.'/'
                    ]);
                    foreach ($results as $result) {
                        foreach ($result['Contents'] as $object) {
                            $key = $object['Key'];

                            if(!isset($files[$Bucket . '/' . $key])){
                                $files[$Bucket . '/' . $key] = $key;
                            } 
                        }
                    }
                } catch (Exception $e){
                    //
                }
            }
        }

        return $files;
    }

    public function copyObject($Region, $args){
        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
        return $S3_Client->copyObject($args);
    }

    public function updateMetadaObject($Bucket, $Region, $data){
        
        if(empty($data)){
            return false;
        }

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
        $S3_Client->registerStreamWrapper();

        $cacheControl = get_option('incoom_carrot_bunnycdn_incoom_plugin_cache_control', 'public, max-age=31536000');
        $private_or_public = ( $data['acl'] == 'private' ? $data['acl'] : 'public-read' );

        $args = array(
            'Bucket'                => $Bucket,
            'CopySource'            => $Bucket . '/' . $data['key'],
            'Key'                   => $data['key'],
            'ACL'                   => $private_or_public,
            'CacheControl'          => $cacheControl,
            'MetadataDirective'     => 'REPLACE'
        );

        $type = substr(strrchr($data['key'], '.'), 1);
        if ( $this->should_gzip_file( $this->dir.$Bucket.'/'.$data['key'], $type ) ) {
            $args['ContentEncoding'] = 'gzip';
        }

        $mime_types = array_unique(array_merge($this->get_mime_types_to_gzip( true ), $this->get_allowed_mime_types()));
        $mimes = array_keys($mime_types);
        if( in_array($type, $mimes) ){
            $args['ContentType'] = $mime_types[$type];
        }
        
        return $S3_Client->copyObject($args);
    }

    public static function docs_link_credentials(){
        return '';
    }

    public static function docs_link_create_bucket(){
        return '';
    }

    public function putFileContent($Bucket, $Region, $path, $content){
        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
        $S3_Client->registerStreamWrapper();
        return file_put_contents($path, $content);
    }

    public function copyObjectFromBucket($Bucket, $Region, $data){
        try{
            $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
            $S3_Client->registerStreamWrapper();
            if(file_exists( $this->dir.$data['source'] )){
                $old_path = $this->dir.$data['source'];
                $new_permanent_path = $this->dir.$data['bucket'].'/'.$data['key'];
                copy($old_path, $new_permanent_path);
            }
            return true;
        } catch (Exception $e){
            return false;
        }
    }

    public function putHostingContent($region, $bucket) {
        $client = $this->Init_S3_Client( $region, $this->_version, $this->_key, $this->_secret );
    	try{
            $result = $client->putObject( array(
                'Bucket'     => $bucket,
                'Key'        => 'index.html',
                'Body'   	 => 'carrot-bunnycdn-incoom-plugin -> hello index.html',
                'ACL'    	 => 'public-read'
            ) );

            if ( !$result ) {
				carrot_bunnycdn_incoom_plugin_Messages::add_error( esc_html__('Error when create hosting index.html', 'carrot-bunnycdn-incoom-plugin') );
				return false;
            }
		} catch(Exception $e){
            error_log(esc_html($e->getMessage()));
			return false;
		}
		
		try{
            $result = $client->putObject( array(
                'Bucket'     => $bucket,
                'Key'        => 'error.html',
                'Body'   	 => 'carrot-bunnycdn-incoom-plugin -> hello error.html',
                'ACL'    	 => 'public-read'
            ) );

            if ( !$result ) {
				carrot_bunnycdn_incoom_plugin_Messages::add_error( esc_html__('Error when create hosting error.html', 'carrot-bunnycdn-incoom-plugin') );
				return false;
            }
        } catch(Exception $e){
            error_log(esc_html($e->getMessage()));
			return false;
		}

		try{

            $result = $client->putBucketWebsite([
				'Bucket'                => $bucket,
				'WebsiteConfiguration'  => [
					'IndexDocument' => ['Suffix' => 'index.html'],
					'ErrorDocument' => ['Key' => 'error.html']
				]
			]);
		
			return true;

            if ( !$result ) {
				carrot_bunnycdn_incoom_plugin_Messages::add_error( esc_html__('Can\'t enable static website hosting', 'carrot-bunnycdn-incoom-plugin') );
				return false;
			}
			
        } catch ( AwsException $e ) {
            error_log(esc_html($e->getMessage()));
            return false;
		}
    }

    public function putBucketPolicy($region, $bucket) {
        $client = $this->Init_S3_Client( $region, $this->_version, $this->_key, $this->_secret );
		try{
            $result = $client->putBucketPolicy([
				'Bucket' => $bucket,
				'Policy' => '{
					"Version": "2012-10-17",
					"Statement": [
						{
							"Sid": "PublicReadGetObject",
							"Effect": "Allow",
							"Principal": "*",
							"Action": "s3:GetObject",
							"Resource": "arn:aws:s3:::'. $bucket .'/*"
						}
					]
				}',
			]);

            if ( !$result ) {
				carrot_bunnycdn_incoom_plugin_Messages::add_error( esc_html__('Can\'t set bucket policy.', 'carrot-bunnycdn-incoom-plugin') );
				return false;
			}
			return true;
			
        } catch ( AwsException $e ) {
            error_log(esc_html($e->getMessage()));
            return false;
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

        $S3_Client = $this->Init_S3_Client( $args['Region'], $this->_version, $this->_key, $this->_secret );

        $radio_private_or_public = get_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', 'public');
        /*== We check if the file is going to be private or public ==*/
        $private_or_public = ( $radio_private_or_public == 'private' ? $radio_private_or_public : 'public-read' );

        if(file_exists($args['SourceFile'])){

            $args = array(
                'Bucket'     => $args['Bucket'],
                'Key'        => $this->rebuild_key($args['Key'], ''),
                'SourceFile' => $args['SourceFile'],
                'ACL'        => $private_or_public,
                'CacheControl' => $cacheControl
            );

            try {
                $result = $S3_Client->putObject( $args );
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

        return $result;
	}

    public function uploadSingleFile( $Bucket, $Region, $SourceFile, $Key ) {
        $result = '';

        $cacheControl = get_option('incoom_carrot_bunnycdn_incoom_plugin_cache_control', 'public, max-age=31536000');

        $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );

        /*== We check if the file is going to be private or public ==*/
        $private_or_public = ( $private_or_public == 'private' ? $private_or_public : 'public-read' );

        if(file_exists($SourceFile)){

            $args = array(
                'Bucket'     => $Bucket,
                'Key'        => $this->rebuild_key($Key, ''),
                'SourceFile' => $SourceFile,
                'ACL'        => $private_or_public,
                'CacheControl' => $cacheControl
            );

            try {
                $result = $S3_Client->putObject( $args );
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

        if ( ! $result ) {
            error_log( 'Error when uploading SourceFile: '.$SourceFile );
        }
        
        return $result;

    }

    public function downloadObject($data)
    {
        if(!file_exists($data['SaveAs'])){
            // If the function it's not available, require it.
            if ( ! function_exists( 'download_url' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            
            $base_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
            $url = $base_url . '/' . $this->rebuild_key($data['Key']);

            // Copies the file to the final destination and deletes temporary file.
            try {
                $tmp_file = download_url( $url );
                if ( !is_wp_error( $tmp_file ) ) {
                    copy( $tmp_file, $data['SaveAs'] );
                    @unlink( $tmp_file );
                }
            } catch ( Exception $e ) {
                error_log($e->getMessage());
            }
        }
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
        try {
            $S3_Client = $this->Init_S3_Client( $Region, $this->_version, $this->_key, $this->_secret );
		    return $S3_Client->doesObjectExist( $Bucket, $this->rebuild_key($key), $options );
        } catch (\Throwable $th) {
            return false;
        }
	}
}