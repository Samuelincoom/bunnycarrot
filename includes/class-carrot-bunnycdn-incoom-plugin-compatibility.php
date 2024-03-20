<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Plugin Compatibility
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */

class carrot_bunnycdn_incoom_plugin_Compatibility {

	/**
	 * @var array
	 */
	private $removed_files = array();

	function __construct() {
		$this->compatibility_init();
	}

	/**
	 * Register the compatibility hooks for the plugin.
	 */
	function compatibility_init() {
		if(incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
			/*
			 * WP_Customize_Control
			 * /wp-includes/class-wp-customize_control.php
			 */
			add_filter( 'attachment_url_to_postid', array( $this, 'customizer_background_image' ), 10, 2 );

			/*
			 * Responsive Images WP 4.4
			 */
			add_filter( 'wp_calculate_image_srcset', array( $this, 'wp_calculate_image_srcset' ), 10, 5 );
			add_filter( 'wp_calculate_image_srcset_meta', array( $this, 'wp_calculate_image_srcset_meta' ), 10, 4 );
		}

		/*
		* @since      1.0.5
		*/
		$disable_emojis = get_option('incoom_carrot_bunnycdn_incoom_plugin_emoji', '');
		if($disable_emojis){
			add_action( 'init', array( $this, 'disable_emojis' ) );
		}

		$minify_html = get_option('incoom_carrot_bunnycdn_incoom_plugin_minify_html', '');
		if($minify_html){
			add_action('get_header', array( $this, 'html_compression_start' ));
		}

		/*
		 * Legacy filter
		 * 'carrot_bunnycdn_incoom_plugin_get_attached_file_copy_back_to_local'
		 */
		add_filter( 'carrot_bunnycdn_incoom_plugin_get_attached_file', array( $this, 'legacy_copy_back_to_local' ), 10, 4 );

		// /*
		//  * WP_Image_Editor
		//  * /wp-includes/class-wp-image-editor.php
		//  */
		add_action( 'carrot_bunnycdn_incoom_plugin_pre_upload_attachment', array( $this, 'image_editor_remove_files' ), 10, 3 );
		add_filter( 'carrot_bunnycdn_incoom_plugin_get_attached_file', array( $this, 'image_editor_download_file' ), 10, 4 );
		
		add_filter( 'carrot_bunnycdn_incoom_plugin_get_attached_file', array( $this, 'customizer_crop_download_file' ), 10, 4 );

		add_filter( 'incoom_carrot_bunnycdn_incoom_plugin_upload_attachment_local_files_to_remove', array( $this, 'customizer_crop_remove_original_image' ), 10, 3 );
		add_filter( 'wp_unique_filename', array( $this, 'customizer_crop_unique_filename' ), 10, 3 );


		/**
		 * Regenerate Thumbnails v3+ and other REST-API using plugins that need a local file.
		 */
		add_filter( 'rest_dispatch_request', array( $this, 'rest_dispatch_request_copy_back_to_local' ), 10, 4 );

		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'attachment_id_class' ), 10, 2 );

		/*
		 * WP-CLI Compatibility
		 */
		if ( defined( 'WP_CLI' ) && class_exists( 'WP_CLI' ) ) {
			WP_CLI::add_hook( 'before_invoke:media regenerate', array( $this, 'enable_get_attached_file_copy_back_to_local' ) );
		}
	}

	function attachment_id_class( $attr, $attachment ) {

		$class_attr = isset( $attr['class'] ) ? $attr['class'] : '';
		$has_class = preg_match(
			'/wp\-image\-[0-9]+/',
			$class_attr,
			$matches
		);
	
		// Check if the image is missing the class
		if ( !$has_class ) {
			$class_attr .= sprintf( ' wp-image-%d', $attachment->ID );
			// Use ltrim to to remove leading space if necessary
			$attr['class'] = ltrim( $class_attr );
	
		}
		
		return $attr;
	}

	function html_compression_finish($html){
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-html-compression.php' );
	    return new carrot_bunnycdn_incoom_plugin_HTML_Compression($html);
	}

	function html_compression_start(){
	    ob_start(array( $this, 'html_compression_finish' ));
	}

	public function disable_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
		add_filter( 'wp_resource_hints', array( $this, 'disable_emojis_remove_dns_prefetch' ), 10, 2 );
	}

	/**
	 * Filter function used to remove the tinymce emoji plugin.
	 * 
	 * @param array $plugins 
	 * @return array Difference betwen the two arrays
	 */
	public function disable_emojis_tinymce( $plugins ) {
	 	if ( is_array( $plugins ) ) {
	 		return array_diff( $plugins, array( 'wpemoji' ) );
	 	} else {
	 		return array();
	 	}
	}

	/**
	 * Remove emoji CDN hostname from DNS prefetching hints.
	 *
	 * @param array $urls URLs to print for resource hints.
	 * @param string $relation_type The relation type the URLs are printed for.
	 * @return array Difference betwen the two arrays.
	 */
	public function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
	 	if ( 'dns-prefetch' == $relation_type ) {
	 		/** This filter is documented in wp-includes/formatting.php */
	 		$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
			$urls = array_diff( $urls, array( $emoji_svg_url ) );
	 	}
		return $urls;
	}

	/**
	 * Helper function for filtering super globals. Easily testable.
	 *
	 * @param string $variable
	 * @param int    $type
	 * @param int    $filter
	 * @param mixed  $options
	 *
	 * @return mixed
	 */
	public function filter_input( $variable, $type = INPUT_GET, $filter = FILTER_DEFAULT, $options = array() ) {
		return filter_input( $type, $variable, $filter, $options );
	}

	/**
	 * Check the current request is a specific one based on action and
	 * optional context
	 *
	 * @param string            $action_key
	 * @param bool              $ajax
	 * @param null|string|array $context_key
	 *
	 * @return bool
	 */
	function maybe_process_on_action( $action_key, $ajax, $context_key = null ) {
		if ( $ajax !== $this->is_ajax() ) {
			return false;
		}

		$var_type = 'GET';

		if ( isset( $_GET['action'] ) ) {
			$action = $this->filter_input( 'action' );
		} else if ( isset( $_POST['action'] ) ) {
			$var_type = 'POST';
			$action   = $this->filter_input( 'action', INPUT_POST );
		} else {
			return false;
		}

		$context_check = true;
		if ( ! is_null( $context_key ) ) {
			$global  = constant( 'INPUT_' . $var_type );
			$context = $this->filter_input( 'context', $global );

			if ( is_array( $context_key ) ) {
				$context_check = in_array( $context, $context_key );
			} else {
				$context_check = ( $context_key === $context );
			}
		}

		return ( $action_key === sanitize_key( $action ) && $context_check );
	}

	/**
	 * Show the correct background image in the customizer
	 *
	 * @param int|null $post_id
	 * @param string   $url
	 *
	 * @return int|null
	 */
	function customizer_background_image( $post_id, $url ) {
		if ( ! is_null( $post_id ) ) {
			return $post_id;
		}
		$url = parse_url( $url );

		if ( ! isset( $url['path'] ) ) {
			return $post_id; // URL path can't be determined
		}

		$key1    = ltrim( $url['path'], '/' );
		$length1 = strlen( $key1 );

		// URLs may contain the bucket name within the path, therefore we must
		// also perform the search with the first path segment removed
		$parts = explode( '/', $key1 );
		unset( $parts[0] );

		$key2    = implode( '/', $parts );
		$length2 = strlen( $key2 );

		global $wpdb;
		$sql = $wpdb->prepare( "
			SELECT `post_id`
			FROM `{$wpdb->prefix}postmeta`
			WHERE `{$wpdb->prefix}postmeta`.`meta_key` = '_incoom_carrot_bunnycdn_amazonS3_info'
			AND ( `{$wpdb->prefix}postmeta`.`meta_value` LIKE %s
			OR `{$wpdb->prefix}postmeta`.`meta_value` LIKE %s )
		",
			"%s:3:\"key\";s:{$length1}:\"{$key1}\";%",
			"%s:3:\"key\";s:{$length2}:\"{$key2}\";%"
		);

		if ( $id = $wpdb->get_var( $sql ) ) {
			return $id;
		}

		return $post_id; // No attachment found on S3
	}

	/**
	 * Replace local URLs with S3 ones for srcset image sources
	 *
	 * @param array  $sources
	 * @param array  $size_array
	 * @param string $image_src
	 * @param array  $image_meta
	 * @param int    $attachment_id
	 *
	 * @return array
	 */
	public function wp_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		$s3_path = get_post_meta( $attachment_id, '_wp_incoom_carrot_bunnycdn_s3_path', true);
		$new_url = false;
		
		if ( $s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' && $s3_path != null ) {
			$new_url = $s3_path;
		}
		
		if ( is_wp_error( $new_url ) || false === $new_url ) {
			return $sources;
		}
				
		if ( ! is_array( $sources ) ) {
			// Sources corrupt
			return $sources;
		}

		if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_is_attachment_served_by_provider( $attachment_id ) ) ) {
			// Attachment not uploaded to S3, abort
			return $sources;
		}

		$enable_webp = carrot_bunnycdn_incoom_plugin_can_use_webp();
		
		foreach ( $sources as $width => $source ) {
			$filename     = wp_basename( $source['url'] );
			$size         = $this->find_image_size_from_width( $image_meta['sizes'], $width, $filename );
			$provider_url = carrot_bunnycdn_incoom_plugin_get_attachment_provider_url( $attachment_id, $provider_object, null, $size, $image_meta );
			if ( false === $provider_url || is_wp_error( $provider_url ) ) {
				// Skip URLs not offloaded to S3
				continue;
			}
			
			if($enable_webp){
				$key = carrot_bunnycdn_incoom_plugin_Utils::get_key_from_url($provider_url, false);
				$webp_url = carrot_bunnycdn_incoom_plugin_Utils::get_webp_url_with_key($attachment_id, $key);
				if(!empty($webp_url)){
					$provider_url = $webp_url;
				}
			}

			$provider_url = carrot_bunnycdn_incoom_plugin_get_real_url($provider_url);
			$cloudfront_url = carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url($provider_url);

			$sources[ $width ]['url'] = $cloudfront_url;
			
		}
		
		return $sources;
	}

	/**
	 * Helper function to find size name from width and filename
	 *
	 * @param array  $sizes
	 * @param string $width
	 * @param string $filename
	 *
	 * @return null|string
	 */
	protected function find_image_size_from_width( $sizes, $width, $filename ) {
		foreach ( $sizes as $name => $size ) {
			if ( $width === absint( $size['width'] ) && $size['file'] === $filename ) {
				return $name;
			}
		}

		return null;
	}

	/**
	 * Alter the image meta data to add srcset support for object versioned S3 URLs
	 *
	 * @param array  $image_meta
	 * @param array  $size_array
	 * @param string $image_src
	 * @param int    $attachment_id
	 *
	 * @return array
	 */
	public function wp_calculate_image_srcset_meta( $image_meta, $size_array, $image_src, $attachment_id ) {
		$s3_path = get_post_meta( $attachment_id, '_wp_incoom_carrot_bunnycdn_s3_path', true);
		$new_url = false;
		
		if ( $s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' && $s3_path != null ) {
			$new_url = $s3_path;
		}
		
		if ( is_wp_error( $new_url ) || false === $new_url ) {
			return $image_meta;
		}
		
		if ( empty( $image_meta['file'] ) ) {
			// Corrupt `_wp_attachment_metadata`
			return $image_meta;
		}

		if ( false !== strpos( $image_src, $image_meta['file'] ) ) {
			// Path matches URL, no need to change
			return $image_meta;
		}

		if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_is_attachment_served_by_provider( $attachment_id ) ) ) {
			// Attachment not uploaded to S3, abort
			return $image_meta;
		}

		$image_basename = wp_basename( $image_meta['file'] );

		if ( false === strpos( $provider_object['key'], $image_basename ) ) {
			// Not the correct attachment, abort
			return $image_meta;
		}

		// Strip the meta file prefix so the just the filename will always match
		// the S3 URL regardless of different prefixes for the offloaded file.
		// Also ensure filename is encoded the same way as URL.
		$image_meta['file'] = rawurlencode( $image_basename );

		// Ensure each size filename is encoded the same way as URL.
		if ( ! empty( $image_meta['sizes'] ) ) {
			$image_meta['sizes'] = array_map( function ( $size ) {
				$size['file'] = rawurlencode( $size['file'] );

				return $size;
			}, $image_meta['sizes'] );
		}

		return $image_meta;
	}

	/**
	 * Allow any process to trigger the copy back to local with
	 * the filter 'carrot_bunnycdn_incoom_plugin_get_attached_file_copy_back_to_local'
	 *
	 * @param string $url
	 * @param string $file
	 * @param int    $attachment_id
	 * @param array  $provider_object
	 *
	 * @return string
	 */
	function legacy_copy_back_to_local( $url, $file, $attachment_id, $provider_object ) {
		$copy_back_to_local = apply_filters( 'carrot_bunnycdn_incoom_plugin_get_attached_file_copy_back_to_local', false, $file, $attachment_id, $provider_object );
		if ( false === $copy_back_to_local ) {
			// Not copying back file
			return $url;
		}

		if ( ( $file = $this->copy_provider_file_to_server( $file, $attachment_id, $provider_object ) ) ) {
			// Return the file if successfully downloaded from S3
			return $file;
		};

		// Return S3 URL as a fallback
		return $url;
	}

	/**
	 * Download a file from S3 if the file does not exist locally and places it where
	 * the attachment's file should be.
	 *
	 * @param array  $provider_object
	 * @param string $file
	 *
	 * @return string|bool File if downloaded, false on failure
	 */
	public function copy_provider_file_to_server( $file, $post_id, $provider_object ) {

		$File_Name = get_post_meta( $post_id, '_wp_attached_file', true );
		$upload_dir = wp_upload_dir();
		$basedir = $upload_dir['basedir'];
		$Path_To_File = $basedir . "/" . $File_Name;
		if(file_exists( $Path_To_File )){
			return $Path_To_File;
		}

		try {
			list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $post_id );

			$file = $aws_s3_client->download_original_file( $Bucket, $Region, $array_files, $basedir_absolute );
		} catch ( Exception $e ) {
			return false;
		}

		return $file;
	}

	/**
	 * Enables copying missing local files back to the server when `get_attached_file` filter is called.
	 */
	public function enable_get_attached_file_copy_back_to_local() {
		add_filter( 'carrot_bunnycdn_incoom_plugin_get_attached_file_copy_back_to_local', '__return_true' );
	}

	/**
	 * Filters the REST dispatch request to determine whether route needs compatibility actions.
	 *
	 * @param bool            $dispatch_result Dispatch result, will be used if not empty.
	 * @param WP_REST_Request $request         Request used to generate the response.
	 * @param string          $route           Route matched for the request.
	 * @param array           $handler         Route handler used for the request.
	 *
	 * @return bool
	 */
	public function rest_dispatch_request_copy_back_to_local( $dispatch_result, $request, $route, $handler ) {
		$routes = array(
			'/regenerate-thumbnails/v\d+/regenerate/',
			'/regenerate-thumbnails/v\d+/attachmentinfo/'
		);

		$routes = apply_filters( 'eopard_offload_media_rest_api_enable_get_attached_file_copy_back_to_local', $routes );
		$routes = is_array( $routes ) ? $routes : (array) $routes;
		if ( ! empty( $routes ) ) {
			foreach ( $routes as $match_route ) {
				if ( preg_match( '@' . $match_route . '@i', $route ) ) {
					$this->enable_get_attached_file_copy_back_to_local();
					break;
				}
			}
		}

		return $dispatch_result;
	}

	/**
	 * Is this an AJAX process?
	 *
	 * @return bool
	 */
	function is_ajax() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		return false;
	}

	/**
	 * Allow the WordPress Image Editor to edit files that have been copied to S3
	 * but removed from the local server, by copying them back temporarily
	 *
	 * @param string $url
	 * @param string $file
	 * @param int    $attachment_id
	 * @param array  $provider_object
	 *
	 * @return string
	 */
	function image_editor_download_file( $url, $file, $attachment_id, $provider_object ) {
		if ( ! $this->is_ajax() ) {
			return $url;
		}

		// When the image-editor restores the original it requests the edited image,
		// but we actually need to copy back the original image at this point
		// for the restore to be successful and edited images to be deleted from S3
		// via image_editor_remove_files()
		if ( isset( $_POST['do'] ) && 'restore' == $_POST['do'] ) {
			$backup_sizes      = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
			$original_filename = $backup_sizes['full-orig']['file'];

			$orig_provider        = $provider_object;
			$orig_provider['key'] = dirname( $provider_object['key'] ) . '/' . $original_filename;
			$orig_file            = dirname( $file ) . '/' . $original_filename;

			// Copy the original file back to the server for the restore process
			$this->copy_provider_file_to_server( $orig_file, $attachment_id, $orig_provider );

			// Copy the edited file back to the server as well, it will be cleaned up later
			if ( $provider_file = $this->copy_provider_file_to_server( $file, $attachment_id, $provider_object ) ) {
				// Return the file if successfully downloaded from S3
				return $provider_file;
			};
		}

		$action = filter_input( INPUT_GET, 'action' ) ?: filter_input( INPUT_POST, 'action' );

		if ( in_array( $action, array( 'image-editor', 'imgedit-preview' ) ) ) { // input var okay
			foreach ( debug_backtrace() as $caller ) {
				if ( isset( $caller['function'] ) && '_load_image_to_edit_path' == $caller['function'] ) {
					// check this has been called by '_load_image_to_edit_path' so as only to copy back once
					if ( $provider_file = $this->copy_provider_file_to_server( $file, $attachment_id, $provider_object ) ) {
						// Return the file if successfully downloaded from S3
						return $provider_file;
					};
				}
			}
		}

		return $url;
	}

	/**
	 * Allow the WordPress Image Editor to remove edited version of images
	 * if the original image is being restored and 'IMAGE_EDIT_OVERWRITE' is set
	 *
	 * @param bool  $pre
	 * @param int   $post_id
	 * @param array $data
	 *
	 * @return bool
	 */
	public function image_editor_remove_files( $pre, $post_id, $data ) {
		if ( ! isset( $_POST['do'] ) || 'restore' !== $_POST['do'] ) {
			return $pre;
		}

		if ( ! defined( 'IMAGE_EDIT_OVERWRITE' ) || ! IMAGE_EDIT_OVERWRITE ) {
			return $pre;
		}

		$provider_object = carrot_bunnycdn_incoom_plugin_get_attachment_provider_info( $post_id );
		$this->remove_edited_image_files( $post_id );

		// Update object key with original filename
		$restored_filename      = wp_basename( $data['file'] );
		$old_filename           = wp_basename( $provider_object['key'] );
		$provider_object['key'] = str_replace( $old_filename, $restored_filename, $provider_object['key'] );
		update_post_meta( $post_id, '_incoom_carrot_bunnycdn_amazonS3_info', $provider_object );

		return true;
	}

	/**
	 * Remove edited image files from S3.
	 *
	 * @param int   $attachment_id
	 */
	protected function remove_edited_image_files( $attachment_id ) {
		try {
			list( $aws_s3_client, $Bucket, $Region, $array_files, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_aws_array_media_actions_function( $attachment_id );

			$file = $aws_s3_client->deleteObject_nou( $bucket, $region, $keys );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Generic check for Customizer crop actions
	 *
	 * @return bool
	 */
	protected function is_customizer_crop_action() {
		$header_crop = $this->maybe_process_on_action( 'custom-header-crop', true );

		$context    = array( 'site-icon', 'custom_logo' );
		$image_crop = $this->maybe_process_on_action( 'crop-image', true, $context );

		if ( ! $header_crop && ! $image_crop ) {
			// Not doing a Customizer action
			return false;
		}

		return true;
	}

	/**
	 * Allow the WordPress Customizer to crop images that have been copied to S3
	 * but removed from the local server, by copying them back temporarily
	 *
	 * @param string $url
	 * @param string $file
	 * @param int    $attachment_id
	 * @param array  $provider_object
	 *
	 * @return string
	 */
	public function customizer_crop_download_file( $url, $file, $attachment_id, $provider_object ) {
		if ( false === $this->is_customizer_crop_action() ) {
			return $url;
		}

		if ( ( $file = $this->copy_provider_file_to_server( $file, $attachment_id, $provider_object ) ) ) {
			// Return the file if successfully downloaded from S3
			return $file;
		};

		return $url;
	}

	/**
	 * Get the file path of the original image file before an update
	 *
	 * @param int    $post_id
	 * @param string $file_path
	 *
	 * @return bool|string
	 */
	function get_original_image_file( $post_id, $file_path ) {
		// remove original main image after edit
		$meta          = get_post_meta( $post_id, '_wp_attachment_metadata', true );
		$original_file = trailingslashit( dirname( $file_path ) ) . wp_basename( $meta['file'] );
		if ( file_exists( $original_file ) ) {
			return $original_file;
		}

		return false;
	}

	/**
	 * Allow the WordPress Image Editor to remove the main image file after it has been copied
	 * back from S3 after it has done the edit.
	 *
	 * @param array  $files
	 * @param int    $post_id
	 * @param string $file_path
	 *
	 * @return array
	 */
	function customizer_crop_remove_original_image( $files, $post_id, $file_path ) {
		if ( false === $this->is_customizer_crop_action() ) {
			return $files;
		}

		// remove original main image after edit
		if ( ( $original_file = $this->get_original_image_file( $_POST['id'], $file_path ) ) ) {
			$files[] = $original_file;
		}

		return $files;
	}

	/**
	 * Filters the result when generating a unique file name for a customizer crop.
	 *
	 * @param string $filename Unique file name.
	 * @param string $ext      File extension, eg. ".png".
	 * @param string $dir      Directory path.
	 *
	 * @return string
	 */
	public function customizer_crop_unique_filename( $filename, $ext, $dir ) {
		if ( false === $this->is_customizer_crop_action() ) {
			return $filename;
		}

		// Get parent Post ID for cropped image.
		$post_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

		$filename = $this->filter_unique_filename( $filename, $ext, $dir, $post_id );

		return $filename;
	}

	/**
	 * Create unique names for file to be uploaded to AWS.
	 * This only applies when the remove local file option is enabled.
	 *
	 * @param string $filename Unique file name.
	 * @param string $ext      File extension, eg. ".png".
	 * @param string $dir      Directory path.
	 * @param int    $post_id  Attachment's parent Post ID.
	 *
	 * @return string
	 */
	public function filter_unique_filename( $filename, $ext, $dir, $post_id = null ) {
		// sanitize the file name before we begin processing
		$filename = sanitize_file_name( $filename );
		$ext      = strtolower( $ext );
		$name     = wp_basename( $filename, $ext );

		// Edge case: if file is named '.ext', treat as an empty name.
		if ( $name === $ext ) {
			$name = '';
		}

		// Rebuild filename with lowercase extension as provider will have converted extension on upload.
		$filename = $name . $ext;
		$time     = current_time( 'mysql' );

		// Get time if uploaded in post screen.
		if ( ! empty( $post_id ) ) {
			$time = $this->get_post_time( $post_id );
		}

		if ( ! $this->does_file_exist( $filename, $time ) ) {
			// File doesn't exist locally or on provider, return it.
			return $filename;
		}

		$filename = $this->generate_unique_filename( $name, $ext, $time );

		return $filename;
	}

	/**
	 * Get post time
	 *
	 * @param int $post_id
	 *
	 * @return string
	 */
	function get_post_time( $post_id ) {
		$time = current_time( 'mysql' );

		if ( ! $post = get_post( $post_id ) ) {
			return $time;
		}

		if ( substr( $post->post_date, 0, 4 ) > 0 ) {
			$time = $post->post_date;
		}

		return $time;
	}

	/**
	 * Does file exist
	 *
	 * @param string $filename
	 * @param string $time
	 *
	 * @return bool
	 */
	function does_file_exist( $filename, $time ) {
		if ( $this->does_file_exist_local( $filename, $time ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Does file exist local
	 *
	 * @param string $filename
	 * @param string $time
	 *
	 * @return bool
	 */
	function does_file_exist_local( $filename, $time ) {
		global $wpdb;

		$path = wp_upload_dir( $time );
		$path = ltrim( $path['subdir'], '/' );

		if ( '' !== $path ) {
			$path = trailingslashit( $path );
		}
		$file = $path . $filename;

		$sql = $wpdb->prepare( "
			SELECT COUNT(*)
			FROM $wpdb->postmeta
			WHERE meta_key = %s
			AND meta_value = %s
		", '_wp_attached_file', $file );

		return (bool) $wpdb->get_var( $sql );
	}

	/**
	 * Generate unique filename
	 *
	 * @param string $name
	 * @param string $ext
	 * @param string $time
	 *
	 * @return string
	 */
	function generate_unique_filename( $name, $ext, $time ) {
		$count    = 1;
		$filename = $name . $count . $ext;

		while ( $this->does_file_exist( $filename, $time ) ) {
			$count++;
			$filename = $name . $count . $ext;
		}

		return $filename;
	}
}