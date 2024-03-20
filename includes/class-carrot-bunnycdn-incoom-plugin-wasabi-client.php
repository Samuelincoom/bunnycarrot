<?php
if (!defined('ABSPATH')) {exit;}
/**
 * Wasabi S3 Client
 *
 * @since      1.0.2
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\AwsException;

class carrot_bunnycdn_incoom_plugin_Wasabi_Client extends carrot_bunnycdn_incoom_plugin_Storage {
    
    /**
	 * Used in filters and settings.
	 *
	 * @var string
	 */
	protected static $provider_key_name = 'wasabi';

    public static function identifier() {
        return 'wasabi';
    }

    public static function name() {
        return esc_html__('Wasabi', 'carrot-bunnycdn-incoom-plugin');
    }

    public function Init_S3_Client( $Region = '', $Version = '', $key = '', $Secret = '' ) {
        $endpoint = 'https://s3.'.$Region.'.wasabisys.com';
        $sdk = new Aws\Sdk( array(
            'endpoint'      => $endpoint,
            'region'        => $Region,
            'version'       => $Version,
            'credentials'   => array(
                'key'    => $key,
                'secret' => $Secret,
            )
        ) );
        return $sdk->createS3();
    }

    public function Load_Regions() {

        $this->_array_regions = array(
            '0'  => array( 'us-east-1', 'US East 1' ),
            '1'  => array( 'us-west-1', 'US West' ),
            '2'  => array( 'eu-central-1', 'Amsterdam, NL' ),
            '3'  => array( 'us-east-2', 'US East 2' ),
            '4'  => array( 'us-central-1', 'US Central 1 (Texas)' ),
            '5'  => array( 'ap-northeast-1', 'AP Northeast 1 (Tokyo)' ),
            '6'  => array( 'eu-west-1', 'London, England' ),
            '7'  => array( 'eu-west-2', 'Paris, France' ),
            '8'  => array( 'ap-northeast-2', 'Osaka, Japan' ),
            '9'  => array( 'eu-central-2', 'Frankfurt, Germany' ),
            '10' => array( 'ca-central-1', 'Toronto, Canada' ),
            '11' => array( 'ap-southeast-2', 'Sydney, Australia' ),
            '12' => array( 'ap-southeast-1', 'Singapore' )
        );
    }

    public function format_region($LocationConstraint){
        if(strpos($LocationConstraint, '<LocationConstraint') !== false){
            return str_replace('<LocationConstraint xmlns="http://s3.amazonaws.com/doc/2006-03-01/">', '', $LocationConstraint);
        }
        return $LocationConstraint;
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
            $S3_Client = $this->Init_S3_Client( $this->_array_regions[0][0], $this->_version, $this->_key, $this->_secret );

            $buckets = $S3_Client->listBuckets();
            $regions = $this->Get_Regions();
            
            echo "<option value='0'>" . esc_html__( 'Choose a bucket', 'carrot-bunnycdn-incoom-plugin' ) . "</option>";

            foreach ( $buckets['Buckets'] as $bucket ) {

                try {
                    $result = $S3_Client->getBucketLocation(array(
                        'Bucket' => $bucket['Name'],
                    ));
                } catch ( S3Exception $e ) {
                    $result = false;
                }

                if ( $result ){
                    $region = $this->format_region($result['LocationConstraint']);
                    if(in_array($region, $regions)){
                        $selected = ( ( $Bucket_Selected == $bucket['Name'] . "_incoom_wc_as3s_separator_" . $region ) ? 'selected="selected"' : '' );

                        ?>
<option <?php echo $selected; ?> value="<?php echo esc_attr($bucket['Name'] . "_incoom_wc_as3s_separator_" . $region); ?>">
    <?php echo esc_html($bucket['Name'] . " - " . $region); ?> </option>
<?php
                    }    

                }

            }

        } catch ( Exception $e ) {

            //
        }

        return ob_get_clean();

    }

    public static function docs_link_credentials(){
        return 'https://wasabi-support.zendesk.com/hc/en-us/articles/360019677192-Creating-a-Root-Access-Key-and-Secret-Key';
    }

    public static function docs_link_create_bucket(){
        return 'https://wasabi.com/wp-content/themes/wasabi/docs/User_Guide/topics/Creating_a_Bucket.htm';
    }

}