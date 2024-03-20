<?php
if (!defined('ABSPATH')) {exit;}
/**
 * change static asset links
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.4
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */

class carrot_bunnycdn_incoom_plugin_Assets {

	function __construct() {
		$this->compatibility_init();
	}

	public function compatibility_init() {
		add_filter( 'style_loader_src',  array($this, 'remove_ver_css_js'), 10000000, 1 );
		add_filter( 'script_loader_src', array($this, 'remove_ver_css_js'), 10000000, 1 );

		add_action('wp_head', array($this, 'wp_head'), 10000000);
		add_action('wp_enqueue_scripts', array($this, 'scan_scripts'), 10000000);
    	add_action('wp_print_styles', array($this, 'scan_css'), 10000000);
	}

	public function remove_ver_css_js( $src ){
    	$parts = explode( '?ver', $src );
    	return $parts[0];
	}

	private function get_script_file_path($src){
		if (strpos($src, 'http') !== false) {

            $site_url = site_url();

            if (strpos($src, $site_url) !== false) {
                $css_file_path = str_replace($site_url, '', $src);
            } else {
                $css_file_path = $src;
            }

            $css_file_path = ltrim($css_file_path, '/');
        } else {
            $css_file_path = ltrim($src, '/');
        }

        return $css_file_path;
	}

	public function get_prefix(){
		$prefix = get_option('incoom_carrot_bunnycdn_incoom_plugin_pull_assets_path', 'pull-assets/');

		if(substr($prefix, -1) == '/') {
            $prefix = substr($prefix, 0, -1);
        }
        return $prefix.'/';
	}

	public function build_tmp_url($key){
		if (filter_var($key, FILTER_VALIDATE_URL) === FALSE) {
			$url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
			$aws_s3_client = carrot_bunnycdn_incoom_plugin_whichtype();

			$bucketFolder = $aws_s3_client->getBucketMainFolder();
			if($bucketFolder){
				$folder = $bucketFolder;
			}
			
			$prefix = $this->get_prefix();
			return esc_url($url.'/'.$folder.$prefix.$key);
		}
		return $key;
	}

	public function valid_uploaded($path){
		$uploaded = get_option('incoom_carrot_bunnycdn_incoom_plugin_uploaded_assets', '');
        if(!is_array($uploaded)){
        	$uploaded = array();
        }

        if(empty($uploaded)){
        	return false;
        }

		$status = false;

		foreach ($uploaded as $uploaded_url) {
			if(strpos($uploaded_url, $path) !== false){
				$status = true;
				break;
			}
		}
		return $status;
	}

	public function wp_head(){
		$enable_assets = get_option('incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox', '');
		if ($enable_assets) {
			global $wp_styles;

	        $wp_styles->all_deps($wp_styles->queue);
	        $handles = $wp_styles->to_do;

	        global $wp_scripts;
	    	$wp_scripts->all_deps($wp_scripts->queue);

	        if(isset($_GET['scan_assets'])){
		        $scripts = get_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_assets');
		        if(!is_array($scripts)){
		        	$scripts = array();
		        }

				foreach ($handles as $handle) {
					$src = isset($wp_styles->registered[$handle]->src) ? $wp_styles->registered[$handle]->src : '';
					if(!empty($src)){
					    $css_file_path = $this->get_script_file_path($src);
			            if (file_exists($css_file_path) && !in_array($css_file_path, $scripts)) {
			                $scripts[] = $css_file_path;
			            }
			        }    
				}

			
		    	foreach ($wp_scripts->to_do as $handle) {
		    		$src = isset($wp_scripts->registered[$handle]->src) ? $wp_scripts->registered[$handle]->src : '';
		    		if(!empty($src)){
		    			$js_file_path = $this->get_script_file_path($src);
		    		    if (file_exists($js_file_path) && !in_array($js_file_path, $scripts)) {
					        $scripts[] = $js_file_path;
					    }
		    		}
		    	}


				update_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_assets', array_unique($scripts));
			}else{

				$uploaded = get_option('incoom_carrot_bunnycdn_incoom_plugin_uploaded_assets', '');
		        if(!is_array($uploaded)){
		        	$uploaded = array();
		        }

				$allowdedCss = get_option('incoom_carrot_bunnycdn_incoom_plugin_offload_css');
				if($allowdedCss == 'on'){
					foreach ($handles as $handle) {
						$src = isset($wp_styles->registered[$handle]->src) ? $wp_styles->registered[$handle]->src : '';
						if(!empty($src)){
							$css_file_path = $this->get_script_file_path($src);
							$url = $this->build_tmp_url($css_file_path);
							if(!empty($css_file_path) && $this->valid_uploaded($css_file_path)){
								$wp_styles->registered[$handle]->src = carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url($url, '', true);
							}
						}
					}
				}	

				$allowdedJs = get_option('incoom_carrot_bunnycdn_incoom_plugin_offload_js');
				if($allowdedJs == 'on'){
					foreach ($wp_scripts->to_do as $handle) {
						$src = isset($wp_scripts->registered[$handle]->src) ? $wp_scripts->registered[$handle]->src : '';
						if(!empty($src)){
							$js_file_path = $this->get_script_file_path($src);
							$url = $this->build_tmp_url($js_file_path);
							if(!empty($js_file_path) && $this->valid_uploaded($js_file_path)){
								$wp_scripts->registered[$handle]->src = carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url($url, '', true);
							}
						}
					}
				}	
			}	
		}	 	
	}

