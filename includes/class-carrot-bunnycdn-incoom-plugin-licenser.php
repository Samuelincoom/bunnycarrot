<?php
if (!defined('ABSPATH')) {exit;}

/**
 * License Manager
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.4
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Licenser {

	private $key = null;

	private $product_id = null;

	private $license_id = null;

	private $product_base = null;

	private $server_host = null;

	private $purchase_key = null;
	private $status = null;

	private $hasCheckUpdate = true;

	private $pluginFile;

    private static $selfobj = null;

	private $plugin_name;

	private $version;

    private $isTheme = false;

    private $emailAddress = "";

	function __construct( $plugin_base_file, $plugin_name, $version ) {

		$this->plugin_name = 'carrot-bunnycdn-incoom-plugin';
		$this->version = $version;

		if ( defined( 'carrot_bunnycdn_incoom_plugin_LICENSE_API_KEY' ) ) {
			$this->key = carrot_bunnycdn_incoom_plugin_LICENSE_API_KEY;
		} else {
			$this->key = '8B1F-532D-E2AF-1AE5-82B6';
		}

		if ( defined( 'carrot_bunnycdn_incoom_plugin_LICENSE_PRODUCT_ID' ) ) {
			$this->product_id = carrot_bunnycdn_incoom_plugin_LICENSE_PRODUCT_ID;
		} else {
			$this->product_id = '1';
		}

		if ( defined( 'carrot_bunnycdn_incoom_plugin_LICENSE_LICENSE_ID' ) ) {
			$this->license_id = carrot_bunnycdn_incoom_plugin_LICENSE_LICENSE_ID;
		} else {
			$this->license_id = '1';
		}

		if ( defined( 'carrot_bunnycdn_incoom_plugin_LICENSE_HOST' ) ) {
			$this->server_host = carrot_bunnycdn_incoom_plugin_LICENSE_HOST;
		} else {
			$this->server_host = "https://www.noutheme.com/api/v1/";
		}

		$this->pluginFile = $plugin_base_file;
        $dir = dirname($plugin_base_file);
        $dir = str_replace('\\','/',$dir);

		$this->version = $this->getCurrentVersion();

		$this->emailAddress = get_option( "incoom_carrot_bunnycdn_incoom_plugin_license_email", "");

		$this->purchase_key = get_option("incoom_carrot_bunnycdn_incoom_plugin_license_key", "");

		$this->status = get_option("incoom_carrot_bunnycdn_incoom_plugin_license_active", 0);

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );

		add_filter( 'cron_schedules', array( $this, 'cron_schedule' ) );

		if ( ! wp_next_scheduled( 'incoom_carrot_bunnycdn_incoom_plugin_cron_hook' ) ) {
		    wp_schedule_event( time(), 'incoom_carrot_bunnycdn_incoom_plugin_every_six_hours', 'incoom_carrot_bunnycdn_incoom_plugin_cron_hook' );
		}

		add_action( 'incoom_carrot_bunnycdn_incoom_plugin_cron_hook', array( $this, 'cron_function' ) );
	}

	public function cron_schedule( $schedules ) {
	    $schedules['incoom_carrot_bunnycdn_incoom_plugin_every_six_hours'] = array(
	        'interval' => 21600,
	        'display'  => esc_html__( 'Every 6 hours', 'carrot-bunnycdn-incoom-plugin' ),
	    );
	    return $schedules;
	}

	public function cron_function() {
	    $this->get_product_info();
	}
	
	function getCurrentVersion(){
		if( !function_exists('get_plugin_data') ){
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$data = get_plugin_data($this->pluginFile);
		if(isset($data['Version'])){
			return $data['Version'];
		}
		return 0;
	}
	
	/**
	 * @param $plugin_base_file
	 *
	 * @return self|null
	 */
	static function &getInstance($plugin_base_file=null) {
		if(empty(self::$selfobj)){
			if(!empty($plugin_base_file)) {
				self::$selfobj = new self( $plugin_base_file );
			}
		}
		return self::$selfobj;
	}

	private function getDomain() {
		if ( defined( "WPINC" ) && function_exists( "get_bloginfo" ) ) {
			return get_bloginfo( 'url' );
		} else {
			$base_url = ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ) ? "https" : "http" );
			$base_url .= "://" . $_SERVER['HTTP_HOST'];
			$base_url .= str_replace( basename( $_SERVER['SCRIPT_NAME'] ), "", $_SERVER['SCRIPT_NAME'] );

			return $base_url;
		}
	}

	private function getEmail() {
        return $this->emailAddress;
    }

	private function process_response($response){
		
		if ( ! empty( $response ) ) {
			return json_decode($response, true);
		}

		return [
            'status'  => false,
            'statusCode' => 'FAIL',
            'data'    => [],
            'message' => '',
            'errors'  => []
        ];
	}

	private function getParams() {
		return [
			'product_id'   		=> $this->product_id,
			'license_type_id'   => $this->license_id,
			'client_email'  	=> $this->getEmail(),
			'client_domain'   	=> $this->getDomain(),
			'current_version'   => $this->version,
			'purchase_key'  	=> $this->purchase_key
		];
	}

	private function getParamsCheckDomain() {
		return [
			'product_id'   		=> $this->product_id,
			'license_type_id'   => $this->license_id,
			'client_domain'   	=> $this->getDomain()
		];
	}

	private function _request( $relative_url, $data, &$error = '' ) {
		set_time_limit(0);

		$url = rtrim( $this->server_host, '/' ) . "/" . ltrim( $relative_url, '/' );
		
		if(function_exists('wp_remote_post')) {
			$serverResponse = wp_remote_post($url, array(
					'method' => 'POST',
					'sslverify' => false,
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array('ApiKey' => $this->key),
           			'body' => $data,
					'cookies' => array()
				)
			);
			
			if (is_wp_error($serverResponse)) {
				return [
		            'status'  => false,
		            'statusCode' => 'FAIL',
		            'data'    => [],
		            'message' => $serverResponse->get_error_message(),
		            'errors'  => []
		        ];
			} else {
				if(!empty($serverResponse['body']) && $serverResponse['body'] != "GET404"){
                    return $this->process_response($serverResponse['body']);
                }
			}

		}
	}

	public function active_plugin( &$error = "", &$responseObj = null ) {
		if ( empty($this->purchase_key) || empty($this->emailAddress) ) {
			$error = "";
			return false;
		}
		$param = $this->getParams();
		$response = $this->_request( 'license/add', $param, $error );
		update_option('incoom_carrot_bunnycdn_incoom_plugin_license_active_message', $response['message']);
		if(isset($response['statusCode']) && $response['statusCode'] == 'SUCCESS'){
			update_option('incoom_carrot_bunnycdn_incoom_plugin_license_active', '1');
			return true;
		}
		update_option('incoom_carrot_bunnycdn_incoom_plugin_license_active', '0');
		return false;
	}

	/**
	 * Calls the License Manager API to get the license information for the
	 * current product.
	 *
	 * @return object|bool   The product data, or false if API call fails.
	 */
	public function get_license_info() {
	    if ( empty($this->purchase_key) || empty($this->emailAddress) ) {
			$error = "";
			return false;
		}
	 
	    $param = $this->getParams();
		$response = $this->_request( 'product/detail', $param, $error );
		if(isset($response['statusCode']) && $response['statusCode'] == 'SUCCESS'){
			return $response['data'];
		}
	    return false;
	}

	/**
	 * Calls the License Manager API to get the license information for the
	 * current product.
	 *
	 * @return object|bool   The product data, or false if API call fails.
	 */
	public function get_product_info() {
	    if ( empty($this->purchase_key) || empty($this->emailAddress) ) {
			$error = "";
			return false;
		}
	 	$count_request = get_option('incoom_carrot_bunnycdn_incoom_plugin_license_count_request_active', 0);
	    $param = $this->getParams();
		$response = $this->_request( 'product/check', $param, $error );
		if(isset($response['statusCode']) && $response['statusCode'] == 'PRODUCT_VALID'){
			update_option('incoom_carrot_bunnycdn_incoom_plugin_license_count_request_active', 0);
			return true;
		}else{
			$count_request = $count_request + 1;
			update_option('incoom_carrot_bunnycdn_incoom_plugin_license_count_request_active', $count_request);
		}
	    return false;
	}

	/**
	 * Calls the License Manager API to get the license information for the
	 * current product.
	 *
	 * @return object|bool   The product data, or false if API call fails.
	 */
	public function deactivate() {
	    if ( empty($this->purchase_key) || empty($this->emailAddress) ) {
			$error = "";
			return false;
		}

	    $param = $this->getParams();
		$response = $this->_request( 'license/deactivate', $param, $error );
		update_option('incoom_carrot_bunnycdn_incoom_plugin_license_key', '');
		update_option('incoom_carrot_bunnycdn_incoom_plugin_license_email', '');
		update_option('incoom_carrot_bunnycdn_incoom_plugin_license_active', 0);
		update_option('incoom_carrot_bunnycdn_incoom_plugin_license_active_message', '');
	    return true;
	}

	/**
	 * Calls the License Manager API to get the license information for the
	 * current product.
	 *
	 * @return object|bool   The product data, or false if API call fails.
	 */
	public function get_download_link() {
	    if ( empty($this->purchase_key) || empty($this->emailAddress) ) {
			$error = "";
			return false;
		}
	 
	    $param = $this->getParams();
		$response = $this->_request( 'product/buyer/download', $param, $error );
		if(isset($response['statusCode']) && $response['statusCode'] == 'SUCCESS'){
			return $response['data']['url'];
		}
	    return false;
	}

	/**
	 * Checks the license manager to see if there is an update available for this theme.
	 *
	 * @return object|bool  If there is an update, returns the license information.
	 *                      Otherwise returns false.
	 */
	public function is_update_available() {
	    $license_info = $this->get_license_info();
	    if ( !$license_info ) {
	        return false;
	    }
	 
	    if ( version_compare( $license_info['current_version'], $this->getCurrentVersion(), '>' ) ) {
	        return $license_info;
	    }
	 
	    return false;
	}

	public function show_admin_notices(){
		$active = get_option('incoom_carrot_bunnycdn_incoom_plugin_license_active');
	    if ( empty($this->purchase_key) || empty($this->emailAddress) || $active != '1' ) {
	 
	        $msg = esc_html__( 'Please enter your email and purchase key to enable updates to %s.', 'carrot-bunnycdn-incoom-plugin' );
	        $msg = sprintf( $msg, $this->plugin_name );
	        ?>
	            <div class="update-nag license-notice">
	                <p>
	                    <?php echo $msg; ?>
	                </p>
	 
	                <p>
	                    <a href="<?php echo admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin_licenser'); ?>">
	                        <?php esc_html_e( 'Complete the setup now.', 'carrot-bunnycdn-incoom-plugin' ); ?>
	                    </a>
	                </p>
	            </div>
	        <?php
	    }
	}

	/**
	 * The filter that checks if there are updates to the theme or plugin
	 * using the License Manager API.
	 *
	 * @param $transient    mixed   The transient used for WordPress theme updates.
	 * @return mixed        The transient with our (possible) additions.
	 */
	public function check_for_update( $transient ) {

		$active = get_option('incoom_carrot_bunnycdn_incoom_plugin_license_active');
		if($active != '1'){
			return $transient;
		}

	    if ( empty( $transient->checked ) ) {
	        return $transient;
	    }
	 
	    if ( $this->is_update_available() ) {
	        $info = $this->get_license_info();
	        if ( !$info ) {
		        return false;
		    }
	        // Plugin update
            $plugin_slug = 'carrot-bunnycdn-incoom-plugin/carrot-bunnycdn-incoom-plugin.php';

 			$url = $this->get_download_link();
 			if($url){
	            $transient->response[$plugin_slug] = (object) array(
	                'new_version' => $info['current_version'],
	                'package' => $url,
	                'slug' => $plugin_slug
	            );
	        }
	    }
	 
	    return $transient;
	}


	public function active_plugin_by_domain( &$error = "", &$responseObj = null ) {

		if(!empty($this->purchase_key) && !empty($this->emailAddress) && $this->status == '1'){
			return true;
		}

		try {
			$param = $this->getParamsCheckDomain();
			$response = $this->_request( 'license/get-license-by-domain', $param, $error );
			update_option('incoom_carrot_bunnycdn_incoom_plugin_license_active_message', $response['message']);
			if(isset($response['statusCode']) && $response['statusCode'] == 'SUCCESS'){
				$data = $response['data'];
				if(
					isset($data['domain']) && 
					isset($data['domain']['domain']) && 
					isset($data['license_key']) && 
					isset($data['license_email'])
				){
					update_option('incoom_carrot_bunnycdn_incoom_plugin_license_email', $data['license_email']);
					update_option('incoom_carrot_bunnycdn_incoom_plugin_license_key', $data['license_key']);
					update_option('incoom_carrot_bunnycdn_incoom_plugin_license_active', '1');

					if(isset($data['domain']['license']['license_type_id'])){
						update_option('incoom_carrot_bunnycdn_incoom_plugin_license_type', $data['domain']['license']['license_type_id']);
					}
					return true;
				}
			}
		} catch (Exception $e) {}

		return false;
	}

}