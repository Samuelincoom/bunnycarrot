<?php
if (!defined('ABSPATH')) {exit;}
/**
 * Cloudflare R2 Client
 *
 * @since      2.0.32
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;

class carrot_bunnycdn_incoom_plugin_Cloudflare_R2_Client extends carrot_bunnycdn_incoom_plugin_Storage {
    
    protected $_account_id;

    /**
     * @var string
     */
    protected $_key = null;

    /**
     * @var string
     */
    protected $_secret = null;

    /**
	 * Used in filters and settings.
	 *
	 * @var string
	 */
	protected static $provider_key_name = 'cloudflare-r2';

    /**
     * instancia Cloudflare R2
     * Cloudflare R2 constructor.
     */
    public function __construct( $key, $_secret, $account_id ) {

        $this->_key = $key;
        $this->_secret = $_secret;
        $this->_account_id = $account_id;
    }

    public static function identifier() {
        return 'cloudflare-r2';
    }

    public static function name() {
        return esc_html__('Cloudflare R2', 'carrot-bunnycdn-incoom-plugin');
    }

    public function Init_S3_Client( $Region = 'auto', $Version = 'latest', $key = '', $Secret = '' ) {

        if(empty($key)){
            $key = $this->_key;
        }

        if(empty($Secret)){
            $Secret = $this->_secret;
        }

        $credentials = new Aws\Credentials\Credentials($key, $Secret);

        $options = [
            'region' => 'auto',
            'endpoint' => "https://{$this->_account_id}.r2.cloudflarestorage.com",
            'version' => 'latest',
            'credentials' => $credentials
        ];

        return new Aws\S3\S3Client($options);
    }

    public function Load_Regions() {

        $this->_array_regions = [];
    }

    public function Checking_Credentials() {

        try {

            $S3_Client = $this->Init_S3_Client( 'auto', 'latest', $this->_key, $this->_secret );

            $buckets = $S3_Client->listBuckets();

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
            $S3_Client = $this->Init_S3_Client( 'auto', 'latest', $this->_key, $this->_secret );

            $buckets = $S3_Client->listBuckets();
            
            echo "<option value='0'>" . esc_html__( 'Choose a bucket', 'carrot-bunnycdn-incoom-plugin' ) . "</option>";

            foreach ( $buckets['Buckets'] as $bucket ) {
                $selected = ( ( $Bucket_Selected == $bucket['Name'] ) ? 'selected="selected"' : '' );
                ?>
                <option <?php echo $selected; ?> value="<?php echo esc_attr($bucket['Name']); ?>"><?php echo esc_html($bucket['Name']); ?> </option>
                <?php

            }

        } catch ( Exception $e ) {}

        return ob_get_clean();

    }

    public function get_base_url($Bucket = '', $Region = '', $Keyname = ''){
        return get_option('incoom_carrot_bunnycdn_connection_r2_bucket_url', '');
    }

    public function getObjectUrl( $Bucket = '', $Region = '', $File_Name = '' ) {
        $File_Name = ltrim($File_Name, '/');
        $url = get_option('incoom_carrot_bunnycdn_connection_r2_bucket_url', '');
        return "{$url}/{$File_Name}";
    }

    public static function docs_link_credentials(){
        return 'https://developers.cloudflare.com/r2/data-access/s3-api/tokens/';
    }

    public static function docs_link_create_bucket(){
        return 'https://developers.cloudflare.com/r2/data-access/public-buckets/';
    }
}