	public function scan_css() {
		$enable_assets = get_option('incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox', '');
		if ($enable_assets) {
	        global $wp_styles;

	        $wp_styles->all_deps($wp_styles->queue);
	        $handles = $wp_styles->to_do;

	        if(isset($_GET['scan_assets'])){
		        $scripts = get_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_assets');
		        if(!is_array($scripts)){
		        	$scripts = array();
		        }

		        foreach ($handles as $handle) {
		            $src = strtok($wp_styles->registered[$handle]->src, '?');
		            $css_file_path = $this->get_script_file_path($src);
		            if (file_exists($css_file_path) && !in_array($css_file_path, $scripts)) {
		                $scripts[] = $css_file_path;
		            }
		        }

		        $default = array(
		        	'wp-includes/css/dashicons.css',
		        	'wp-includes/css/dashicons.min.css',
		        	'wp-includes/css/admin-bar.css',
		        	'wp-includes/css/admin-bar.min.css',
		        	'wp-includes/css/dist/block-library/style.css'
		        	);

		        $scripts = array_merge($scripts, $default);

		        update_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_assets', array_unique($scripts)); 
		    }else{    	
		        $uploaded = get_option('incoom_carrot_bunnycdn_incoom_plugin_uploaded_assets', '');
		        if(!is_array($uploaded)){
		        	$uploaded = array();
		        }

				$allowdedCss = get_option('incoom_carrot_bunnycdn_incoom_plugin_offload_css');
				if($allowdedCss == 'on'){
					foreach ($handles as $handle) {
						$src = isset($wp_styles->registered[$handle]->src) ? $wp_styles->registered[$handle]->src : '';
						if(!empty($src)){
							$css_file_path = $this->get_script_file_path($src);
							$url = $this->build_tmp_url($css_file_path);
							if(!empty($css_file_path) && $this->valid_uploaded($css_file_path)){
								$wp_styles->registered[$handle]->src = carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url($url, '', true);
							}
						}
					}
				}
			}    
	    }
    }

    public function scan_scripts() {
    	$enable_assets = get_option('incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox', '');
		if ($enable_assets) {
			global $wp_scripts;
			$wp_scripts->all_deps($wp_scripts->queue);

			if(isset($_GET['scan_assets'])){
		        $scripts = get_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_assets');
		        if(!is_array($scripts)){
		        	$scripts = array();
		        }

				foreach ($wp_scripts->registered as $script) {
				    $src = strtok($script->src, '?');
				    $js_file_path = $this->get_script_file_path($src);
				    if (file_exists($js_file_path) && !in_array($js_file_path, $scripts)) {
				        $scripts[] = $js_file_path;
				    }
				}

				$default = array(
		        	'wp-includes/js/admin-bar.min.js',
		        	'wp-includes/js/imagesloaded.min.js',
		        	'wp-includes/js/masonry.min.js',
		        	'wp-includes/js/dist/vendor/moment.min.js',
		        	'wp-includes/js/wp-embed.min.js',
		        	'wp-includes/js/jquery/ui/core.min.js',
		        	'wp-includes/js/jquery/ui/widget.min.js',
		        	'wp-includes/js/jquery/ui/mouse.min.js',
		        	'wp-includes/js/jquery/ui/slider.min.js'
		        	);

		        $scripts = array_merge($scripts, $default);

				update_option('incoom_carrot_bunnycdn_incoom_plugin_scanned_assets', array_unique($scripts));
			}else{
			
				$uploaded = get_option('incoom_carrot_bunnycdn_incoom_plugin_uploaded_assets', '');
		        if(!is_array($uploaded)){
		        	$uploaded = array();
		        }

				$allowdedJs = get_option('incoom_carrot_bunnycdn_incoom_plugin_offload_js');
				if($allowdedJs == 'on'){
					foreach ($wp_scripts->registered as $script) {
						$handle = $script->handle;
						$src = strtok($script->src, '?');
						$js_file_path = $this->get_script_file_path($src);
						$url = $this->build_tmp_url($js_file_path);
						if(!empty($js_file_path) && $this->valid_uploaded($js_file_path)){
							$wp_scripts->registered[$handle]->src = carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url($url, '', true);
						}	
					} 
				}   
			}	
		}
    }

