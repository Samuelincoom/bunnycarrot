<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Shortcodes
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.5
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */
class carrot_bunnycdn_incoom_plugin_Shortcodes {

	function __construct() {
		self::init();	
	}

	public static function init() {

		$shortcodes = array(
			'carrot_bunnycdn_incoom_plugin_storage' => __CLASS__ . '::Show_Presigned_URL'
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}

		shortcode_atts( array( 'key' => '', 'name' => '' ), array(), 'carrot_bunnycdn_incoom_plugin_storage' );

	}

	public static function Show_Presigned_URL( $atts ) {

		$key  = isset( $atts['key'] ) ? $atts['key'] : '';
		$Name = isset( $atts['name'] ) ? $atts['name'] : '';
		$download_url = '';

		$bucket_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
		if (strpos($key, $bucket_url) !== false) {
			$key = str_replace($bucket_url, '', $key);
			$key = ltrim($key, '/');
		}

		$cname_url = carrot_bunnycdn_incoom_plugin_get_cname_url();
		if (strpos($key, $cname_url) !== false) {
			$key = str_replace($cname_url, '', $key);
			$key = ltrim($key, '/');
		}

		list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();
		$url = $aws_s3_client->Get_Presigned_URL($Bucket, $Region, $key);
		if ( $url ) {
			$download_url = $url;
		}

		return $download_url;

	}
	
}
