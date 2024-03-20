<?php
/**
 * Get provider
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.2
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */

use Jenssegers\Agent\Agent;

/**
 * Check PHP version
 * @since      1.0.8
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_version_check(){
	if ( function_exists( 'phpversion' ) && version_compare( phpversion(), carrot_bunnycdn_incoom_plugin_MINIMUM_PHP_VERSION, '>=' ) ) {
		return true;
	}
	return false;
}

/**
 * Check cache folder
 * @since      1.0.29
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_cache_folder_check(){
	if ( is_writable(carrot_bunnycdn_incoom_plugin_CACHE_PATH) ) {
		return true;
	}
	return false;
}


/**
 * Wrapper for _doing_it_wrong().
 *
 * @since  1.0.31
 * @param string $function Function used.
 * @param string $message Message to log.
 * @param string $version Version the message was added in.
 */
function incoom_carrot_bunnycdn_incoom_plugin_doing_it_wrong( $function, $message, $version ) {
	// @codingStandardsIgnoreStart
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();
	_doing_it_wrong( $function, $message, $version );
	// @codingStandardsIgnoreEnd
}


/**
 * Load template with variables
 * @since      1.0.8
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_load_template($filePath, $variables = array(), $print = true){
    $output = NULL;
    $path = carrot_bunnycdn_incoom_plugin_PLUGIN_DIR.$filePath;
    if(file_exists($path)){
        // Extract the variables to a local namespace
        extract($variables);

        // Start output buffering
        ob_start();

        // Include the template file
        include $path;

        // End buffering and return its contents
        $output = ob_get_clean();
    }
    if ($print) {
        print $output;
    }
    return $output;

}

/**
 * Create file
 *
 * @param bool $with_credentials Do provider credentials need to be set up too? Defaults to false.
 * @since      1.0.4
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_get_domain() {
	return get_home_url('/');
}

/**
 * Create file
 *
 * @param bool $with_credentials Do provider credentials need to be set up too? Defaults to false.
 * @since      1.0.4
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_create_file( $file ) {
	$path = trailingslashit( $file['base'] ) . $file['file'];
	if ( wp_mkdir_p( $file['base'] ) && ! file_exists( $path ) ) {
		$file_handle = @fopen( $path, 'w' );
		if ( $file_handle ) {
			fwrite( $file_handle, $file['content'] );
			fclose( $file_handle );
			return $path;
		}
	}
	
	return false;
}

/**
 * Create files
 *
 * @param bool $with_credentials Do provider credentials need to be set up too? Defaults to false.
 * @since      1.0.4
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_create_files( $files ) {
	
	foreach ( $files as $file ) {
		incoom_carrot_bunnycdn_incoom_plugin_create_file($file);
	}

	// All good, let's do this
	return true;
}

function carrot_bunnycdn_incoom_plugin_whichtype_settings() {
	return get_option('incoom_carrot_bunnycdn_incoom_plugin');
}

/**
 * Check the plugin is correctly setup
 *
 * @param bool $with_credentials Do provider credentials need to be set up too? Defaults to false.
 *
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup( $with_credentials = false ) {

	if(!incoom_carrot_bunnycdn_incoom_plugin_version_check()){
		return false;
	}
	
	$active 		= get_option('incoom_carrot_bunnycdn_incoom_plugin_license_active');
	$emailAddress 	= get_option('incoom_carrot_bunnycdn_incoom_plugin_license_email');
	$purchase_key 	= get_option('incoom_carrot_bunnycdn_incoom_plugin_license_key');
    if ( empty($purchase_key) || empty($emailAddress) || $active != '1' ){
		return false;
	}
	    	
	$connection = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_success');
	if(!$connection){
		return false;
	}

	$settings = carrot_bunnycdn_incoom_plugin_whichtype_settings();
	$provider = isset($settings['provider']) ? $settings['provider'] : 'aws';
	if($provider == 'DO'){
		$regional = get_option('incoom_carrot_bunnycdn_incoom_plugin_bucket_regional');
		if(empty($regional)){
			return false;
		}
	}

	if($provider == 'cloudflare-r2'){
		$account_id = get_option('incoom_carrot_bunnycdn_connection_r2_account_id');
		if(empty($account_id)){
			return false;
		}
	}

	$bucket = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select');
	if(!$bucket){
		return false;
	}

	// All good, let's do this
	return true;
}

/**
 * Check plugin BBoss is activate
 *
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_is_bb_activate() {
	
	global $buddyboss_platform_plugin_file;
	if ( class_exists( 'BuddyPress' ) && is_string( $buddyboss_platform_plugin_file ) && ! is_multisite() ) {
		return true;
	}

	return false;
}

/**
 * Check the plugin enable mod rewrite url
 *
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls() {

	if(!incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup()){
		return false;
	}
	    	
	$rewrite_urls = get_option('incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox');
	if(empty($rewrite_urls)){
		return false;
	}

	// All good, let's do this
	return true;
}


/**
 * Get provider
 *
 * @return class
 */
function carrot_bunnycdn_incoom_plugin_whichtype($provider='', $settings=[]) {
	if(empty($provider)){
		$settings = carrot_bunnycdn_incoom_plugin_whichtype_settings();
		$provider = isset($settings['provider']) ? $settings['provider'] : 'aws';
	}
	$class = '';
	switch ($provider) {
		case 'wasabi':
			$Access_Key = $settings['access_key'];
			$Secret_Access_Key = $settings['secret_access_key'];
			$class = new carrot_bunnycdn_incoom_plugin_Wasabi_Client( $Access_Key, $Secret_Access_Key );
			break;
		case 'google':
			if(isset($settings['credentials_key']) && !empty($settings['credentials_key'])){
				$Access_Key = $settings['credentials_key'];
			}else{
				$Access_Key = get_option('incoom_carrot_bunnycdn_incoom_plugin_google_credentials');
			}
			$Secret_Access_Key = '';
			$class = new carrot_bunnycdn_incoom_plugin_Google( $Access_Key, $Secret_Access_Key );
			break;
		case 'aws':
			$Access_Key = $settings['access_key'];
			$Secret_Access_Key = $settings['secret_access_key'];
			$class = new carrot_bunnycdn_incoom_plugin_Aws_Client( $Access_Key, $Secret_Access_Key );
			break;
		case 'DO':
			$Access_Key = $settings['access_key'];
			$Secret_Access_Key = $settings['secret_access_key'];
			$region = isset($settings['region']) ? $settings['region'] : null;
			$class = new carrot_bunnycdn_incoom_plugin_DO_Client( $Access_Key, $Secret_Access_Key );
			$class->setRegion($region);
			break;
		case 'bunnycdn':
			$Access_Key = $settings['access_key'];
			$storage_key = get_option('incoom_carrot_bunnycdn_connection_bunny_storage_key');
			$storage_path = get_option('incoom_carrot_bunnycdn_connection_bunny_storage_path');
			$class = new carrot_bunnycdn_incoom_plugin_Bunny_Client( $Access_Key, $storage_key, $storage_path );
			break;
		case 'cloudflare-r2':
			$Access_Key = $settings['access_key'];
			$Secret_Access_Key = $settings['secret_access_key'];
			$account_id = get_option('incoom_carrot_bunnycdn_connection_r2_account_id');
			$class = new carrot_bunnycdn_incoom_plugin_Cloudflare_R2_Client( $Access_Key, $Secret_Access_Key, $account_id );
			break;
		default:
			carrot_bunnycdn_incoom_plugin_Messages::add_error( esc_html__("Provider not found.", 'carrot-bunnycdn-incoom-plugin') );
			break;
	}

	return $class;
}

/**
 * Get provider info
 *
 *
 * @return class
 */
function carrot_bunnycdn_incoom_plugin_whichtype_info($provider='', $settings=[]) {
	$upload_dir = wp_upload_dir();
	$basedir_absolute = $upload_dir['basedir'];

	if(empty($provider)){
		$settings = carrot_bunnycdn_incoom_plugin_whichtype_settings();
		$provider = isset($settings['provider']) ? $settings['provider'] : 'aws';
	}

	$aws_s3_client = carrot_bunnycdn_incoom_plugin_whichtype($provider, $settings);
	
	$Bucket_Selected = get_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select', '' );

	if($provider == 'google'){
		$Bucket                = $Bucket_Selected;
		$Region                = 'none';
	}elseif($provider == 'bunnycdn'){
		$Bucket                = $Bucket_Selected;
		$Region                = 'none';
	}elseif($provider == 'cloudflare-r2'){
		$Region = 'auto';
		$Bucket = $Bucket_Selected;
	}else{
		$Array_Bucket_Selected = explode( "_incoom_wc_as3s_separator_", $Bucket_Selected );

		if ( count( $Array_Bucket_Selected ) == 2 ){
			$Bucket                = $Array_Bucket_Selected[0];
			$Region                = $Array_Bucket_Selected[1];
		}
		else{
			$Bucket                = 'none';
			$Region                = 'none';
		}
	}

	if ( $Bucket == 'none' ){
		return false;
	}

	return array( $aws_s3_client, $Bucket, $Region, $basedir_absolute );
}


function carrot_bunnycdn_incoom_plugin_text_actions($action){
	$provider = carrot_bunnycdn_incoom_plugin_whichtype();
	$text = '';
	switch ($action) {
		case 'incoom_carrot_bunnycdn_copy_to_s3':
			$text = sprintf(esc_html__('Copy to %s', 'carrot-bunnycdn-incoom-plugin'), $provider::name());
			break;
		case 'incoom_carrot_bunnycdn_remove_from_server':
			$text = esc_html__('Remove from server', 'carrot-bunnycdn-incoom-plugin');
			break;
		case 'incoom_carrot_bunnycdn_copy_to_server_from_s3':
			$text = sprintf(esc_html__('Download file to server from %s', 'carrot-bunnycdn-incoom-plugin'), $provider::name());
			break;
		case 'incoom_carrot_bunnycdn_remove_from_s3':
			$text = sprintf(esc_html__('Remove from %s', 'carrot-bunnycdn-incoom-plugin'), $provider::name());
			break;
		case 'incoom_carrot_bunnycdn_build_webp':
			$text = esc_html__('Rebuild WebP version', 'carrot-bunnycdn-incoom-plugin');
			break;
		default:
			# code...
			break;
	}
	return $text;
}

/**
 * Checking connection success to amazon s3
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */
function carrot_bunnycdn_incoom_plugin_check_connection_success() {

	if ( ! get_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_success' ) ) {

		echo "<div>";

		echo "<p class='incoom_carrot_bunnycdn_error_accessing_class'>";

		$Path_warning_image = esc_url(carrot_bunnycdn_incoom_plugin_PLUGIN_URI.'admin/images/Warning.png');

		echo "<img class='incoom_carrot_bunnycdn_error_accessing_class_img' style='width: 35px;' src='$Path_warning_image'/>";
		echo "<span class='incoom_carrot_bunnycdn_error_accessing_class_span'>";
		esc_html_e( 'You have to configure your Access Key and Secret Access Key correctly in the "Connect to your s3 amazon account" tab',
            'carrot-bunnycdn-incoom-plugin' );
		echo "</span>";

		echo "</p>";

		echo "<br>";

		echo "</div>";

		return 0;

	}
	else
	{

		$Bucket_Selected = ( get_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select' ) ? get_option( 'incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select' ) : '' );

		$Array_Bucket_Selected = explode( "_incoom_wc_as3s_separator_", $Bucket_Selected );

		if ( count( $Array_Bucket_Selected ) != 2 ){

			echo "<div>";

			echo "<p class='incoom_carrot_bunnycdn_error_accessing_class'>";

			$Path_warning_image = esc_url(carrot_bunnycdn_incoom_plugin_PLUGIN_URI.'admin/images/Warning.png');

			echo "<img class='incoom_carrot_bunnycdn_error_accessing_class_img' style='width: 35px;' src='$Path_warning_image'/>";
			echo "<span class='incoom_carrot_bunnycdn_error_accessing_class_span'>";
			esc_html_e( 'You have to choose a bucket in the "Setting" tab in the Amazon S3 admin panel', 'carrot-bunnycdn-incoom-plugin' );
			echo "</span>";

			echo "</p>";

			echo "<br>";

			echo "</div>";

			return 0;

		}
		else
			return 1;
	}

}

/**
 * Return the default object prefix
 *
 * @return string
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */

function carrot_bunnycdn_incoom_plugin_get_default_object_prefix() {
	if ( is_multisite() ) {
		return 'wp-content/uploads/';
	}

	$uploads = wp_upload_dir();
	$parts   = parse_url( $uploads['baseurl'] );
	$path    = ltrim( $parts['path'], '/' );

	return trailingslashit( $path );
}


/**
 * @return array
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */
function carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $post_id ) {

	$array_aux = explode( '/', get_post_meta( $post_id, '_wp_attached_file', true ) );
	$main_file = array_pop( $array_aux );

	// Creating an array with all the files with different sizes.
	// The first element of the array is the folder content.
	// Second element is the main file with no personal size
	$array_files[] = implode( "/", $array_aux );
	$array_files[] = $main_file;

	// Getting the rest of the sizes of the file to add to the array
	$array_metadata = wp_get_attachment_metadata( $post_id );

	if ( ! empty( $array_metadata ) && isset( $array_metadata['sizes'] ) )
	{
		$array_metadata = $array_metadata['sizes'];
		foreach ( $array_metadata as $metadata ) {
			$array_files[] = $metadata['file'];
		}
	}

	list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();

	return array( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute );

}