	public function get_contents($file_path){
		$wp_filesystem = new WP_Filesystem_Direct(array());
        try{
        	return $wp_filesystem->get_contents($file_path);
        } catch ( Exception $e ) {
            return '';
        }
	}

	public function put_contents($file_path, $content){
		$wp_filesystem = new WP_Filesystem_Direct(array());
        try{
        	return $wp_filesystem->put_contents( $file_path, $content, FS_CHMOD_FILE);
        } catch ( Exception $e ) {
            return '';
        }
	}

	public function upload($path_absolute, $key){
		set_time_limit(0);
		list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();
		$basedir_absolute = $path_absolute;
		$prefix = $this->get_prefix();
		$uploadedKey = "{$prefix}{$key}";

		$uploaded = array();
        
        $array_aux = explode( '/', $key );
		$main_file = array_pop( $array_aux );
		$array_files[] = implode( "/", $array_aux );
		$array_files[] = $main_file;

        if(!in_array($key, $uploaded)){
        	try {
	            $result = $aws_s3_client->Upload_Media_File( $Bucket, $Region, $array_files, $basedir_absolute, 'public', $prefix );
	            return $aws_s3_client->getObjectUrl( $Bucket, $Region, $uploadedKey);
	        } catch ( Exception $e ) {
	            return false;
	        }
        }
	}

	/*
	* Get all file in directory
	*/
	public function get_dir_contents($dir, $filter = '', &$results = array()) {
	    $files = scandir($dir);

	    foreach($files as $key => $value){
	        $path = realpath($dir.DIRECTORY_SEPARATOR.$value); 

	        if(!is_dir($path)) {
	            if(empty($filter) || preg_match($filter, $path)) $results[] = $path;
	        } elseif($value != "." && $value != "..") {
	            $this->get_dir_contents($path, $filter, $results);
	        }
	    }

	    return $results;
	} 

	/**
     * Private function to determine if files are local or remote
     * Used for merge_images() and minify() to determine if filemtime can be used
     *
     * @access private
     * @param string $file
     * @return bool
     */
    private function remote_file( $file )
    {
        //It is a remote file
        if( substr( $file, 0, 4 ) == 'http' )
        {
            return true;
        }
        //Local file
        else
        {
            return false;
        }
    }

