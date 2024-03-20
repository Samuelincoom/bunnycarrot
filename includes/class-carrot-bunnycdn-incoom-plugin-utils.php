<?php

/**
 * Plugin utils
 *
 * @since      1.0.0
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'carrot_bunnycdn_incoom_plugin_Utils' ) ) {

	class carrot_bunnycdn_incoom_plugin_Utils {

		/**
		 * Get post ID.
		 *
		 * @param null|int|WP_Post $post Optional. Post ID or post object. Defaults to current post.
		 *
		 * @return int
		 */
		public static function get_post_id( $post = null ) {
			return (int) get_post_field( 'ID', $post );
		}

		/**
		 * Trailing slash prefix string ensuring no leading slashes.
		 *
		 * @param $string
		 *
		 * @return string
		 */
		public static function trailingslash_prefix( $string ) {
			return ltrim( trailingslashit( $string ), '/\\' );
		}

		/**
		 * Remove scheme from URL.
		 *
		 * @param string $url
		 *
		 * @return string
		 */
		public static function remove_scheme( $url ) {
			if(!is_string($url)){
				return false;
			}
			return preg_replace( '/^(?:http|https):/', '', $url );
		}

		/**
		 * Remove size from filename (image[-100x100].jpeg).
		 *
		 * @param string $url
		 * @param bool   $remove_extension
		 *
		 * @return string
		 */
		public static function remove_size_from_filename( $url, $remove_extension = false ) {
			$url = preg_replace( '/^(\S+)-[0-9]{1,4}x[0-9]{1,4}(\.[a-zA-Z0-9\.]{2,})?/', '$1$2', $url );

			$url = apply_filters( 'carrot_remove_size_from_filename', $url );

			if ( $remove_extension ) {
				$ext = pathinfo( $url, PATHINFO_EXTENSION );
				$url = str_replace( ".$ext", '', $url );
			}

			return $url;
		}

		/**
		 * Reduce the given URL down to the simplest version of itself.
		 *
		 * Useful for matching against the full version of the URL in a full-text search
		 * or saving as a key for dictionary type lookup.
		 *
		 * @param string $url
		 *
		 * @return string
		 */
		public static function reduce_url( $url ) {
			$parts = self::parse_url( $url );
			$host  = isset( $parts['host'] ) ? $parts['host'] : '';
			$port  = isset( $parts['port'] ) ? ":{$parts['port']}" : '';
			$path  = isset( $parts['path'] ) ? $parts['path'] : '';

			return '//' . $host . $port . $path;
		}

		/**
		 * Parses a URL into its components. Compatible with PHP < 5.4.7.
		 *
		 * @param  string $url       The URL to parse.
		 *
		 * @param int     $component PHP_URL_ constant for URL component to return.
		 *
		 * @return mixed An array of the parsed components, mixed for a requested component, or false on error.
		 */
		public static function parse_url( $url, $component = -1 ) {
			$url       = trim( $url );
			$no_scheme = 0 === strpos( $url, '//' );

			if ( $no_scheme ) {
				$url = 'http:' . $url;
			}

			$parts = parse_url( $url, $component );

			if ( 0 < $component ) {
				return $parts;
			}

			if ( $no_scheme && is_array( $parts ) ) {
				unset( $parts['scheme'] );
			}

			return $parts;
		}

		/**
		 * Is the string a URL?
		 *
		 * @param string $string
		 *
		 * @return bool
		 */
		public static function is_url( $string ) {
			if ( ! is_string( $string ) ) {
				return false;
			}

			if ( preg_match( '@^(?:https?:)?//[a-zA-Z0-9\-]+@', $string ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Is the string a relative URL?
		 *
		 * @param $string
		 *
		 * @return bool
		 */
		public static function is_relative_url( $string ) {
			if ( empty( $string ) || ! is_string( $string ) ) {
				return false;
			}

			$url = static::parse_url( $string );

			return ( empty( $url['scheme'] ) && empty( $url['host'] ) );
		}

		/**
		 * Get file paths for all attachment versions.
		 *
		 * @param int        $attachment_id
		 * @param bool       $exists_locally
		 * @param array|bool $meta
		 * @param bool       $include_backups
		 *
		 * @return array
		 */
		public static function get_attachment_file_paths( $attachment_id, $exists_locally = true, $meta = false, $include_backups = true ) {
			$file_path = get_attached_file( $attachment_id, true );
			$paths     = array(
				'original' => $file_path,
			);

			if ( ! $meta ) {
				$meta = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
			}

			if ( is_wp_error( $meta ) ) {
				return $paths;
			}

			$file_name = wp_basename( $file_path );

			// If file edited, current file name might be different.
			if ( isset( $meta['file'] ) ) {
				$paths['file'] = str_replace( $file_name, wp_basename( $meta['file'] ), $file_path );
			}

			// Thumb
			if ( isset( $meta['thumb'] ) ) {
				$paths['thumb'] = str_replace( $file_name, $meta['thumb'], $file_path );
			}

			// Sizes
			if ( isset( $meta['sizes'] ) ) {
				foreach ( $meta['sizes'] as $size => $file ) {
					if ( isset( $file['file'] ) ) {
						$paths[ $size ] = str_replace( $file_name, $file['file'], $file_path );
					}
				}
			}

			$backups = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

			// Backups
			if ( $include_backups && is_array( $backups ) ) {
				foreach ( $backups as $size => $file ) {
					if ( isset( $file['file'] ) ) {
						$paths[ $size ] = str_replace( $file_name, $file['file'], $file_path );
					}
				}
			}

			// Allow other processes to add files to be uploaded
			$paths = apply_filters( 'carrot_bunnycdn_incoom_plugin_attachment_file_paths', $paths, $attachment_id, $meta );

			// Remove duplicates
			$paths = array_unique( $paths );

			// Remove paths that don't exist
			if ( $exists_locally ) {
				foreach ( $paths as $key => $path ) {
					if ( ! file_exists( $path ) ) {
						unset( $paths[ $key ] );
					}
				}
			}

			return $paths;
		}

		/**
		 * Get an attachment's edited file paths.
		 *
		 * @param int $attachment_id
		 *
		 * @return array
		 */
		public static function get_attachment_edited_file_paths( $attachment_id ) {
			$paths = self::get_attachment_file_paths( $attachment_id, false );
			$paths = array_filter( $paths, function ( $path ) {
				return preg_match( '/-e[0-9]{13}(?:-[0-9]{1,4}x[0-9]{1,4})?\./', wp_basename( $path ) );
			} );

			return $paths;
		}

		/**
		 * Get an attachment's edited S3 keys.
		 *
		 * @param int   $attachment_id
		 * @param array $provider_object
		 *
		 * @return array
		 */
		public static function get_attachment_edited_keys( $attachment_id, $provider_object ) {
			$prefix = trailingslashit( pathinfo( $provider_object['key'], PATHINFO_DIRNAME ) );
			$paths  = self::get_attachment_edited_file_paths( $attachment_id );
			$paths  = array_map( function ( $path ) use ( $prefix ) {
				return array( 'Key' => $prefix . wp_basename( $path ) );
			}, $paths );

			return $paths;
		}

		/**
		 * Get intermediate size from attachment filename.
		 *
		 * @param int    $attachment_id
		 * @param string $filename
		 *
		 * @return string
		 */
		public static function get_intermediate_size_from_filename( $attachment_id, $filename ) {
			$sizes = self::get_attachment_file_paths( $attachment_id, false );

			foreach ( $sizes as $size => $file ) {
				if ( wp_basename( $file ) === $filename ) {
					return $size;
				}
			}

			return '';
		}

		/**
		 * Strip edited image suffix and extension from path.
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		public static function strip_image_edit_suffix_and_extension( $path ) {
			$parts    = pathinfo( $path );
			$filename = preg_replace( '/-e[0-9]{13}/', '', $parts['filename'] );

			return str_replace( $parts['basename'], $filename, $path );
		}

		/**
		 * Create a site link for given URL.
		 *
		 * @param string $url
		 * @param string $text
		 *
		 * @return string
		 */
		public static function dbrains_link( $url, $text ) {
			return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $text ) );
		}

		/**
		 * Check whether two URLs share the same domain.
		 *
		 * @param string $url_a
		 * @param string $url_b
		 *
		 * @return bool
		 */
		public static function url_domains_match( $url_a, $url_b ) {
			if ( ! static::is_url( $url_a ) || ! static::is_url( $url_b ) ) {
				return false;
			}

			return static::parse_url( $url_a, PHP_URL_HOST ) === static::parse_url( $url_b, PHP_URL_HOST );
		}

		/**
		 * Get the current domain.
		 *
		 * @return string|false
		 */
		public static function current_domain() {
			return parse_url( home_url(), PHP_URL_HOST );
		}

		/**
		 * Get the base domain of the current domain.
		 *
		 * @return string
		 */
		public static function current_base_domain() {
			return static::base_domain( static::current_domain() );
		}

		/**
		 * Get the base domain of the supplied domain.
		 *
		 * @param string $domain
		 *
		 * @return string
		 */
		public static function base_domain( $domain ) {
			if ( WP_Http::is_ip_address( $domain ) ) {
				return $domain;
			}

			$parts = explode( '.', $domain );

			// localhost etc.
			if ( is_string( $parts ) ) {
				return $domain;
			}

			if ( count( $parts ) < 3 ) {
				return $domain;
			}

			// Just knock off the first segment.
			unset( $parts[0] );

			return implode( '.', $parts );
		}

		/**
		 * Very basic check of whether domain is real.
		 *
		 * @param string $domain
		 *
		 * @return bool
		 *
		 * Note: Very early version, may extend with further "local" domain checks if relevant.
		 */
		public static function is_public_domain( $domain ) {
			// We're not going to test SEO etc. for ip addresses.
			if ( WP_Http::is_ip_address( $domain ) ) {
				return false;
			}

			$parts = explode( '.', $domain );

			// localhost etc.
			if ( is_string( $parts ) ) {
				return false;
			}

			// TODO: Maybe check domain TLD.

			return true;
		}

		/**
		 * Is given URL considered SEO friendly?
		 *
		 * @param string $url
		 *
		 * @return bool
		 */
		public static function seo_friendly_url( $url ) {
			$domain      = static::base_domain( parse_url( $url, PHP_URL_HOST ) );
			$base_domain = static::current_base_domain();

			// If either domain is not a public domain then skip checks.
			if ( ! static::is_public_domain( $domain ) || ! static::is_public_domain( $base_domain ) ) {
				return true;
			}

			if ( substr( $domain, -strlen( $base_domain ) ) === $base_domain ) {
				return true;
			}

			return false;
		}

		/**
		 * A safe wrapper for deactivate_plugins()
		 */
		public static function deactivate_plugins() {
			if ( ! function_exists( 'deactivate_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			call_user_func_array( 'deactivate_plugins', func_get_args() );
		}

		/**
		 * Get the first defined constant from the given list of constant names.
		 *
		 * @param array $constants
		 *
		 * @return string|false string constant name if defined, otherwise false if none are defined
		 */
		public static function get_first_defined_constant( $constants ) {
			if ( ! empty( $constants ) ) {
				foreach ( (array) $constants as $constant ) {
					if ( defined( $constant ) ) {
						return $constant;
					}
				}
			}

			return false;
		}

		/**
		 * Ensure returned keys are for correct attachment.
		 *
		 * @param array $keys
		 *
		 * @return array
		 */
		public static function validate_attachment_keys( $attachment_id, $keys ) {
			$paths     = self::get_attachment_file_paths( $attachment_id, false );
			$filenames = array_map( 'wp_basename', $paths );

			foreach ( $keys as $key => $value ) {
				$filename = wp_basename( $value );

				if ( ! in_array( $filename, $filenames ) ) {
					unset( $keys[ $key ] );
				}
			}

			return $keys;
		}

		/**
		 * Sanitize custom domain
		 *
		 * @param string $domain
		 *
		 * @return string
		 */
		public static function sanitize_custom_domain( $domain ) {
			$domain = preg_replace( '@^[a-zA-Z]*:\/\/@', '', $domain );
			$domain = preg_replace( '@[^a-zA-Z0-9\.\-]@', '', $domain );

			return $domain;
		}

		/**
		 * Check is image by attachment_id
		 *
		 * @param string $attachment_id
		 *
		 * @return boolean
		 */
		public static function is_image( $attachment_id ) {
			return wp_attachment_is_image( $attachment_id );
		}

		/**
		 * Check can build webp by attachment_id
		 *
		 * @param string $attachment_id
		 *
		 * @return boolean
		 */
		public static function can_build_webp( $attachment_id ) {
			if(!self::is_image( $attachment_id )){
				return false;
			}

			$upload_dir = wp_upload_dir();
			$basedir_absolute = $upload_dir['basedir'];
			$key = get_post_meta( $attachment_id, '_wp_attached_file', true );
			$source = $basedir_absolute. '/' . $key;
			
			if(file_exists($source) && is_readable($source)){
				if(strpos($source, '.png') !== false || strpos($source, '.jpg') !== false || strpos($source, '.jpeg') !== false){
					return true;
				}
			}

			return false;
		}

		/**
		 * Get key from URL
		 *
		 * @param string $url
		 *
		 * @return string
		 */
		public static function get_key_from_url($old_url, $remove_size = true){
			
			if($remove_size){
				$old_url = self::remove_size_from_filename($old_url);
			}
			
			$base_url = carrot_bunnycdn_incoom_plugin_get_bucket_url();
			$bucket_folder_main = get_option('incoom_carrot_bunnycdn_incoom_plugin_bucket_folder_main', '');
			if(!empty($bucket_folder_main)){
				if(substr($bucket_folder_main, -1) == '/') {
		            $bucket_folder_main = substr($bucket_folder_main, 0, -1);
		        }
		        $base_url = $base_url.'/'.$bucket_folder_main;
			}

			$upload_dir = wp_upload_dir();
			$site_url = $upload_dir['baseurl'];
			$find = [$base_url, $site_url];
			
			try {
				$url_remove_schema = preg_replace("(^https?://)", "", $base_url );
				if($url_remove_schema){
					$tmp = explode('/', $url_remove_schema);
					$url_remove_schema = str_replace($tmp[0], '', $url_remove_schema);
					$find[] = rtrim(ltrim($url_remove_schema, '/'));
				}
			} catch ( Exception $e ) {
				///
			}
			
			$url = str_replace($find, '', $old_url);
			return urldecode(ltrim($url, '/'));
		}

		public static function get_webp_url_with_key($post_id, $key){

			if(!carrot_bunnycdn_incoom_plugin_enable_webp()){
				return false;
			}
			
			$data = get_post_meta($post_id, '_incoom_carrot_bunnycdn_webp_info', true);
			if(empty($data)){
				return false;
			}

			if(!is_array($data)){
				return false;
			}

			$s3_path = get_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_s3_path', true);
			if ( $s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' && $s3_path != null ) {
				$webp = '';
				$sanitizekey = sanitize_title(basename($key));

				foreach ($data as $file => $k) {
					if( (strpos($k, $key) !== false) || (strpos( $k, $sanitizekey ) !== false) || $file == $key){
						$webp = $k;
						break;
					}
				}
				
				return $webp;
			}

			return false;
		}

		/**
		 * Get an array of bare base_urls that can be used for uploaded items.
		 *
		 * @return array
		 */
		public static function get_bare_upload_base_urls() {
			$base_urls = array();

			$uploads  = wp_upload_dir();
			$base_url = incoom_carrot_bunnycdn_incoom_plugin_maybe_fix_local_subsite_url( $uploads['baseurl'] );
			$base_url = self::remove_scheme( $base_url );
			$domain   = self::parse_url( $uploads['baseurl'], PHP_URL_HOST );

			/**
			 * Allow alteration of the local domains that can be matched on.
			 *
			 * @param array $domains
			 */
			$domains = apply_filters( 'incoom_carrot_bunnycdn_incoom_plugin_local_domains', (array) $domain );

			if ( ! empty( $domains ) ) {
				foreach ( array_unique( $domains ) as $match_domain ) {
					$base_urls[] = substr_replace( $base_url, $match_domain, 2, strlen( $domain ) );
				}
			}

			return $base_urls;
		}

		public static function put_contents($file_path, $content){
			$wp_filesystem = new WP_Filesystem_Direct(array());
	        try{
	        	return $wp_filesystem->put_contents( $file_path, $content, FS_CHMOD_FILE);
	        } catch ( Exception $e ) {
				error_log($e->getMessage());
	            return '';
	        }
		}

		/**
		 * Get a file's real mime type
		 *
		 * @param string $file_path
		 *
		 * @return string
		 */
		public static function get_mime_type( $file_path ) {
			$file_type = wp_check_filetype_and_ext( $file_path, wp_basename( $file_path ) );

			return $file_type['type'];
		}


		/**
		 * Ensure string has no leading slash, like in relative paths.
		 *
		 * @param $string
		 *
		 * @return string
		 */
		public static function unleadingslashit( $string ) {
			return ltrim( trim( $string ), '/\\' );
		}

		/**
		 * Returns indexed array of full size paths, e.g. orig and edited.
		 *
		 * @param array $paths Associative array of sizes and relative paths
		 *
		 * @return array
		 *
		 * @see get_attachment_file_paths
		 */
		public static function fullsize_paths( $paths ) {
			if ( is_array( $paths ) && ! empty( $paths ) ) {
				return array_values( array_unique( array_intersect_key( $paths, array_flip( array( carrot_bunnycdn_incoom_plugin_Item::primary_object_key(), 'file', 'full-orig', 'original_image', 'original' ) ) ) ) );
			} else {
				return array();
			}
		}

		/**
		 * Converts an array of upload file paths to all be relative paths.
		 * If any path is not absolute or does begin with current uploads base dir it will not be altered.
		 *
		 * @param array $paths Array of upload file paths, absolute or relative.
		 *
		 * @return array Input array with values switched to relative upload file paths.
		 */
		public static function make_upload_file_paths_relative( $paths ) {
			if ( empty( $paths ) ) {
				return array();
			}

			if ( ! is_array( $paths ) ) {
				$paths = array( $paths );
			}

			$uploads = wp_upload_dir();
			$basedir = trailingslashit( $uploads['basedir'] );
			$offset  = strlen( $basedir );

			foreach ( $paths as $key => $path ) {
				if ( 0 === strpos( $path, $basedir ) ) {
					$paths[ $key ] = substr( $path, $offset );
				}
			}

			return $paths;
		}
	}
}