/**
 * Get attachment provider info
 *
 * @param int $post_id
 *
 * @return mixed
 */
function carrot_bunnycdn_incoom_plugin_get_attachment_provider_info( $post_id ) {

	if(!incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup()){
		return false;
	}

	$cacheKey = "_carrot_bunnycdn_attachment_{$post_id}_provider_info";
	$provider_object = incoom_carrot_bunnycdn_incoom_plugin_get_cache_item($cacheKey);
	if(!empty($provider_object)){
		return $provider_object;
	}

	$provider_object = get_post_meta( $post_id, '_incoom_carrot_bunnycdn_amazonS3_info', true );

	list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();
	
	if(!is_array($provider_object)){
		$provider_object = [];
	}

	$key = get_post_meta( $post_id, '_wp_attached_file', true );
	$itemProvider = isset($provider_object['provider']) ? $provider_object['provider'] : '';

	try {
		$bucketFolder = $aws_s3_client->getBucketMainFolder();
		if($bucketFolder){
			$key = $bucketFolder.$key;
		}
	} catch (\Throwable $th) {
		//throw $th;
	}
	
	$provider_object['key'] = $key;

	$class      = carrot_bunnycdn_incoom_plugin_get_source_type_name();
	$carrot_item = null;

	try {
		$carrot_item = $class::get_by_source_id( $post_id );
		if ( !empty( $carrot_item->id() ) && $carrot_item->provider() == $itemProvider ) {
			$provider_url = $carrot_item->get_provider_url($key);
			if($provider_url){
				$provider_object['url'] = $provider_url;
			}
		}
	} catch (\Throwable $th) {}

	update_post_meta( $post_id, '_incoom_carrot_bunnycdn_amazonS3_info', $provider_object );

	incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($cacheKey, $provider_object);

	return apply_filters( 'carrot_bunnycdn_incoom_plugin_get_attachment_provider_info', $provider_object, $post_id );
}

function carrot_bunnycdn_incoom_plugin_get_bucket_url(){
	$base_url = get_option('incoom_carrot_bunnycdn_incoom_plugin_aws_connection_bucket_base_url');
	if( filter_var($base_url, FILTER_VALIDATE_URL) ){
	    return $base_url;
	}
	update_option('incoom_carrot_bunnycdn_incoom_plugin_aws_connection_bucket_base_url', '');
	update_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select', '');
	carrot_bunnycdn_incoom_plugin_Messages::add_error( esc_html__("carrot-bunnycdn-incoom-plugin: Bucket URL is invalid. Please, check again.", 'carrot-bunnycdn-incoom-plugin') );
}

/**
 * Is attachment served by provider.
 *
 * @param int           $attachment_id
 * @param bool          $skip_rewrite_check          Still check if offloaded even if not currently rewriting URLs? Default: false
 * @param bool          $skip_current_provider_check Skip checking if offloaded to current provider. Default: false, negated if $provider supplied
 * @param Provider|null $provider                    Provider where attachment expected to be offloaded to. Default: currently configured provider
 *
 * @return bool|array
 */
function carrot_bunnycdn_incoom_plugin_is_attachment_served_by_provider( $attachment_id, $skip_rewrite_check = false, $skip_current_provider_check = false, $provider = 'aws' ) {
	
	if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_get_attachment_provider_info( $attachment_id ) ) ) {
		// File not uploaded to a provider
		return false;
	}

	return $provider_object;
}

/**
 * Build WebP
 *
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */
function carrot_bunnycdn_incoom_plugin_build_webp_function( $post_id, $data = array() ) {

	$attachment_key = get_post_meta( $post_id, '_wp_attached_file', true );
	if(empty($attachment_key)){
		return false;
	}

	if(carrot_bunnycdn_incoom_plugin_enable_webp()){

		$webpData = get_post_meta($post_id, '_incoom_carrot_bunnycdn_webp_info', true);
		if(empty($webpData)){
			$is_permission = get_post_meta( $post_id, 'carrot_downloadable_file_permission', true);
			if(carrot_bunnycdn_incoom_plugin_Utils::can_build_webp($post_id) && $is_permission != 'yes'){
				try {
					$webp = new carrot_bunnycdn_incoom_plugin_Webp($post_id);
					$webp->do_converts();
				} catch (Exception $e) {
					error_log("Error convert webp: ". $e->getMessage());
				}
			}
		}
	}
}

function carrot_bunnycdn_incoom_plugin_remove_webp_function( $post_id ) {
	$webpData = get_post_meta($post_id, '_incoom_carrot_bunnycdn_webp_info', true);
	if(!empty($webpData)){
		$bucket_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
		$attachment_key = get_post_meta( $post_id, '_wp_attached_file', true );

		$array_files = [];
		$array_aux = explode( '/', $attachment_key );
		$main_file = array_pop( $array_aux );
		$array_files[] = implode( "/", $array_aux );

		if(count($array_files) > 0){
			$strReplace = "{$bucket_url}/{$array_files['0']}/";
		}else{
			$strReplace = "{$bucket_url}/";
		}

		foreach($webpData as $url){
			$file = str_replace($strReplace, '', $url);
			$array_files[] = $file;
		}

		try{
			list( $aws_s3_client, $Bucket, $Region ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $post_id );
			$result = $aws_s3_client->deleteObject_nou( $Bucket, $Region, $array_files );
			update_post_meta( $post_id, '_incoom_carrot_bunnycdn_webp_info', [] );
		}catch(Exception $e){
			error_log($e->getMessage());
		}

	}
}

/**
 * Add logs
 *
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      2.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */
function carrot_bunnycdn_incoom_plugin_add_log($data){
	global $wpdb;

	try {
		$table = $wpdb->prefix.'carrot_offload_stats';
		$data = [
			'post_id'       => $data['post_id'],
			'action'       	=> $data['action'],
			'num_items' 	=> !empty($data['total']) ? $data['total'] : 1,
			'date_created' 	=> date('Y-m-d H:i:s')
		];

		$format = [
			'%d',
			'%s',
			'%d',
			'%s'
		];
		$wpdb->insert($table, $data, $format);
		$result = $wpdb->insert_id;
	} catch (\Throwable $th) {}
}

/**
 * Copy to AWS S3
 *
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */
function carrot_bunnycdn_incoom_plugin_copy_to_s3_function( $post_id, $private_or_public = 'public', $data = array() ) {

	$attachment_key = get_post_meta( $post_id, '_wp_attached_file', true );
	if(empty($attachment_key)){
		return false;
	}

	$accepted_filetypes = get_option('incoom_carrot_bunnycdn_incoom_plugin_accepted_filetypes', '');
	if(!empty($accepted_filetypes)){
		$types = explode( ',', $accepted_filetypes );
		$extension = substr(strrchr($attachment_key, '.'), 1);
		if(!empty($types) && is_array($types)){
			if(!in_array($extension, $types)){
				return false;
			}
		}
	}

	$class      = carrot_bunnycdn_incoom_plugin_get_source_type_name();
	$carrot_item = null;

	try {
		$carrot_item = $class::get_by_source_id( $post_id );
	} catch (\Throwable $th) {}

	try {
		if ( empty( $carrot_item ) || empty( $carrot_item->id() ) ) {
			$carrot_item = $class::create_from_source_id( $post_id );
			if ( is_wp_error( $carrot_item ) ) {
				return false;
			}else{
				$carrot_item->save();
			}
		}
	} catch (\Throwable $th) {
		error_log("Error carrot_bunnycdn_incoom_plugin_copy_to_s3_function: ". $th->getMessage());
	}

	if($carrot_item){
		$upload_handler = carrot_bunnycdn_incoom_plugin_get_item_handler( carrot_bunnycdn_incoom_plugin_Upload_Handler::get_item_handler_key_name() );
		$upload_result  = $upload_handler->handle( $carrot_item );
		
		if ( $upload_result ) {
		
			try {
				carrot_bunnycdn_incoom_plugin_set_cache_attached_file( $post_id );
			} catch (\Throwable $th) {}
	
			$remove_local_files_setting = get_option('incoom_carrot_bunnycdn_incoom_plugin_remove_from_server_checkbox');
			if ( $remove_local_files_setting ) {
				carrot_bunnycdn_incoom_plugin_remove_from_server_function( $post_id );
			}
	
			do_action( 'carrot_bunnycdn_incoom_plugin_copy_file_to_provider', $carrot_item );
		}
	}
}

/**
 * Remove from S3
 *
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */
function carrot_bunnycdn_incoom_plugin_remove_from_s3_function( $post_id ) {

	$class      = carrot_bunnycdn_incoom_plugin_get_source_type_name();
	$carrot_item = null;

	try {
		// Skip item if item already on provider.
		$carrot_item = $class::get_by_source_id( $post_id );
		if ( empty( $carrot_item->id() ) ) {
			$carrot_item = $class::create_from_source_id( $post_id );
			if ( is_wp_error( $carrot_item ) ) {
				return false;
			}else{
				$carrot_item->save();
			}
		}
	} catch (\Throwable $th) {}

	try{
		carrot_bunnycdn_incoom_plugin_remove_webp_function($post_id);
	}catch(Exception $e){
		error_log($e->getMessage());
	}

	if(!empty($carrot_item)){
		$remove_provider_handler = carrot_bunnycdn_incoom_plugin_get_item_handler( carrot_bunnycdn_incoom_plugin_Remove_Provider_Handler::get_item_handler_key_name() );
		$remove_provider_handler->handle( $carrot_item );
	}
}

/**
 * Copy to server from S3
 *
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */
function carrot_bunnycdn_incoom_plugin_copy_to_server_from_s3_function( $post_id ) {

	$checkDownloaded = get_post_meta($post_id, '_wp_incoom_carrot_bunnycdn_copy_to_server', true);
	if($checkDownloaded == '1'){
		return;
	}

	$class = carrot_bunnycdn_incoom_plugin_get_source_type_name();
	$carrot_item = null;

	try {
		// Skip item if item already on provider.
		$carrot_item = $class::get_by_source_id( $post_id );
		if ( empty( $carrot_item->id() ) ) {
			$carrot_item = $class::create_from_source_id( $post_id );
			if ( is_wp_error( $carrot_item ) ) {
				return false;
			}else{
				$carrot_item->save();
			}
		}
	} catch ( Exception $e ) {
		error_log($e->getMessage());
	}

	if(!empty($carrot_item)){
		$download_handler = carrot_bunnycdn_incoom_plugin_get_item_handler( carrot_bunnycdn_incoom_plugin_Download_Handler::get_item_handler_key_name() );
		$download_result  = $download_handler->handle( $carrot_item );
		update_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_s3_wordpress_path', '1' );
		update_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_copy_to_server', '1' );	
	}

}

/**
 * Remove from server
 *
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/functions
 */
function carrot_bunnycdn_incoom_plugin_remove_from_server_function( $post_id, $data = array() ) {

	$class      = carrot_bunnycdn_incoom_plugin_get_source_type_name();
	$carrot_item = null;

	try {
		// Skip item if item already on provider.
		$carrot_item = $class::get_by_source_id( $post_id );
		if ( empty( $carrot_item->id() ) ) {
			$carrot_item = $class::create_from_source_id( $post_id );
			if ( is_wp_error( $carrot_item ) ) {
				return false;
			}else{
				$carrot_item->save();
			}
		}
	} catch (\Throwable $th) {}

	if(!empty($carrot_item)){
		$remove_handler = carrot_bunnycdn_incoom_plugin_get_item_handler( carrot_bunnycdn_incoom_plugin_Remove_Local_Handler::get_item_handler_key_name() );
		$remove_result  = $remove_handler->handle( $carrot_item );
		
		update_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_copy_to_server', '2' );
		update_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_s3_wordpress_path', '_wp_incoom_carrot_bunnycdn_s3_wordpress_path_not_in_used' );
	}

	return true;

}