	private function get_font_urls($input){
	    $results = array(); // Just an empty array;
		$fontface_regex = '~
		@font-face\s*    # Match @font-face and some spaces
		(                # Start group 1
		   \{            # Match {
		   (?:           # A non-capturing group
		      [^{}]+     # Match anything except {} one or more times
		      |          # Or
		      (?1)       # Recurse/rerun the expression of group 1
		   )*            # Repeat 0 or more times
		   \}            # Match }
		)                # End group 1
		~xs';

		$url_regex = '~
		url\s*\(         # Match url, optionally some whitespaces and then (
		\s*              # Match optionally some whitespaces
		("|\'|)          # It seems that the quotes are optional according to http://www.w3.org/TR/CSS2/syndata.html#uri
		\K               # Reset the match
		(?!["\']?(?:https?://|ftp://))  # Put your negative-rules here (do not match url\'s with http, https or ftp)
		(?:[^\\\\]|\\\\.)*?  # Match anything except a backslash or backslash and a character zero or more times ungreedy
		(?=              # Lookahead
		   \1            # Match what was matched in group 2
		   \s*           # Match optionally some whitespaces
		   \)            # Match )
		)
		~xs';

		preg_match_all($fontface_regex, $input, $fontfaces); // Get all font-face instances
		if(isset($fontfaces[0])){ // If there is a match then
			foreach($fontfaces[0] as $fontface){ // Foreach instance
				preg_match_all($url_regex, $fontface, $r); // Let's match the url's
				if(isset($r[0])){ // If there is a hit
					foreach ($r[0] as $font) {
						$results[] = $font;
					}
				}
			}
		}
	 
	    return $results;
	}

	private function get_absolute_path($path){
		return dirname($path);
	}

	private function get_real_file_name($relative){
		$relative = strtok($relative, '?');
		$arr_relative = explode('/', $relative);
		$count_arr_relative = count($arr_relative);
		$count_relative = substr_count($relative, '..');

		if($count_relative > 0){
			$relative = join('/', array_slice($arr_relative, $count_relative, $count_arr_relative));
		}
		return strtok($relative, '#');
	}

	private function format_absolute_path($path, $relative){
		$relative = strtok($relative, '?');
		$arr_path = explode('/', $path);
		$count_path = count($arr_path);
		$count_relative = substr_count($relative, '..');
		$real_index = $count_path - $count_relative;
		$arr_path = array_slice($arr_path, 0, $real_index);
		$file = join('/', $arr_path);
		return strtok($file, '#');
	}

	public function image_to_uri($content, $path){
		$search = array();
        $replace = array();
        $import_extensions = array(
	        'gif' => 'data:image/gif',
	        'png' => 'data:image/png',
	        'jpe' => 'data:image/jpeg',
	        'jpg' => 'data:image/jpeg',
	        'jpeg' => 'data:image/jpeg',
	        'svg' => 'data:image/svg+xml',
	        'tif' => 'image/tiff',
	        'tiff' => 'image/tiff',
	        'xbm' => 'image/x-xbitmap',
	    );
		preg_match_all('/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i', $content, $matches, PREG_PATTERN_ORDER);
		if ($matches) {
		    $path_dir = $this->get_absolute_path($path);
		    foreach($matches[3] as $u) {
		    	if(!$this->remote_file($u)){
			    	$path_absolute = $this->format_absolute_path($path_dir, $u);
	        		$Key = $this->get_real_file_name($u);
	        		$extension = substr(strrchr($Key, '.'), 1);
	        		if(in_array($extension, array_keys($import_extensions))){
				      	$url_uploaded = $this->upload($path_absolute, $Key);
	        			if($url_uploaded){
	        				$search[] = $u;
				      		$replace[] = $url_uploaded; 
	        			}
				    }  	
			    }
		    }
		    $content = str_replace($search, $replace, $content);
		}

		return $content;
	}

	public function upload_css($css_file_path){
		$search = array();
        $replace = array();
		$content = $this->get_contents(ABSPATH. $css_file_path);
		
        $path = $this->get_absolute_path(ABSPATH. $css_file_path);
        $urls = $this->get_font_urls($content);
        
        if(!empty($urls)){
        	foreach ($urls as $u) {
        		if(!$this->remote_file($u)){
        			$path_absolute = $this->format_absolute_path($path, $u);
        			$Key = $this->get_real_file_name($u);
        			$url_uploaded = $this->upload($path_absolute, $Key);
        			if($url_uploaded){
        				$search[] = $u;
			      		$replace[] = $url_uploaded; 
        			}
        		}
        	}

        	$content = str_replace($search, $replace, $content);
        }

        $content = $this->image_to_uri($content, ABSPATH. $css_file_path);

       
        try {
	        $upload_dir = wp_upload_dir();
	        $css_file_path_arr = explode('/', str_replace('/'.basename($css_file_path), '', $css_file_path));
	        $path_arr = join('/', $css_file_path_arr);

	        $tmp = $upload_dir['basedir'] . '/carrot-wordpress-offload/'. $path_arr. basename($css_file_path);
	        if(file_exists($tmp)){
	        	unlink($tmp);
	        } 


			$file = array(
					'base'    => $upload_dir['basedir'] . '/carrot-wordpress-offload/'. $path_arr,
					'file'    => basename($css_file_path),
					'content' => $content,
			);
			$new_file = incoom_carrot_bunnycdn_incoom_plugin_create_file( $file );
			return $this->upload($upload_dir['basedir'] . '/carrot-wordpress-offload', $css_file_path);

        } catch ( Exception $e ) {
            return false;
        }
	}

	public function upload_script($js_file_path){
	    return $this->upload(ABSPATH, $js_file_path);
	}
}
