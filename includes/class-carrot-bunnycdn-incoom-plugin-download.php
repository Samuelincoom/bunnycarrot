<?php

/**
 * Downloads Integration
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.2
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Download {

	function __construct() {
		$this->compatibility_init();
	}

	public function compatibility_init() {}

	public function get_path_from_url($url){
		$domain = parse_url($url);
		$url = isset($domain['path']) ? $domain['path'] : '';
		if(!empty($url)){
			return ltrim($url, '/');
		}
		return false;
	}

	public function get_key_from_url($old_url){
		return carrot_bunnycdn_incoom_plugin_Utils::get_key_from_url($old_url);
	}

	public function get_post_id($value){
		return carrot_bunnycdn_incoom_plugin_get_post_id_from_download_url($value);
	}
}