function carrot_bunnycdn_incoom_plugin_remove_local_files( $files_to_remove, $post_id ){

	foreach ( $files_to_remove as $path ) {
		// Individual files might still be kept local, but we're still going to count them towards total above.
		
		if ( false !== ( $pre = apply_filters( 'carrot_bunnycdn_incoom_plugin_preserve_file_from_local_removal', false, $path ) ) ) {
			continue;
		}

		if ( ! @unlink( $path ) ) {
			$message = esc_html__('Error removing local file ', 'carrot-bunnycdn-incoom-plugin');

			if ( ! file_exists( $path ) ) {
				$message = esc_html__("Error removing local file. Couldn't find the file at ", 'carrot-bunnycdn-incoom-plugin');
			} else if ( ! is_writable( $path ) ) {
				$message = esc_html__('Error removing local file. Ownership or permissions are mis-configured for ', 'carrot-bunnycdn-incoom-plugin');
			}
		}else{
			$message = esc_html__('Completed remove local file ', 'carrot-bunnycdn-incoom-plugin');
		}
	}
}

/**
 * Get the url of the file from Amazon provider
 *
 * @param int         $post_id            Post ID of the attachment
 * @param int|null    $expires            Seconds for the link to live
 * @param string|null $size               Size of the image to get
 * @param array|null  $meta               Pre retrieved _wp_attachment_metadata for the attachment
 * @param array       $headers            Header overrides for request
 * @param bool        $skip_rewrite_check Always return the URL regardless of the 'Rewrite File URLs' setting.
 *                                        Useful for the EDD and Woo addons to not break download URLs when the
 *                                        option is disabled.
 *
 * @return bool|mixed|WP_Error
 */
function carrot_bunnycdn_incoom_plugin_get_attachment_url( $post_id, $expires = null, $size = null, $meta = null, $headers = array(), $skip_rewrite_check = false ) {
	$provider_object = carrot_bunnycdn_incoom_plugin_is_attachment_served_by_provider( $post_id, $skip_rewrite_check );
	if ( !$provider_object ) {
		return false;
	}

	$url = carrot_bunnycdn_incoom_plugin_get_attachment_provider_url( $post_id, $provider_object, $expires, $size, $meta, $headers );

	return apply_filters( 'incoom_carrot_bunnycdn_wp_get_attachment_url', $url, $post_id );
}

/**
 * Convert dimensions to size
 *
 * @param int   $attachment_id
 * @param array $dimensions
 *
 * @return null|string
 */
function carrot_bunnycdn_incoom_plugin_convert_dimensions_to_size_name( $attachment_id, $dimensions ) {
	$w                     = ( isset( $dimensions[0] ) && $dimensions[0] > 0 ) ? $dimensions[0] : 1;
	$h                     = ( isset( $dimensions[1] ) && $dimensions[1] > 0 ) ? $dimensions[1] : 1;
	$original_aspect_ratio = $w / $h;
	$meta                  = wp_get_attachment_metadata( $attachment_id );

	if ( ! isset( $meta['sizes'] ) || empty( $meta['sizes'] ) ) {
		$data = get_post_meta($attachment_id, '_incoom_carrot_bunnycdn_amazonS3_info', true);
		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			if(isset($data['data']['sizes'])){
				$meta = $data['data'];
			}else{
				return null;
			}
		}else{
			return null;
		}
	}
	
	if(empty($meta)){
		return null;
	}

	$sizes = $meta['sizes'];
	uasort( $sizes, function ( $a, $b ) {
		// Order by image area
		return ( $a['width'] * $a['height'] ) - ( $b['width'] * $b['height'] );
	} );

	$nearest_matches = array();

	foreach ( $sizes as $size => $value ) {
		if ( $w > $value['width'] || $h > $value['height'] ) {
			continue;
		}

		$aspect_ratio = $value['width'] / $value['height'];

		if ( $aspect_ratio === $original_aspect_ratio ) {
			return $size;
		}

		$nearest_matches[] = $size;
	}

	// Return nearest match
	if ( ! empty( $nearest_matches ) ) {
		return $nearest_matches[0];
	}

	return null;
}

/**
 * Return the scheme to be used in URLs
 *
 * @param bool $use_ssl
 *
 * @return string
 */
function carrot_bunnycdn_incoom_plugin_get_url_scheme( $use_ssl = true ) {
	if ( $use_ssl ) {
		$scheme = 'https';
	} else {
		$scheme = 'http';
	}

	return $scheme;
}

/**
 * Maybe convert size to string
 *
 * @param int   $attachment_id
 * @param mixed $size
 *
 * @return null|string
 */
function carrot_bunnycdn_incoom_plugin_maybe_convert_size_to_string( $attachment_id, $size ) {
	if ( is_array( $size ) ) {
		return carrot_bunnycdn_incoom_plugin_convert_dimensions_to_size_name( $attachment_id, $size );
	}

	return $size;
}

/**
 * Potentially update path for CloudFront URLs.
 *
 * @param string $path
 *
 * @return string
 */
function carrot_bunnycdn_incoom_plugin_maybe_update_cloudfront_path( $path ) {
	if(!incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
		return $path;
	}

	$cloudfront = get_option('incoom_carrot_bunnycdn_incoom_plugin_cname');
	if ( $cloudfront ) {
		$path_parts = apply_filters( 'incoom_carrot_bunnycdn_incoom_plugin_cloudfront_path_parts', explode( '/', $path ), $cloudfront );

		if ( ! empty( $path_parts ) ) {
			$path = implode( '/', $path_parts );
		}
	}

	return urldecode($path);
}

function carrot_bunnycdn_incoom_plugin_get_cname_url($bucket_base_url = '', $private_url = 'no'){
	$url = false;
	$cloudfront = get_option('incoom_carrot_bunnycdn_incoom_plugin_cname');
	if ( !empty($cloudfront) && $private_url != 'yes' ) {
		$base_url = empty($bucket_base_url) ? carrot_bunnycdn_incoom_plugin_get_bucket_url() : $bucket_base_url;
		$base_domain = str_replace('https://', '', $base_url);
		$url = str_replace($base_domain, $cloudfront, $base_url);
		$url = "{$url}/";
	}

	return $url;
}

/**
 * Format S3 to CloudFront URLs.
 *
 * @param string $url
 *
 * @return string
 */
function carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url( $url, $bucket_base_url='', $only_rewrite_assets=false ) {
	if(!$only_rewrite_assets){
		if(!incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
			return $url;
		}
	}

	// Exclude media, EX: mp4,avi,mkv
	$exclude_filetypes = get_option('incoom_carrot_bunnycdn_incoom_plugin_cdn_exclude_filetypes', '');
	if(!empty($exclude_filetypes)){
		$types = explode( ',', $exclude_filetypes );
		$extension = substr(strrchr($url, '.'), 1);
		if(!empty($types) && is_array($types)){
			if(in_array($extension, $types)){
				return $url;
			}
		}
	}
	
	$private_url = 'no';
	$domain = parse_url($url);
	$url_private = isset($domain['path']) ? $domain['path'] : '';
	if(!empty($url_private)){
		$url_private = ltrim($url_private, '/');
		$attachment_id = carrot_bunnycdn_incoom_plugin_get_post_id($url_private);
		if($attachment_id){
			$private_url = get_post_meta($attachment_id, 'carrot_downloadable_file_permission', true);
		}
	}

	$cloudfront = get_option('incoom_carrot_bunnycdn_incoom_plugin_cname');
	if ( !empty($cloudfront) && $private_url != 'yes' ) {
		$base_url = empty($bucket_base_url) ? carrot_bunnycdn_incoom_plugin_get_bucket_url() : $bucket_base_url;
		$base_domain = str_replace('https://', '', $base_url);
		$url = str_replace($base_domain, $cloudfront, $url);
	}

	$force_https = get_option('incoom_carrot_bunnycdn_incoom_plugin_force_https_checkbox');
	if($force_https){
		$url = str_replace('http://', 'https://', $url);
	}

	return $url;
}

/**
 * Get the provider URL for an attachment
 *
 * @param int               $post_id
 * @param array             $provider_object
 * @param null|int          $expires
 * @param null|string|array $size
 * @param null|array        $meta
 * @param array             $headers
 *
 * @return mixed|WP_Error
 */
function carrot_bunnycdn_incoom_plugin_get_attachment_provider_url( $post_id, $provider_object, $expires = null, $size = null, $meta = null, $headers = array() ) {
	// We don't use $this->get_provider_object_region() here because we don't want
	// to make an AWS API call and slow down page loading
	if ( isset( $provider_object['region'] ) ) {
		$region = $provider_object['region'];
	} else {
		$region = '';
	}

	$size = carrot_bunnycdn_incoom_plugin_maybe_convert_size_to_string( $post_id, $size );

	// Force use of secured URL when ACL has been set to private
	if ( !is_null( $expires ) ) {
		$expires  = time() + apply_filters( 'incoom_carrot_bunnycdn_incoom_plugin_expires', $expires );
	}

	if ( ! is_null( $size ) ) {
		if ( is_null( $meta ) ) {
			$meta = get_post_meta( $post_id, '_wp_attachment_metadata', true );
			if ( ! isset( $meta['sizes'] ) || empty( $meta['sizes'] ) ) {
				$data = get_post_meta($post_id, '_incoom_carrot_bunnycdn_amazonS3_info', true);
				if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
					$meta = $data['data'];
				}
			}
		}

		if ( is_wp_error( $meta ) ) {
			return $meta;
		}

		if ( ! empty( $meta ) && isset( $meta['sizes'][ $size ]['file'] ) ) {
			$size_prefix      = dirname( $provider_object['key'] );
			$size_file_prefix = ( '.' === $size_prefix ) ? '' : $size_prefix . '/';

			$provider_object['key'] = $size_file_prefix.$meta['sizes'][ $size ]['file'];
		}
	}

	if ( !incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {
		return $meta;
	}
	
	try {
		
		list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $post_id );
		$provider_object['key'] = carrot_bunnycdn_incoom_plugin_maybe_update_cloudfront_path( $provider_object['key'] );
		$secure_url = $aws_s3_client->getObjectUrl( $Bucket, $Region, $provider_object['key']);
		return apply_filters( 'incoom_carrot_bunnycdn_incoom_plugin_get_attachment_secure_url', $secure_url, $provider_object, $post_id, $headers );
	} catch ( Exception $e ) {
		return wp_get_attachment_url($post_id);
	}

}


/**
 * Maybe remove query string from URL.
 *
 * @param string $url
 *
 * @return string
 */
function incoom_carrot_bunnycdn_incoom_plugin_maybe_remove_query_string( $url ) {
	$parts = explode( '?', $url );

	return reset( $parts );
}

/**
 * Helper to switch to a Multisite blog
 *  - If the site is MS
 *  - If the blog is not the current blog defined
 *
 * @param int|bool $blog_id
 */
function incoom_carrot_bunnycdn_incoom_plugin_switch_to_blog( $blog_id = false ) {
	if ( ! is_multisite() ) {
		return;
	}

	if ( ! $blog_id ) {
		$blog_id = defined( 'BLOG_ID_CURRENT_SITE' ) ? BLOG_ID_CURRENT_SITE : 1;
	}

	if ( $blog_id !== get_current_blog_id() ) {
		switch_to_blog( $blog_id );
	}
}

/**
 * Helper to restore to the current Multisite blog
 */
function incoom_carrot_bunnycdn_incoom_plugin_restore_current_blog() {
	if ( is_multisite() ) {
		restore_current_blog();
	}
}


/**
 * Is the current blog ID that specified in wp-config.php
 *
 * @param int $blog_id
 *
 * @return bool
 */
function incoom_carrot_bunnycdn_incoom_plugin_is_current_blog( $blog_id ) {
	$default = defined( 'BLOG_ID_CURRENT_SITE' ) ? BLOG_ID_CURRENT_SITE : 1;

	if ( $default === $blog_id ) {
		return true;
	}

	return false;
}

/**
 * Encode file names according to RFC 3986 when generating urls
 * As per Amazon https://forums.aws.amazon.com/thread.jspa?threadID=55746#jive-message-244233
 *
 * @param string $file
 *
 * @return string Encoded filename
 */
function incoom_carrot_bunnycdn_incoom_plugin_encode_filename_in_path( $file ) {
	$url = parse_url( $file );

	if ( ! isset( $url['path'] ) ) {
		// Can't determine path, return original
		return $file;
	}

	if ( isset( $url['query'] ) ) {
		// Manually strip query string, as passing $url['path'] to basename results in corrupt � characters
		$file_name = wp_basename( str_replace( '?' . $url['query'], '', $file ) );
	} else {
		$file_name = wp_basename( $file );
	}

	if ( false !== strpos( $file_name, '%' ) ) {
		// File name already encoded, return original
		return $file;
	}

	$encoded_file_name = rawurlencode( $file_name );

	if ( $file_name === $encoded_file_name ) {
		// File name doesn't need encoding, return original
		return $file;
	}

	return str_replace( $file_name, $encoded_file_name, $file );
}

