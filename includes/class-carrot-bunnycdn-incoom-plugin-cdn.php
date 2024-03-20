<?php
if (!defined('ABSPATH')) {exit;}
/**
 * Cross Origin Resource Sharing (CORS)
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.4
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */

class carrot_bunnycdn_incoom_plugin_CDN {

	/**
     * @var string
     */

    protected $client = null;
    protected $region = null;
    protected $bucket = null;


    public function __construct() {

        $Bucket_Selected = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select');

		$this->client = carrot_bunnycdn_incoom_plugin_whichtype();
		
		if($this->client::identifier() == 'google'){
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

    }

    public function putHostingContent() {
    	return $this->client->putHostingContent($this->region, $this->bucket);
    }

    public function putBucketPolicy() {
		return $this->client->putBucketPolicy($this->region, $this->bucket);
    }
}