/**
 * Decode file name.
 *
 * @param string $file
 *
 * @return string
 */
function incoom_carrot_bunnycdn_incoom_plugin_decode_filename_in_path( $file ) {
	$url = parse_url( $file );

	if ( ! isset( $url['path'] ) ) {
		// Can't determine path, return original
		return $file;
	}

	$file_name = wp_basename( $url['path'] );

	if ( false === strpos( $file_name, '%' ) ) {
		// File name not encoded, return original
		return $file;
	}

	$decoded_file_name = rawurldecode( $file_name );

	return str_replace( $file_name, $decoded_file_name, $file );
}


/**
 * Ensure local URL is correct for multisite's non-primary subsites.
 *
 * @param string $url
 *
 * @return string
 */
function incoom_carrot_bunnycdn_incoom_plugin_maybe_fix_local_subsite_url( $url ) {
	$siteurl = trailingslashit( get_option( 'siteurl' ) );

	if ( is_multisite() && ! incoom_carrot_bunnycdn_incoom_plugin_is_current_blog( get_current_blog_id() ) && 0 !== strpos( $url, $siteurl ) ) {
		// Replace network URL with subsite's URL.
		$network_siteurl = trailingslashit( network_site_url() );
		$url             = str_replace( $network_siteurl, $siteurl, $url );
	}

	return $url;
}

/**
 * Get attachment local URL.
 *
 * @param int $post_id
 *
 * @return string|false Attachment URL, otherwise false.
 */
function incoom_carrot_bunnycdn_incoom_plugin_get_attachment_local_url( $post_id ) {
	$url = '';

	// Get attached file.
	if ( $file = get_post_meta( $post_id, '_wp_attached_file', true ) ) {
		// Get upload directory.
		if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) {
			// Check that the upload base exists in the file location.
			if ( 0 === strpos( $file, $uploads['basedir'] ) ) {
				// Replace file location with url location.
				$url = str_replace( $uploads['basedir'], $uploads['baseurl'], $file );
			} elseif ( false !== strpos( $file, 'wp-content/uploads' ) ) {
				$url = $uploads['baseurl'] . substr( $file, strpos( $file, 'wp-content/uploads' ) + 18 );
			} else {
				// It's a newly-uploaded file, therefore $file is relative to the basedir.
				$url = $uploads['baseurl'] . "/$file";
			}
		}
	}

	if ( empty( $url ) ) {
		return false;
	}

	$url = incoom_carrot_bunnycdn_incoom_plugin_maybe_fix_local_subsite_url( $url );

	return $url;
}

/**
 * Get attachment local URL size.
 *
 * @param int         $post_id
 * @param string|null $size
 *
 * @return false|string
 */
function incoom_carrot_bunnycdn_incoom_plugin_get_attachment_local_url_size( $post_id, $size = null ) {
	$url = incoom_carrot_bunnycdn_incoom_plugin_get_attachment_local_url( $post_id );

	if ( empty( $size ) ) {
		return $url;
	}

	$meta = get_post_meta( $post_id, '_wp_attachment_metadata', true );

	if ( empty( $meta['sizes'][ $size ]['file'] ) ) {
		// No alternative sizes available, return
		return $url;
	}

	return str_replace( wp_basename( $url ), $meta['sizes'][ $size ]['file'], $url );
}


/**
 * Do Bulk Action.
 *
 * @param int         $post_id
 * @param string|null $size
 *
 * @return false|string
 */
function incoom_carrot_bunnycdn_incoom_plugin_do_bulk_actions_extra_options_function( $doaction, $post_ids ) {
	
	switch ( $doaction ) {

		case 'incoom_carrot_bunnycdn_copy_to_s3':
			$radio_private_or_public = get_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', 'public');
			foreach ( $post_ids as $post_id ) {
				$s3_path = get_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_s3_path', true );
				if ( $s3_path == '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' || $s3_path == null ) {
					carrot_bunnycdn_incoom_plugin_copy_to_s3_function( $post_id, $radio_private_or_public );
				}
			}

			break;

		case 'incoom_carrot_bunnycdn_remove_from_s3':
			foreach ( $post_ids as $post_id ) {
				carrot_bunnycdn_incoom_plugin_remove_from_s3_function( $post_id );
			}

			break;

		case 'incoom_carrot_bunnycdn_copy_to_server_from_s3':
			
			foreach ( $post_ids as $post_id ) {
				carrot_bunnycdn_incoom_plugin_copy_to_server_from_s3_function( $post_id );
			}

			break;

		case 'incoom_carrot_bunnycdn_remove_from_server':
			
			foreach ( $post_ids as $post_id ) {
				carrot_bunnycdn_incoom_plugin_remove_from_server_function( $post_id );
			}

			break;

		case 'incoom_carrot_bunnycdn_build_webp':
			
			foreach ( $post_ids as $post_id ) {
				carrot_bunnycdn_incoom_plugin_build_webp_function( $post_id );
			}

			break;
	}

}

/**
 * Sign intermediate size.
 *
 * @param string       $url
 * @param int          $attachment_id
 * @param string|array $size
 * @param bool|array   $provider_object
 *
 * @return mixed|WP_Error
 */
function carrot_bunnycdn_incoom_plugin_maybe_sign_intermediate_size( $url, $attachment_id, $size, $provider_object = false ) {
	if ( ! $provider_object ) {
		$provider_object = carrot_bunnycdn_incoom_plugin_get_attachment_provider_info( $attachment_id );
	}

	$size = carrot_bunnycdn_incoom_plugin_maybe_convert_size_to_string( $attachment_id, $size );

	if ( isset( $provider_object['sizes'][ $size ] ) ) {
		// Private file, add AWS signature if required
		return carrot_bunnycdn_incoom_plugin_get_attachment_provider_url( $attachment_id, $provider_object, null, $size );
	}

	return $url;
}

function carrot_bunnycdn_incoom_plugin_get_provider_service_name($key_name){
	switch ($key_name) {
		case 'google':
			$name = esc_html__('Google Cloud Storage', 'carrot-bunnycdn-incoom-plugin');
			break;
		case 'wasabi':
			$name = esc_html__('Wasabi', 'carrot-bunnycdn-incoom-plugin');
			break;
		case 'DO':
			$name = esc_html__('DigitalOcean Spaces', 'carrot-bunnycdn-incoom-plugin');
			break;
		case 'bunnycdn':
			$name = esc_html__('Bunny CDN', 'carrot-bunnycdn-incoom-plugin');
			break;
		
		default:
			$name = esc_html__('Amazon S3', 'carrot-bunnycdn-incoom-plugin');
			break;
	}
	return $name;
}

/**
 * Return a formatted S3 info with display friendly defaults
 *
 * @param int        $id
 * @param array|null $provider_object
 *
 * @return array
 */
function carrot_bunnycdn_incoom_plugin_get_formatted_provider_info( $id, $provider_object = null ) {
	if ( is_null( $provider_object ) ) {
		if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_get_attachment_provider_info( $id ) ) ) {
			return false;
		}
	}

	$provider_object['url'] = carrot_bunnycdn_incoom_plugin_get_attachment_provider_url( $id, $provider_object );

	if ( ! empty( $provider_object['provider'] ) ) {
		$provider_object['provider_name'] = carrot_bunnycdn_incoom_plugin_get_provider_service_name($provider_object['provider']);
	}

	return $provider_object;
}

function carrot_bunnycdn_incoom_plugin_row_actions_extra( $actions, $post_id ) {

	if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {

		$wordpress_path = get_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_s3_wordpress_path', true );
		$s3_path        = get_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_s3_path', true );
		$is_permission = get_post_meta( $post_id, 'carrot_downloadable_file_permission', true);

		// Show the copy to s3 link if the file is not in S3
		if ( $s3_path == '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' || $s3_path == null ) {
			$actions['incoom_carrot_bunnycdn_copy_to_s3'] = '<a href="post.php?post=' . esc_attr($post_id) . '&action=incoom_carrot_bunnycdn_copy_to_s3">'.carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_copy_to_s3').'</a>';
		}

		// Remove the file from the server if it is in both places (wordpress installation and S3) otherwise user will click in "delete permanently"
		if ( ( $s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' && $s3_path != null ) && ( $wordpress_path != '_wp_incoom_carrot_bunnycdn_s3_wordpress_path_not_in_used' && $wordpress_path != null ) ) {
			$actions['incoom_carrot_bunnycdn_remove_from_server'] = '<a href="post.php?post=' . esc_attr($post_id) . '&action=incoom_carrot_bunnycdn_remove_from_server">'.carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_remove_from_server').'</a>';
		}

		// Show the copy to server from S3 link if the file is not in the server and it is in S3
		if ( ( $wordpress_path == '_wp_incoom_carrot_bunnycdn_s3_wordpress_path_not_in_used' || $wordpress_path == null ) && ( $s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' && $s3_path != null ) ) {
			$actions['incoom_carrot_bunnycdn_copy_to_server_from_s3'] = '<a href="post.php?post=' . esc_attr($post_id) . '&action=incoom_carrot_bunnycdn_copy_to_server_from_s3">'.carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_copy_to_server_from_s3').'</a>';
		}

		// Remove the file from S3 if it is in both places (wordpress installation and S3) otherwise user will click in "delete permanently"
		if ( ( $s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' && $s3_path != null ) && ( $wordpress_path != '_wp_incoom_carrot_bunnycdn_s3_wordpress_path_not_in_used' && $wordpress_path != null ) ) {
			$actions['incoom_carrot_bunnycdn_remove_from_s3'] = '<a href="post.php?post=' . esc_attr($post_id) . '&action=incoom_carrot_bunnycdn_remove_from_s3">'.carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_remove_from_s3').'</a>';
		}

		// Build WebP
		if(carrot_bunnycdn_incoom_plugin_enable_webp()){
			if ( carrot_bunnycdn_incoom_plugin_Utils::can_build_webp($post_id) && $is_permission != 'yes' ) {
				$actions['incoom_carrot_bunnycdn_build_webp'] = '<a href="post.php?post=' . esc_attr($post_id) . '&action=incoom_carrot_bunnycdn_build_webp">'.carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_build_webp').'</a>';
			}
		}
	}

	return $actions;

}

function carrot_bunnycdn_incoom_plugin_bucket_base_url() {

	$Bucket_Selected = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select');
	
	$aws_s3_client = carrot_bunnycdn_incoom_plugin_whichtype();
	if($aws_s3_client::identifier() == 'google'){
		$Base_url = $aws_s3_client->get_base_url(  $Bucket_Selected, null, null );
	}elseif($aws_s3_client::identifier() == 'bunnycdn'){
		$Base_url = $aws_s3_client->get_base_url(  $Bucket_Selected );
	}elseif($aws_s3_client::identifier() == 'cloudflare-r2'){
		$Base_url = $aws_s3_client->get_base_url(null, null, null);
	}else{

		$Array_Bucket_Selected = explode( "_incoom_wc_as3s_separator_", $Bucket_Selected );

        if ( count( $Array_Bucket_Selected ) == 2 ){
            $Bucket                = $Array_Bucket_Selected[0];
            $Region                = $Array_Bucket_Selected[1];
        }
        else{
            $Bucket                = 'none';
            $Region                = 'none';
        }

        if($aws_s3_client::identifier() == 'DO'){
        	$Base_url = $aws_s3_client->get_base_url( $Bucket, $Region, '' );
        }else{
        	$result = $aws_s3_client->delete_Objects_no_base_folder_nou( $Bucket, $Region, array( '5a90320d39a72_incoom_wc_as3s_5a90320d39a8a.txt', '5a902e5124a80_incoom_wc_as3s_5a902e5124a86.txt', '5a902be279c34_incoom_wc_as3s_5a902be279c3btxt' ) );

	        $Keyname = uniqid() . '_incoom_wc_as3s_' . uniqid() . '.txt';

	        $Base_url = $aws_s3_client->get_base_url( $Bucket, $Region, $Keyname );

	        $result = $aws_s3_client->delete_Objects_no_base_folder_nou( $Bucket, $Region, array( $Keyname ) );
        }
    }

    update_option( 'incoom_carrot_bunnycdn_incoom_plugin_aws_connection_bucket_base_url', $Base_url );

}

function carrot_bunnycdn_incoom_plugin_clone_option($from_option_key, $to_option_key) {
	$from_option = get_option($from_option_key);
	if(!empty($from_option)){
		update_option($to_option_key, $from_option);
	}
}

function carrot_bunnycdn_incoom_plugin_get_post_id_from_attached_file_scaled($path) {
	$post_id = false;
	$key = 'carrot_bunnycdn_sql_get_id_from_meta_scaled_'.wp_hash($path);
	$post_id_cache = incoom_carrot_bunnycdn_incoom_plugin_get_cache_item($key);
	if(!empty($post_id_cache)){
		$post_id = $post_id_cache;
	}else{
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$name = str_replace(".{$ext}", "", $path);
		$new_name = "{$name}-scaled.{$ext}";
		global $wpdb;
		$meta = $wpdb->get_row("SELECT post_id FROM `".$wpdb->postmeta."` WHERE meta_key='_wp_attached_file' AND meta_value='".esc_sql($new_name)."'");
		if (is_object($meta)) {
			$post_id_cache = incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($key, $meta->post_id);
			$post_id = $post_id_cache;
		}
	}

	return $post_id;
}

function carrot_bunnycdn_incoom_plugin_get_post_id_from_attached_file($path) {
	$post_id = false;
	$key = 'carrot_bunnycdn_sql_get_id_from_meta_'.wp_hash($path);
	$post_id_cache = incoom_carrot_bunnycdn_incoom_plugin_get_cache_item($key);
	if(!empty($post_id_cache)){
		$post_id = $post_id_cache;
	}else{
		global $wpdb;
		$meta = $wpdb->get_row("SELECT post_id FROM `".$wpdb->postmeta."` WHERE meta_key='_wp_attached_file' AND meta_value='".esc_sql($path)."'");
		if (is_object($meta)) {
			$post_id_cache = incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($key, $meta->post_id);
			$post_id = $post_id_cache;
		}
	}

	return $post_id;
}

function carrot_bunnycdn_incoom_plugin_set_cache_attached_file($post_id){
	$path = get_post_meta( $post_id, '_wp_attached_file', true );
	if($path){
		$key = 'carrot_bunnycdn_sql_get_id_from_meta_'.wp_hash($path);
		incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($key, $post_id);
	}
}

function carrot_bunnycdn_incoom_plugin_get_post_id($old_url) {
	
	if(strpos($old_url, '.js') !== false){
		return false;
	}

	if(strpos($old_url, '.css') !== false){
		return false;
	}

	if(strpos($old_url, '.webp') !== false){
		$old_url = str_replace('.webp', '', $old_url);
	}

	$_wp_attached_file = carrot_bunnycdn_incoom_plugin_Utils::get_key_from_url($old_url);
	
	$post_id = false;
	$key = carrot_bunnycdn_incoom_plugin_CACHE_KEY_ATTACHED_FILE.wp_hash($_wp_attached_file);

	$post_id_cache = incoom_carrot_bunnycdn_incoom_plugin_get_cache_item($key);
	if(!empty($post_id_cache)){
		$post_id = $post_id_cache;
	}else{
		$get_post_id = carrot_bunnycdn_incoom_plugin_get_post_id_from_attached_file($_wp_attached_file);
		if ($get_post_id) {
			$post_id_cache = incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($key, $get_post_id);
			$post_id = $post_id_cache;
		}else{
			$get_post_id = carrot_bunnycdn_incoom_plugin_get_post_id_from_attached_file_scaled($_wp_attached_file);
			if ($get_post_id) {
				$post_id_cache = incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($key, $get_post_id);
				$post_id = $post_id_cache;
			}
		}
	}
	
	return $post_id;
}

function carrot_bunnycdn_incoom_plugin_calculator_sync_processed() {
	$processed = count(get_option('incoom_carrot_bunnycdn_incoom_plugin_synced_data', []));
	if($processed == 0){
		return 0;
	}

	$sync = new carrot_bunnycdn_incoom_plugin_Sync();
	$cacheData = $sync->getCacheData();

	$total = count($cacheData);
	if($total == 0){
		return 0;
	}
	return round($processed / $total * 100);
}


function carrot_bunnycdn_incoom_plugin_has_backup_option() {
	$default = carrot_bunnycdn_incoom_plugin_whichtype_settings();
	$default_bk = get_option('incoom_carrot_bunnycdn_incoom_plugin_backup');
	$has_backup = false;
	if(!empty($default_bk) && $default_bk['provider'] != $default['provider']){
		$has_backup = true;
	}

	$bucket = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select');
	$bucket_bk = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select_backup');

	if(!empty($bucket_bk) && $bucket != $bucket_bk){
		$has_backup = true;
	}
	return $has_backup;
}

function carrot_bunnycdn_incoom_plugin_get_real_provider($post_id){
	$provider_object = carrot_bunnycdn_incoom_plugin_get_attachment_provider_info($post_id);
	$settings = carrot_bunnycdn_incoom_plugin_whichtype_settings();
	$provider = isset($settings['provider']) ? $settings['provider'] : 'aws';
	list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();
	$base_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();

	if(!is_array($provider_object)){
		$provider_object = [];
	}

	$key = isset($provider_object['key']) ? $provider_object['key'] : '';
	
	return array(
		'provider' 			=> $provider,
		'provider_name' 	=> carrot_bunnycdn_incoom_plugin_get_provider_service_name($provider),
		'region'   			=> $Region,
		'bucket'   			=> $Bucket,
		'base_url'  		=> $base_url.'/'.$key,
		'key' 	   			=> isset($provider_object['key']) ? $provider_object['key'] : '',
		'data'     			=> isset($provider_object['data']) ? $provider_object['data'] : []
	);
}

function carrot_bunnycdn_incoom_plugin_get_real_url($current_url){
	$upload_base_urls = carrot_bunnycdn_incoom_plugin_Utils::get_bare_upload_base_urls();
	if ( str_replace( $upload_base_urls, '', $current_url ) === $current_url ) {
		// Remote host
		$domain = parse_url($current_url);
		$path = isset($domain['path']) ? $domain['path'] : '';
		if(!empty($path)){
			$base_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
			$Bucket_Selected = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select');
	
			$aws_s3_client = carrot_bunnycdn_incoom_plugin_whichtype();
			$Bucket = $Bucket_Selected;
			if($aws_s3_client::identifier() != 'google'){
				$Array_Bucket_Selected = explode( "_incoom_wc_as3s_separator_", $Bucket_Selected );

		        if ( count( $Array_Bucket_Selected ) == 2 ){
		            $Bucket = $Array_Bucket_Selected[0];
		        }else{
		            $Bucket = 'none';
		        }
		    }

		    if($Bucket !== 'none'){
		    	if(strpos($path, $Bucket.'/') !== false){
		    		$path = str_replace($Bucket.'/', '', $path);
		    	}
		    }

			return $base_url.$path;
		}
	}
	return $current_url;
}

function carrot_bunnycdn_incoom_plugin_get_url_from_key($key){
	list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();
	$base_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();

	$folder_main = get_option('incoom_carrot_bunnycdn_incoom_plugin_bucket_folder_main', '');
    if(!empty($folder_main)){
    	if(substr($folder_main, -1) == '/') {
            $folder_main = substr($folder_main, 0, -1);
        }
        if(strpos($key, $folder_main) !== false){
        	$key = str_replace($folder_main.'/', '', $key);
        }
    }

	$bucketFolder = $aws_s3_client->getBucketMainFolder();

	if($bucketFolder){
		$key = $bucketFolder.$key;
	}
	
	$provider_url = $base_url.'/'.$key;
	return carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url($provider_url);
}


/**
 * Generator the WordPress loop.
 *
 * @since 1.0
 * @param WP_Query|WP_Post[] $iterable
 *
 * @return Generator
 */
function carrot_bunnycdn_incoom_plugin_lazy_loop($iterable = null){
    return carrot_bunnycdn_incoom_plugin_Lazy_Query_Loop::generator($iterable);
}

function carrot_bunnycdn_incoom_plugin_unschedule_action($hook){
	global $wpdb;

	try {
		$table = $wpdb->prefix.'actionscheduler_actions';
		$updated = $wpdb->update(
			$table,
			[ 'status' => 'canceled' ],
			[ 'hook' => $hook, 'status' => 'pending' ],
			[ '%s' ],
			[ '%s', '%s' ]
		);
	} catch (\Throwable $th) {
		error_log($th->getMessage());
	}
}

/**
 * Action after scan attachments completed.
 *
 * @since 1.0.31
 */
function carrot_bunnycdn_incoom_plugin_scan_attachments_completed(){

	update_option('incoom_carrot_bunnycdn_incoom_plugin_step_scan_attachments', 2);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_output_progress', []);

	try {
		if ( function_exists( 'carrot_bunnycdn_incoom_plugin_unschedule_action' ) ) {
			carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_scan_attachments' );
		}
	} catch (\Throwable $th) {}

	$action = get_option('incoom_carrot_bunnycdn_incoom_plugin_action');

	switch($action){
		case "copy_files_to_bucket":
			update_option('incoom_carrot_bunnycdn_incoom_plugin_copyed_to_cloud_status', 1);

			if ( function_exists( 'as_schedule_recurring_action' ) ) {
				as_schedule_recurring_action( strtotime( 'now' ), 10, 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_copy_attachments_to_cloud' );
			}
			break;
		case "remove_files_from_server":
			update_option('incoom_carrot_bunnycdn_incoom_plugin_remove_files_from_server_status', 1);

			if ( function_exists( 'as_schedule_recurring_action' ) ) {
				error_log("Create action sync data: remove_files_from_server");
				as_schedule_recurring_action( strtotime( 'now' ), 5, 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_remove_files_from_server' );
			}
			break;
		case "remove_files_from_bucket":
			update_option('incoom_carrot_bunnycdn_incoom_plugin_remove_files_from_bucket_status', 1);

			if ( function_exists( 'as_schedule_recurring_action' ) ) {
				error_log("Create action sync data: remove_files_from_bucket");
				as_schedule_recurring_action( strtotime( 'now' ), 15, 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_remove_files_from_bucket' );
			}
			break;
		case "download_files_from_bucket":
			update_option('incoom_carrot_bunnycdn_incoom_plugin_download_files_from_bucket_status', 1);

			if ( function_exists( 'as_schedule_recurring_action' ) ) {
				error_log("Create action sync data: download_files_from_bucket");
				as_schedule_recurring_action( strtotime( 'now' ), 15, 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_download_files_from_bucket' );
			}
			break;
		default:
			break;
	}
}

function carrot_bunnycdn_incoom_plugin_datetime($format = 'now'){
	$timezone = wp_timezone();
	return new DateTime( $format, $timezone );
}

function carrot_bunnycdn_incoom_plugin_cronjob_timed(){

	$dt = carrot_bunnycdn_incoom_plugin_datetime();
	$h_from = (int) get_option('incoom_carrot_bunnycdn_cronjob_timed_from', 24);
	$h_to = (int) get_option('incoom_carrot_bunnycdn_cronjob_timed_to', 24);
	$current = (int) $dt->format("G");
	if($h_from < 24 && $h_to < 24){
		if($current > $h_from && $current < $h_to){
			return true;
		}else{
			return false;
		}
	}
	return true;
}

function carrot_bunnycdn_incoom_plugin_get_sync_objects($key) {
	$value = incoom_carrot_bunnycdn_incoom_plugin_get_cache_item($key);
	if(!empty($value)){
		return $value;
	}

	return [];
}

function carrot_bunnycdn_incoom_plugin_set_sync_objects($key, $objects) {
	
	try {
		incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($key, $objects);
		return $objects;
	} catch (\Throwable $th) {
		//throw $th;
	}

	return false;
}

function carrot_bunnycdn_incoom_plugin_after_action_scheduler_completed(){

	if ( function_exists( 'carrot_bunnycdn_incoom_plugin_unschedule_action' ) ) {
		carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_scan_attachments' );
		carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_copy_attachments_to_cloud' );
		carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_remove_files_from_server' );
		carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_remove_files_from_bucket' );
		carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_download_files_from_bucket' );
	}

	update_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_attachments', []);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_attachments_copy', []);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_copyed_to_cloud_data', []);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_processed_downloadd_files_from_bucket', []);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_processed_removed_files_from_bucket', []);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_processed_removed_files_from_server', []);

	update_option('incoom_carrot_bunnycdn_incoom_plugin_step_scan_attachments', 0);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_page_scaned_attachments', 1);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_scaned_pages_attachments', 0);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_output_progress', []);

	update_option('incoom_carrot_bunnycdn_incoom_plugin_copyed_to_cloud_status', 0);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_remove_files_from_server_status', 0);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_remove_files_from_bucket_status', 0);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_download_files_from_bucket_status', 0);
	update_option('incoom_carrot_bunnycdn_incoom_plugin_action', '');

	try {
		delete_post_meta_by_key('incoom_carrot_bunnycdn_scanned_status');
	} catch (\Throwable $th) {}

	try {
		delete_transient( 'carrot_' . get_current_blog_id() . '_media_counts' );
		delete_transient( 'carrot_' . get_current_blog_id() . '_attachment_counts' );
	} catch (\Throwable $th) {}

	do_action('carrot_bunnycdn_incoom_plugin_action_scheduler_completed');
}

/**
 * Calls the specified mb_*** function if it is available.
 * If it isn't, calls the regular function instead
 * @param string $fn The function name to call
 * @return mixed
 */
function carrot_bunnycdn_incoom_plugin_mb($fn) {
	static $available = null;
	if ( is_null($available) ) $available = extension_loaded( 'mbstring' );

	if ( func_num_args() > 1 ) {
		$args = func_get_args();
		array_shift( $args ); // Remove 1st arg
		return $available ?
			call_user_func_array( "mb_{$fn}", $args ) :
			call_user_func_array( $fn, $args );
	}
	return $available ?
		call_user_func( "mb_{$fn}" ) :
		call_user_func( $fn );
}

function carrot_bunnycdn_incoom_plugin_pathinfo( $path, $options = null ) {
	if ( is_null( $options ) ) {
		$r = array ();
		if ( $x = carrot_bunnycdn_incoom_plugin_pathinfo( $path, PATHINFO_DIRNAME ) ) $r['dirname'] = $x;
		$r['basename'] = carrot_bunnycdn_incoom_plugin_pathinfo( $path, PATHINFO_BASENAME );
		if ( $x = carrot_bunnycdn_incoom_plugin_pathinfo( $path, PATHINFO_EXTENSION ) ) $r['extension'] = $x;
		$r['filename'] = carrot_bunnycdn_incoom_plugin_pathinfo( $path, PATHINFO_FILENAME );
		return $r;
	}
	if ( !$path ) return '';
	$path = rtrim( $path, DIRECTORY_SEPARATOR );
	switch ( $options ) {
	case PATHINFO_DIRNAME:
		$x = carrot_bunnycdn_incoom_plugin_mb( 'strrpos', $path, DIRECTORY_SEPARATOR ); // The last occurrence of slash
		return is_int($x) ? carrot_bunnycdn_incoom_plugin_mb( 'substr', $path, 0, $x ) : '.';

	case PATHINFO_BASENAME:
		$x = carrot_bunnycdn_incoom_plugin_mb( 'strrpos', $path, DIRECTORY_SEPARATOR ); // The last occurrence of slash
		return is_int($x) ? carrot_bunnycdn_incoom_plugin_mb( 'substr', $path, $x + 1 ) : $path;

	case PATHINFO_EXTENSION:
		$x = carrot_bunnycdn_incoom_plugin_mb( 'strrpos', $path, '.' ); // The last occurrence of dot
		return is_int($x) ? carrot_bunnycdn_incoom_plugin_mb( 'substr', $path, $x + 1 ) : '';

	case PATHINFO_FILENAME:
		$basename = carrot_bunnycdn_incoom_plugin_pathinfo( $path, PATHINFO_BASENAME );
		$x = carrot_bunnycdn_incoom_plugin_mb( 'strrpos', $basename, '.' ); // The last occurrence of dot
		return is_int($x) ? carrot_bunnycdn_incoom_plugin_mb( 'substr', $basename, 0, $x ) : $basename;
	}
	return pathinfo( $path, $options );
}

/**
 * A multibyte compatible implementation of dirname()
 * @param string $path
 * @return string
 */
function carrot_bunnycdn_incoom_plugin_dirname( $path ) {
	return carrot_bunnycdn_incoom_plugin_pathinfo( $path, PATHINFO_DIRNAME );
}

/**
 * A multibyte compatible implementation of basename()
 * @param string $path
 * @return string
 */
function carrot_bunnycdn_incoom_plugin_basename( $path ) {
	return carrot_bunnycdn_incoom_plugin_pathinfo( $path, PATHINFO_BASENAME );
}

/**
 * A PHP desktop/mobile user agent parser
*/
function carrot_bunnycdn_incoom_plugin_agent(){
	return (new Agent());
}

function carrot_bunnycdn_incoom_plugin_enable_webp(){
	$enable_webp = get_option('incoom_carrot_bunnycdn_incoom_plugin_webp');
	if($enable_webp != 'on'){
		return false;
	}
	return true;
}
/**
 * Check user agent can use WebP
*/
function carrot_bunnycdn_incoom_plugin_can_use_webp(){

	if(!carrot_bunnycdn_incoom_plugin_enable_webp()){
		return false;
	}

	$agent = carrot_bunnycdn_incoom_plugin_agent();
	if($agent->browser() == 'Safari'){
		return false;
	}

	return true;
}

function carrot_bunnycdn_incoom_plugin_tempnam( $filename = '', $dir = '' ) {
    if ( empty( $dir ) ) {
        $dir = get_temp_dir();
    }
 
    if ( empty( $filename ) || in_array( $filename, array( '.', '/', '\\' ), true ) ) {
        $filename = uniqid();
    }
 
    // Use the basename of the given file without the extension as the name for the temporary directory.
    $temp_filename = basename( $filename );
    $temp_filename = preg_replace( '|\.[^.]*$|', '', $temp_filename );
 
    // If the folder is falsey, use its parent directory name instead.
    if ( ! $temp_filename ) {
        return carrot_bunnycdn_incoom_plugin_tempnam( dirname( $filename ), $dir );
    }
 
    // Suffix some random data to avoid filename conflicts.
    $temp_filename .= '-' . wp_generate_password( 6, false );
    $temp_filename .= '.tmp';
    $temp_filename  = $dir . wp_unique_filename( $dir, $temp_filename );
 
    $fp = @fopen( $temp_filename, 'x' );
    if ( ! $fp && is_writable( $dir ) && file_exists( $temp_filename ) ) {
        return carrot_bunnycdn_incoom_plugin_tempnam( $filename, $dir );
    }
    if ( $fp ) {
        fclose( $fp );
    }
 
    return $temp_filename;
}

/**
 * Woocommerce
 * Get all product downloadable
*/
function carrot_bunnycdn_incoom_plugin_get_products_downloadable() {
    
	$args = [
		'post_type' => 'product',
		'posts_per_page' => '-1',
		'post_status' => 'publish',
		'meta_query' => [
			[
				'key'     => '_downloadable',
				'value'   => 'yes',
				'compare' => '=',
			],
		],
	];

	$downloads = [];

	foreach ( carrot_bunnycdn_incoom_plugin_lazy_loop( new WP_Query($args) ) as $post ) {
		ini_set("memory_limit", -1);
		set_time_limit(0);
		$id = get_the_ID();
		$files = get_post_meta($id, '_downloadable_files', true);
		if(!empty($files) && is_array($files)){
			$downloads[] = [
				'post_id' => $id,
				'type' => 'woo',
				'files' => $files
			];
		}
		
	}

    return $downloads;
}

/**
 * Easy Digital Downloads
 * Get all download
*/
function carrot_bunnycdn_incoom_plugin_get_edd_downloadable() {
    
	$args = [
		'post_type' => 'download',
		'posts_per_page' => '-1',
		'post_status' => 'publish',
	];

	$downloads = [];

	foreach ( carrot_bunnycdn_incoom_plugin_lazy_loop( new WP_Query($args) ) as $post ) {
		ini_set("memory_limit", -1);
		set_time_limit(0);
		$id = get_the_ID();
		$files = get_post_meta($id, 'edd_download_files', true);
		if(!empty($files) && is_array($files)){
			$downloads[] = [
				'post_id' => $id,
				'type' => 'edd',
				'files' => $files
			];
		}
	}

    return $downloads;
}

/**
 * Get file name from download url
*/
function carrot_bunnycdn_incoom_plugin_get_file_name_from_download_url($value) {
	
	$fileUrl = $value;

	if (strpos($value, 'carrot_bunnycdn_incoom_plugin_storage') !== false) {
		$atts = shortcode_parse_atts($value);
		if(!empty($atts['key'])){
			$fileUrl = $atts['key'];
		}
	}

	return $fileUrl;
}

/**
 * Get post id from download url
*/
function carrot_bunnycdn_incoom_plugin_get_post_id_from_download_url($value) {
	
	$upload_dir = wp_upload_dir();
	$baseurl = $upload_dir['baseurl'];

	if (strpos($value, 'carrot_bunnycdn_incoom_plugin_storage') !== false) {
		$atts = shortcode_parse_atts($value);
		if(!empty($atts['key'])){

			$key = $atts['key'];

			if (strpos($value, $baseurl) !== false) {
				$key = carrot_bunnycdn_incoom_plugin_Utils::get_key_from_url($key);
				$key = ltrim($key, '/');
			}

			$bucket_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
			if (strpos($value, $bucket_url) !== false) {
				$key = str_replace($bucket_url, '', $atts['key']);
				$key = ltrim($key, '/');
			}

			$cname_url = carrot_bunnycdn_incoom_plugin_get_cname_url();
			if (strpos($value, $cname_url) !== false) {
				$key = str_replace($cname_url, '', $atts['key']);
				$key = ltrim($key, '/');
			}

			$post_id = carrot_bunnycdn_incoom_plugin_get_post_id_from_attached_file($key);
			if ($post_id) {
				return $post_id;
			}
		}
	}

	$url = carrot_bunnycdn_incoom_plugin_Utils::get_key_from_url($value);
	if($url){
		return carrot_bunnycdn_incoom_plugin_get_post_id($url);
	}

	return false;
}

/**
 * Change download link from server to cloud
*/
function carrot_bunnycdn_incoom_plugin_change_link_download($fileData) {

	$post_id = $fileData['post_id'];
	$files = $fileData['files'];
	$value = $files;

	switch ($fileData['type']) {
		case 'edd':
			$key = 'edd_download_files';

			foreach ($files as $k => $file) {
				$attachment_id = false;
				$shortcode = false;
					
				$value[$k]['name'] = basename(carrot_bunnycdn_incoom_plugin_get_file_name_from_download_url($file['file']));

				if(isset($file['attachment_id']) && $file['attachment_id'] > 0){
					$attachment_id = $file['attachment_id'];
				}else{
					$attachment_id = carrot_bunnycdn_incoom_plugin_get_post_id_from_download_url($file['file']);
				}

				if($attachment_id){
					$_wp_incoom_carrot_bunnycdn_s3_path = get_post_meta($attachment_id, '_wp_incoom_carrot_bunnycdn_s3_path', true);
					$wp_attached_file = get_post_meta($attachment_id, '_wp_attached_file', true);
					if($wp_attached_file){
						if ($_wp_incoom_carrot_bunnycdn_s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used') {
							$value[$k]['file'] = '[carrot_bunnycdn_incoom_plugin_storage key="'.$wp_attached_file.'" name="'.($file['name'] ? $file['name'] : '').'"]';
						}
					}

					try {
						$is_permission = get_post_meta($attachment_id, 'carrot_downloadable_file_permission', true);
						if($is_permission != 'yes'){
							list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $attachment_id );
							$result = $aws_s3_client->set_object_permission( $Bucket, $Region, $array_files, 'private' );
							if($result){
								update_post_meta($attachment_id, 'carrot_downloadable_file_permission', 'yes');
							}
						}
					} catch (Exception $e) {}
				}
			}

			break;
		case 'woo':
			$key = '_downloadable_files';

			if(!empty($files)){
				foreach ($files as $k => $file) {
					$shortcode = false;
					
					$value[$k]['name'] = basename(carrot_bunnycdn_incoom_plugin_get_file_name_from_download_url($file['file']));

					$attachment_id = carrot_bunnycdn_incoom_plugin_get_post_id_from_download_url($file['file']);
					if($attachment_id){
						$_wp_incoom_carrot_bunnycdn_s3_path = get_post_meta($attachment_id, '_wp_incoom_carrot_bunnycdn_s3_path', true);
						$wp_attached_file = get_post_meta($attachment_id, '_wp_attached_file', true);
						if($wp_attached_file){
							if ($_wp_incoom_carrot_bunnycdn_s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used') {
								$value[$k]['file'] = '[carrot_bunnycdn_incoom_plugin_storage key="'.$wp_attached_file.'" name="'.($file['name'] ? $file['name'] : '').'"]';
							}
						}

						try {
							$is_permission = get_post_meta($attachment_id, 'carrot_downloadable_file_permission', true);
							if($is_permission != 'yes'){
								list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $attachment_id );
								$aws_s3_client->set_object_permission( $Bucket, $Region, $array_files, 'private' );
								update_post_meta($attachment_id, 'carrot_downloadable_file_permission', 'yes');
							}
						} catch (Exception $e) {}	
					}	
				}
			}

			break;
		default:
			break;
	}

	update_post_meta( $post_id, $key, $value );
}


function carrot_bunnycdn_incoom_plugin_get_item_handler( $handler_type ) {

	$item_handlers = [];
	
	switch ( $handler_type ) {
		case 'upload':
			$item_handlers[ $handler_type ] = new carrot_bunnycdn_incoom_plugin_Upload_Handler();
			break;
		case 'download':
			$item_handlers[ $handler_type ] = new carrot_bunnycdn_incoom_plugin_Download_Handler();
			break;
		case 'remove-local':
			$item_handlers[ $handler_type ] = new carrot_bunnycdn_incoom_plugin_Remove_Local_Handler();
			break;
		case 'remove-provider':
			$item_handlers[ $handler_type ] = new carrot_bunnycdn_incoom_plugin_Remove_Provider_Handler();
			break;
		default:
			return null;
	}

	return $item_handlers[ $handler_type ];
}

function carrot_bunnycdn_incoom_plugin_get_object_prefix(){
	return get_option('incoom_carrot_bunnycdn_incoom_plugin_bucket_folder_main', '');
}



/**
 * Get all registered Item classes
 *
 * @return array
 */
function carrot_bunnycdn_incoom_plugin_get_source_type_classes() {
	return apply_filters('carrot_bunnycdn_incoom_plugin_source_type_classes', [
		'media-library' => 'carrot_bunnycdn_incoom_plugin_Library_Item',
	]);
}

function carrot_bunnycdn_incoom_plugin_get_source_type_name( $source_type = 'media-library' ) {
	$source_type_classes = carrot_bunnycdn_incoom_plugin_get_source_type_classes();

	if ( isset( $source_type_classes[ $source_type ] ) ) {
		return $source_type_classes[ $source_type ];
	}

	return false;
}

function carrot_bunnycdn_incoom_plugin_get_allowed_mime_types() {
	return array(
		// Image formats
		'jpg'                 => 'image/jpeg',
		'jpeg'                 => 'image/jpeg',
		'jpe'                 => 'image/jpeg',
		'gif'                          => 'image/gif',
		'png'                          => 'image/png',
		'bmp'                          => 'image/bmp',
		'tif'                     => 'image/tiff',
		'tiff'                     => 'image/tiff',
		'ico'                          => 'image/x-icon',

		// Video formats
		'asf'                      => 'video/x-ms-asf',
		'asx'                      => 'video/x-ms-asf',
		'wmv'                          => 'video/x-ms-wmv',
		'wmx'                          => 'video/x-ms-wmx',
		'wm'                           => 'video/x-ms-wm',
		'avi'                          => 'video/avi',
		'divx'                         => 'video/divx',
		'flv'                          => 'video/x-flv',
		'mov'                       => 'video/quicktime',
		'qt'                       => 'video/quicktime',
		'mpeg'                 => 'video/mpeg',
		'mpg'                 => 'video/mpeg',
		'mpe'                 => 'video/mpeg',
		'mp4'                      => 'video/mp4',
		'm4v'                      => 'video/mp4',
		'ogv'                          => 'video/ogg',
		'webm'                         => 'video/webm',
		'mkv'                          => 'video/x-matroska',
		
		// Text formats
		'txt'               => 'text/plain',
		'csv'                          => 'text/csv',
		'tsv'                          => 'text/tab-separated-values',
		'ics'                          => 'text/calendar',
		'rtx'                          => 'text/richtext',
		'css'                          => 'text/css',
		'htm'                     => 'text/html',
		'html'                     => 'text/html',
		
		// Audio formats
		'mp3'                  => 'audio/mpeg',
		'm4a'                  => 'audio/mpeg',
		'm4b'                  => 'audio/mpeg',
		'ra'                       => 'audio/x-realaudio',
		'ram'                       => 'audio/x-realaudio',
		'wav'                          => 'audio/wav',
		'ogg'                      => 'audio/ogg',
		'oga'                      => 'audio/ogg',
		'mid'                     => 'audio/midi',
		'midi'                     => 'audio/midi',
		'wma'                          => 'audio/x-ms-wma',
		'wax'                          => 'audio/x-ms-wax',
		'mka'                          => 'audio/x-matroska',
		
		// Misc application formats
		'rtf'                          => 'application/rtf',
		'js'                           => 'application/javascript',
		'pdf'                          => 'application/pdf',
		'swf'                          => 'application/x-shockwave-flash',
		'class'                        => 'application/java',
		'tar'                          => 'application/x-tar',
		'zip'                          => 'application/zip',
		'gz'                      => 'application/x-gzip',
		'gzip'                      => 'application/x-gzip',
		'rar'                          => 'application/rar',
		'7z'                           => 'application/x-7z-compressed',
		'exe'                          => 'application/x-msdownload',
		
		// MS Office formats
		'doc'                          => 'application/msword',
		'pot|pps|ppt'                  => 'application/vnd.ms-powerpoint',
		'wri'                          => 'application/vnd.ms-write',
		'xla'              => 'application/vnd.ms-excel',
		'xls'              => 'application/vnd.ms-excel',
		'xlt'              => 'application/vnd.ms-excel',
		'xlw'              => 'application/vnd.ms-excel',
		'mdb'                          => 'application/vnd.ms-access',
		'mpp'                          => 'application/vnd.ms-project',
		'docx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'docm'                         => 'application/vnd.ms-word.document.macroEnabled.12',
		'dotx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
		'dotm'                         => 'application/vnd.ms-word.template.macroEnabled.12',
		'xlsx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'xlsm'                         => 'application/vnd.ms-excel.sheet.macroEnabled.12',
		'xlsb'                         => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
		'xltx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
		'xltm'                         => 'application/vnd.ms-excel.template.macroEnabled.12',
		'xlam'                         => 'application/vnd.ms-excel.addin.macroEnabled.12',
		'pptx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'pptm'                         => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
		'ppsx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
		'ppsm'                         => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
		'potx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.template',
		'potm'                         => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
		'ppam'                         => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
		'sldx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
		'sldm'                         => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
		'onetoc' => 'application/onenote',
		'onetoc2' => 'application/onenote',
		'onetmp' => 'application/onenote',
		'onepkg' => 'application/onenote',
		
		// OpenOffice formats
		'odt'                          => 'application/vnd.oasis.opendocument.text',
		'odp'                          => 'application/vnd.oasis.opendocument.presentation',
		'ods'                          => 'application/vnd.oasis.opendocument.spreadsheet',
		'odg'                          => 'application/vnd.oasis.opendocument.graphics',
		'odc'                          => 'application/vnd.oasis.opendocument.chart',
		'odb'                          => 'application/vnd.oasis.opendocument.database',
		'odf'                          => 'application/vnd.oasis.opendocument.formula',
		
		// WordPerfect formats
		'wp'                       => 'application/wordperfect',
		'wpd'                       => 'application/wordperfect',
		
		// iWork formats
		'key'                          => 'application/vnd.apple.keynote',
		'numbers'                      => 'application/vnd.apple.numbers',
		'pages'                        => 'application/vnd.apple.pages',
	);
}

function carrot_bunnycdn_incoom_plugin_get_bare_upload_base_urls() {
	return carrot_bunnycdn_incoom_plugin_Utils::get_bare_upload_base_urls();
}

function carrot_bunnycdn_incoom_plugin_url_needs_replacing( $url ) {
	
	if ( str_replace( carrot_bunnycdn_incoom_plugin_get_bare_upload_base_urls(), '', $url ) === $url ) {
		// Remote URL, no replacement needed
		return false;
	}

	// Local URL, perform replacement
	return true;
}

/**
 * Get mime types to gzip
 *
 * @param bool $media_library
 *
 * @return array
 */
function carrot_bunnycdn_incoom_plugin_get_mime_types_to_gzip( $media_library = false ) {
	$mimes = apply_filters( 'incoom_carrot_bunnycdn_incoom_plugin_gzip_mime_types', array(
		'css'   => 'text/css',
		'eot'   => 'application/vnd.ms-fontobject',
		'html'  => 'text/html',
		'ico'   => 'image/x-icon',
		'js'    => 'application/javascript',
		'json'  => 'application/json',
		'otf'   => 'application/x-font-opentype',
		'rss'   => 'application/rss+xml',
		'svg'   => 'image/svg+xml',
		'ttf'   => 'application/x-font-ttf',
		'woff'  => 'application/font-woff',
		'woff2' => 'application/font-woff2',
		'xml'   => 'application/xml',
	), $media_library );

	return $mimes;
}

/**
 * Should gzip file
 *
 * @param string $file_path
 * @param string $type
 *
 * @return bool
 */
function carrot_bunnycdn_incoom_plugin_should_gzip_file( $file_path, $type ) {
	$gzip = get_option('incoom_carrot_bunnycdn_incoom_plugin_gzip', '');
	
	if(empty($gzip)){
		return false;
	}

	$mimes = carrot_bunnycdn_incoom_plugin_get_mime_types_to_gzip( true );

	if ( is_readable( $file_path ) && in_array($type, $mimes) ) {
		return true;
	}

	return false;
}

/**
 * Get bucket main folder
 *
 * @return string
 */
function carrot_bunnycdn_incoom_plugin_get_bucket_main_folder(){
	error_reporting(0);
	ini_set('display_errors', 0);
	
	$url = carrot_bunnycdn_incoom_plugin_get_object_prefix();
	if(empty($url)){
		return false;
	}
	
	if(substr($url, -1) == '/') {
		$url = substr($url, 0, -1);
	}

	return $url.'/';
}


/**
 * Build key with custom path
 *
 * @return string
 */
function carrot_bunnycdn_incoom_plugin_rebuild_key($Key, $custom_prefix=''){
	$prefix = carrot_bunnycdn_incoom_plugin_get_bucket_main_folder();
	if($prefix){
		$new_key = $Key;
		if(strpos($Key, $prefix) !== false){
			$new_key = str_replace($prefix, '', $Key);
		}
		$new_key = $prefix.$custom_prefix.$new_key;
		return $new_key;

	}else{
		$new_key = $Key;
		$new_key = $custom_prefix.$new_key;
		return $new_key;
	}
}

/**
 * Remove upload base url
 *
 * @return string
 */
function carrot_bunnycdn_incoom_plugin_remove_upload_base_url($url){
	$upload_dir = wp_upload_dir();
	$object_key = str_replace(carrot_bunnycdn_incoom_plugin_Utils::reduce_url($upload_dir['baseurl']) . '/', '', carrot_bunnycdn_incoom_plugin_Utils::reduce_url($url));
	return $object_key;
}


/**
 * Get all the blog IDs for the multisite network used for table prefixes
 *
 * @return false|array
 */
function carrot_bunnycdn_incoom_plugin_get_blog_ids() {
	if ( ! is_multisite() ) {
		return false;
	}

	$args = array(
		'limit'    => false, // Deprecated
		'number'   => false, // WordPress 4.6+
		'spam'     => 0,
		'deleted'  => 0,
		'archived' => 0,
	);

	if ( version_compare( $GLOBALS['wp_version'], '4.6', '>=' ) ) {
		$blogs = get_sites( $args );
	} else {
		$blogs = wp_get_sites( $args ); // phpcs:ignore
	}

	$blog_ids = array();

	foreach ( $blogs as $blog ) {
		$blog       = (array) $blog;
		$blog_ids[] = (int) $blog['blog_id'];
	}

	return $blog_ids;
}


/**
 * Get all the table prefixes for the blogs in the site. MS compatible
 *
 * @param array $exclude_blog_ids blog ids to exclude
 *
 * @return array associative array with blog ID as key, prefix as value
 */
function carrot_bunnycdn_incoom_plugin_get_all_blog_table_prefixes( $exclude_blog_ids = array() ) {
	global $wpdb;
	$prefix = $wpdb->prefix;

	$table_prefixes = array();

	if ( ! in_array( 1, $exclude_blog_ids ) ) {
		$table_prefixes[1] = $prefix;
	}

	if ( is_multisite() ) {
		$blog_ids = carrot_bunnycdn_incoom_plugin_get_blog_ids();
		foreach ( $blog_ids as $blog_id ) {
			if ( in_array( $blog_id, $exclude_blog_ids ) ) {
				continue;
			}
			$table_prefixes[ $blog_id ] = $wpdb->get_blog_prefix( $blog_id );
		}
	}

	return $table_prefixes;
}
/**
 * Get the total attachment and total offloaded/not offloaded attachment counts
 *
 * @param bool $skip_transient Whether to force database query and skip transient, default false
 * @param bool $force          Whether to force database query and skip static cache, implies $skip_transient, default false
 *
 * @return array
 */
function carrot_bunnycdn_incoom_plugin_get_media_counts( $skip_transient = false, $force = false ) {
	$keyTransient = carrot_bunnycdn_incoom_plugin_CACHE_KEY_MEDIA_COUNTS;
	
	$attachment_counts = array(
		'total'         => 0,
		'offloaded'     => 0,
		'not_offloaded' => 0,
		'local_removed' => 0,
		'not_local_removed' => 0,
		'copy_from_cloud' => 0,
		'not_copy_from_cloud' => 0,
		'cloud_removed' => 0,
		'not_cloud_removed' => 0,
		'counts_missing_source_ids' => 0
	);

	$cache_value = incoom_carrot_bunnycdn_incoom_plugin_get_cache_item($keyTransient);
	if ( !empty($cache_value) ) {
		$attachment_counts = $cache_value;
	}else{
		$table_prefixes = carrot_bunnycdn_incoom_plugin_get_all_blog_table_prefixes();

		$total          = 0;
		$offloaded      = 0;
		$not_offloaded  = 0;

		$local_removed = 0;
		$not_local_removed = 0;

		$copy_from_cloud = 0;
		$not_copy_from_cloud = 0;

		$cloud_removed = 0;
		$not_cloud_removed = 0;
		$counts_missing_source_ids = 0;

		foreach ( $table_prefixes as $blog_id => $table_prefix ) {
			incoom_carrot_bunnycdn_incoom_plugin_switch_to_blog( $blog_id );

			foreach ( carrot_bunnycdn_incoom_plugin_get_source_type_classes() as $source_type => $class ) {
				$counts_missing_source_ids += $class::verify_missing_source_ids(0, true);
				$counts        = $class::count_items( $skip_transient, $force );
				$total         += $counts['total'];
				$offloaded     += $counts['offloaded'];
				$not_offloaded += $counts['not_offloaded'];

				$local_removed += isset($counts['local_removed']) ? $counts['local_removed'] : 0;
				$not_local_removed += isset($counts['not_local_removed']) ? $counts['not_local_removed'] : 0;

				$copy_from_cloud += isset($counts['copy_from_cloud']) ? $counts['copy_from_cloud'] : 0;
				$not_copy_from_cloud += isset($counts['not_copy_from_cloud']) ? $counts['not_copy_from_cloud'] : 0;

				$cloud_removed += isset($counts['cloud_removed']) ? $counts['cloud_removed'] : 0;
				$not_cloud_removed += isset($counts['not_cloud_removed']) ? $counts['not_cloud_removed'] : 0;
			}

			incoom_carrot_bunnycdn_incoom_plugin_restore_current_blog();
		}

		$attachment_counts = array(
			'total'         => $total,
			'offloaded'     => $offloaded,
			'not_offloaded' => $not_offloaded,
			'local_removed' => $local_removed,
			'not_local_removed' => $not_local_removed,
			'copy_from_cloud' => $copy_from_cloud,
			'not_copy_from_cloud' => $not_copy_from_cloud,
			'cloud_removed' => $cloud_removed,
			'not_cloud_removed' => $not_cloud_removed,
			'counts_missing_source_ids' => $counts_missing_source_ids
		);

		incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($keyTransient, $attachment_counts);
	}

	return $attachment_counts;
}

function carrot_bunnycdn_incoom_plugin_get_sync_action_title($action){
	$actions = [
		'copy_files_to_bucket' => esc_html__('Copy all files from server to bucket', 'carrot-bunnycdn-incoom-plugin'),
		'remove_files_from_server' => esc_html__('Remove all files from server', 'carrot-bunnycdn-incoom-plugin'),
		'remove_files_from_bucket' => esc_html__('Remove all files from bucket', 'carrot-bunnycdn-incoom-plugin'),
		'download_files_from_bucket' => esc_html__('Download all files from bucket to server', 'carrot-bunnycdn-incoom-plugin')
	];
	return isset($actions[$action]) ? $actions[$action] : '';
}

function carrot_bunnycdn_incoom_plugin_copy_to_s3_by_source_id( $source_type, $source_id ) {
	
	$class      = carrot_bunnycdn_incoom_plugin_get_source_type_name( $source_type );
	$carrot_item = null;

	try {
		// Skip item if item already on provider.
		$carrot_item = $class::get_by_source_id( $source_id );
		if ( !empty( $carrot_item->id() ) ) {
			return false;
		}
	} catch (\Throwable $th) {}

	// Skip if we can't get a valid Item instance.
	try {
		$carrot_item = $class::create_from_source_id( $source_id );
		if ( is_wp_error( $carrot_item ) ) {
			return false;
		}else{
			$carrot_item->save();
		}
	} catch (\Throwable $th) {
		//throw $th;
	}

	if($carrot_item){
		$upload_handler = carrot_bunnycdn_incoom_plugin_get_item_handler( carrot_bunnycdn_incoom_plugin_Upload_Handler::get_item_handler_key_name() );
		$upload_result  = $upload_handler->handle( $carrot_item );
		
		if ( $upload_result ) {
			return true;
		}
	}

	return false;
}

/**
 * Count item for action: remove file local, remove file provider, download file from provider to local
*/
function carrot_bunnycdn_incoom_plugin_get_items_sync_action($meta_key, $meta_value, $source_type, $limit = 100, $count = false, $removed = true){
	global $wpdb;

	$table_name = $wpdb->get_blog_prefix() . carrot_bunnycdn_incoom_plugin_ITEMS_TABLE;

	if ( $count ) {
		$sql = 'SELECT COUNT(*)';
	} else {
		$sql = 'SELECT DISTINCT m.post_id';
	}

	$cond = '=';
	$checkItems = "";

	if(!$removed){
		$cond = '!=';
		$checkItems = "AND m.post_id IN (
			SELECT i.source_id
			FROM " . $table_name . " AS i
			WHERE i.source_type = '". $source_type ."'
			AND i.source_id = m.post_id
		)";
	}

	$sql .= "
		FROM " . $wpdb->postmeta . " AS m
		LEFT JOIN " . $wpdb->posts . " AS p ON m.post_id = p.ID AND p.`post_type` = 'attachment'
		WHERE m.meta_key = '".$meta_key."'
		AND m.meta_value ". $cond ." '".$meta_value."'
		".$checkItems."
	";

	if(!$count){
		if($limit > 0){
			$sql .= "LIMIT ". $limit;
		}
	}
	
	if ( $count ) {
		return (int) $wpdb->get_var( $sql );
	} else {
		return array_map( 'intval', $wpdb->get_col( $sql ) );
	}
}

/**
 * Get items Remove all files from server
*/
function carrot_bunnycdn_incoom_plugin_items_local_removed($source_type, $limit = 100, $count = false, $removed = true){
	return carrot_bunnycdn_incoom_plugin_get_items_sync_action(
		'_wp_incoom_carrot_bunnycdn_s3_wordpress_path', 
		'_wp_incoom_carrot_bunnycdn_s3_wordpress_path_not_in_used', 
		$source_type, 
		$limit, 
		$count, 
		$removed
	);
}

/**
 * Count items Remove all files from server
*/
function carrot_bunnycdn_incoom_plugin_count_items_local_removed($source_type){
	return carrot_bunnycdn_incoom_plugin_items_local_removed($source_type, 100, true);
}

/**
 * Get items download to server from cloud
*/
function carrot_bunnycdn_incoom_plugin_items_downloaded_to_server_from_cloud($source_type, $limit = 100, $count = false, $removed = true){
	return carrot_bunnycdn_incoom_plugin_get_items_sync_action(
		'_wp_incoom_carrot_bunnycdn_copy_to_server', 
		'1', 
		$source_type, 
		$limit, 
		$count, 
		$removed
	);
}

/**
 * Count items download to server from cloud
*/
function carrot_bunnycdn_incoom_plugin_count_items_downloaded_to_server_from_cloud($source_type = 'media-library'){
	return carrot_bunnycdn_incoom_plugin_items_downloaded_to_server_from_cloud($source_type, 100, true);
}

/**
 * Get items Remove all files from bucket
*/
function carrot_bunnycdn_incoom_plugin_items_remove_file_cloud($source_type, $limit = 100, $count = false, $removed = true){
	return carrot_bunnycdn_incoom_plugin_get_items_sync_action(
		'_wp_incoom_carrot_bunnycdn_s3_path', 
		'_wp_incoom_carrot_bunnycdn_s3_path_not_in_used', 
		$source_type, 
		$limit, 
		$count, 
		$removed
	);
}

/**
 * Count items Remove all files from bucket
*/
function carrot_bunnycdn_incoom_plugin_count_items_remove_file_cloud($source_type){
	return carrot_bunnycdn_incoom_plugin_items_remove_file_cloud($source_type, 100, true, true);
}

function carrot_bunnycdn_incoom_plugin_count_offloaded(){
	$media_count = carrot_bunnycdn_incoom_plugin_get_media_counts();
	$percentOffload = 0;
	$count = 0;
	$offloaded = (!empty($media_count['offloaded']) && $media_count['offloaded'] > 0) ? $media_count['offloaded'] : 0;
	$total = (!empty($media_count['total']) && $media_count['total'] > 0) ? $media_count['total'] : 0;
	if($media_count['counts_missing_source_ids'] > 0 && $offloaded > $total){
		$total += $media_count['counts_missing_source_ids'];
		$offloaded = $total - $media_count['counts_missing_source_ids'];
		$count = "{$offloaded}/{$total}";
		$percentOffload = round($offloaded / $total * 100);
	}else{
		if($total > 0){
			if($offloaded <= $total){
				$percentOffload = round($offloaded / $total * 100);
				$count = "{$offloaded}/{$total}";
			}else{
				$percentOffload = 100;
				$count = "{$total}/{$total}";
			}
		}
	}

	return [
		'total' 		=> $total,
		'count' 		=> $count,
		'percent' 		=> $percentOffload,
		'offloaded' 	=> $offloaded,
		'not_offloaded' => $media_count['not_offloaded']
	];
}

function carrot_bunnycdn_incoom_plugin_count_local_removed(){
	$media_count = carrot_bunnycdn_incoom_plugin_get_media_counts();
	$percentRemoved = 0;
	$count = 0;
	if($media_count['total'] > 0){
		if($media_count['local_removed'] <= $media_count['total']){
			$percentRemoved = round($media_count['local_removed'] / $media_count['total'] * 100);
			$count = "{$media_count['local_removed']}/{$media_count['total']}";
		}else{
			$percentRemoved = 100;
			$count = "{$media_count['total']}/{$media_count['total']}";
		}
	}

	return [
		'total' 			=> $media_count['total'],
		'count' 			=> $count,
		'percent' 			=> $percentRemoved,
		'local_removed' 	=> $media_count['local_removed'],
		'not_local_removed' => $media_count['not_local_removed']
	];
}

function carrot_bunnycdn_incoom_plugin_count_download_files_from_cloud(){
	$media_count = carrot_bunnycdn_incoom_plugin_get_media_counts();
	$percentRemoved = 0;
	$count = 0;
	if($media_count['total'] > 0){
		if($media_count['copy_from_cloud'] <= $media_count['total']){
			$percentRemoved = round($media_count['copy_from_cloud'] / $media_count['total'] * 100);
			$count = "{$media_count['copy_from_cloud']}/{$media_count['total']}";
		}else{
			$percentRemoved = 100;
			$count = "{$media_count['total']}/{$media_count['total']}";
		}
	}

	return [
		'total' 			=> $media_count['total'],
		'count' 			=> $count,
		'percent' 			=> $percentRemoved,
		'copy_from_cloud' 	=> $media_count['copy_from_cloud'],
		'not_copy_from_cloud' => $media_count['not_copy_from_cloud']
	];
}

function carrot_bunnycdn_incoom_plugin_count_remove_files_from_cloud(){
	$media_count = carrot_bunnycdn_incoom_plugin_get_media_counts();
	$percentRemoved = 0;
	$count = 0;
	
	if($media_count['offloaded'] == 0){
		$percentRemoved = 100;
		$count = "{$media_count['total']}/{$media_count['total']}";
	}else{
		if($media_count['total'] > 0){
			if($media_count['cloud_removed'] <= $media_count['total']){
				$percentRemoved = round($media_count['cloud_removed'] / $media_count['total'] * 100);
				$count = "{$media_count['cloud_removed']}/{$media_count['total']}";
			}else{
				$percentRemoved = 100;
				$count = "{$media_count['total']}/{$media_count['total']}";
			}
		}
	}

	return [
		'total' 			=> $media_count['total'],
		'count' 			=> $count,
		'percent' 			=> $percentRemoved,
		'cloud_removed' 	=> $media_count['cloud_removed'],
		'not_cloud_removed' => $media_count['not_cloud_removed']
	];
}

