<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Rename file
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      2.0.3
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */
class carrot_bunnycdn_incoom_plugin_Rename_File {

    public $admin = null;
	public $pro = false;
	public $is_rest = false;
	public $is_cli = false;
	public $upload_folder = null;
	public $site_url = null;
	public $contentDir = null; // becomes 'wp-content/uploads'
	private $allow_usage = null;
	private $allow_setup = null;

	public function __construct() {
		$this->site_url = get_site_url();
		$this->upload_folder = wp_upload_dir();
		$this->contentDir = substr( $this->upload_folder['baseurl'], 1 + strlen( $this->site_url ) );
    }
    
	public static function sensitive_file_exists( $filename ) {

		$original_filename = $filename;
		$caseInsensitive = get_option( 'carrot_bunnycdn_incoom_plugin_case_insensitive_check', false );
		$output = false;
		$directoryName = carrot_bunnycdn_incoom_plugin_dirname( $filename );
		$fileArray = glob( $directoryName . '/*', GLOB_NOSORT );
		$i = ( $caseInsensitive ) ? "i" : "";

		// Check if \ is in the string
		if ( preg_match( "/\\\|\//", $filename) ) {
			$array = preg_split("/\\\|\//", $filename);
			$filename = $array[count( $array ) -1];
		}
		// Compare filenames
		foreach ( $fileArray as $file ) {
			if ( preg_match( "/\/" . preg_quote( $filename ) . "$/{$i}", $file ) ) {
				$output = $file;
				break;
			}
		}
		return $output;
	}

	public static function rmdir_recursive( $directory ) {
		foreach ( glob( "{$directory}/*" ) as $file ) {
			if ( is_dir( $file ) )
			carrot_bunnycdn_incoom_plugin_Rename_File::rmdir_recursive( $file );
			else
				unlink( $file );
		}
		rmdir( $directory );
	}

	public function wpml_media_is_installed() {
		return defined( 'WPML_MEDIA_VERSION' );
	}

	// To avoid issue with WPML Media for instance
	public function is_real_media( $id ) {
		if ( $this->wpml_media_is_installed() ) {
			global $sitepress;
			$language = $sitepress->get_default_language( $id );
			return icl_object_id( $id, 'attachment', true, $language ) == $id;
		}
		return true;
	}

	public function is_header_image( $id ) {
		static $headers = false;
		if ( $headers == false ) {
			global $wpdb;
			$headers = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_is_custom_header'" );
		}
		return in_array( $id, $headers );
	}

	public function generate_unique_filename( $actual, $dirname, $filename, $counter = null ) {
		$new_filename = $filename;
		if ( !is_null( $counter ) ) {
			$whereisdot = strrpos( $new_filename, '.' );
			$new_filename = substr( $new_filename, 0, $whereisdot ) . '-' . $counter
				. '.' . substr( $new_filename, $whereisdot + 1 );
		}
		if ( $actual == $new_filename )
			return false;
		if ( file_exists( $dirname . "/" . $new_filename ) )
			return $this->generate_unique_filename( $actual, $dirname, $filename,
				is_null( $counter ) ? 2 : $counter + 1 );
		return $new_filename;
	}
	
	public function check_attachment( $post, &$output = array(), $manual_filename = null ) {

		if( !isset( $post['ID'] ) ){
			return false;
		}

		$id = $post['ID'];
		$old_filepath = get_attached_file( $id );
		$old_filepath = carrot_bunnycdn_incoom_plugin_Rename_File::sensitive_file_exists( $old_filepath );
		$path_parts = carrot_bunnycdn_incoom_plugin_pathinfo( $old_filepath );
		
		// If the file doesn't exist, let's not go further.
		if ( !isset( $path_parts['dirname'] ) || !isset( $path_parts['basename'] ) )
			return false;

		//print_r( $path_parts );
		$directory = $path_parts['dirname'];
		$old_filename = $path_parts['basename'];

		// Check if media/file is dead
		if ( !$old_filepath || !file_exists( $old_filepath ) ) {
			delete_post_meta( $id, '_require_file_renaming' );
			return false;
		}

		if ( !empty( $manual_filename ) ) {
			$new_filename = $manual_filename;
			$output['manual'] = true;
		}
		else {
			
			if ( get_post_meta( $id, '_manual_file_renaming', true ) ) {
				return false;
			}

			$base_for_rename = apply_filters( 'carrot_bunnycdn_incoom_plugin_base_for_rename', $post['post_title'], $id );
			$new_filename = $this->new_filename( $base_for_rename, $old_filename, null, $post );
			if ( is_null( $new_filename ) ) {
				return false; // Leave it as it is
			}
		}
		
		// Filename is equal to sanitized title
		if ( $new_filename == $old_filename ) {
			delete_post_meta( $id, '_require_file_renaming' );
			return false;
		}

		// Check for case issue, numbering
		$ideal_filename = $new_filename;
		$new_filepath = trailingslashit( $directory ) . $new_filename;
		$existing_file = carrot_bunnycdn_incoom_plugin_Rename_File::sensitive_file_exists( $new_filepath );
		$case_issue = strtolower( $old_filename ) == strtolower( $new_filename );
		if ( $existing_file && !$case_issue ) {
			$is_numbered = apply_filters( 'carrot_bunnycdn_incoom_plugin_numbered', false );
			if ( $is_numbered ) {
				$new_filename = $this->generate_unique_filename( $ideal, $directory, $new_filename );
				if ( !$new_filename ) {
					delete_post_meta( $id, '_require_file_renaming' );
					return false;
				}
				$new_filepath = trailingslashit( $directory ) . $new_filename;
				$existing_file = carrot_bunnycdn_incoom_plugin_Rename_File::sensitive_file_exists( $new_filepath );
			}
		}

		// Send info to the requester function
		$output['post_id'] = $id;
		$output['post_name'] = $post['post_name'];
		$output['post_title'] = $post['post_title'];
		$output['current_filename'] = $old_filename;
		$output['current_filepath'] = $old_filepath;
		$output['ideal_filename'] = $ideal_filename;
		$output['proposed_filename'] = $new_filename;
		$output['desired_filepath'] = $new_filepath;
		$output['case_issue'] = $case_issue;
		$output['manual'] = !empty( $manual_filename );
		$output['locked'] = get_post_meta( $id, '_manual_file_renaming', true );
		$output['proposed_filename_exists'] = !!$existing_file;
		$output['original_image'] = null;
		
		// If the ideal filename already exists
		// Maybe that's the original_image! If yes, we should let it go through
		// as the original_rename will be renamed into another filename anyway.
		if ( !!$existing_file ) {
			$meta = wp_get_attachment_metadata( $id );
			if ( isset( $meta['original_image'] ) && $new_filename === $meta['original_image'] ) {
				$output['original_image'] = $meta['original_image'];
				$output['proposed_filename_exists'] = false;
			}
		}

		// Set the '_require_file_renaming', even though it's not really used at this point (but will be,
		// with the new UI).
		if ( !get_post_meta( $post['ID'], '_require_file_renaming', true ) && !$output['locked']) {
			add_post_meta( $post['ID'], '_require_file_renaming', true, true );
		}

		return true;
	}

	public function check_text() {
		$issues = array();
		global $wpdb;
		$ids = $wpdb->get_col( "
			SELECT p.ID
			FROM $wpdb->posts p
			WHERE post_status = 'inherit'
			AND post_type = 'attachment'
		" );
		foreach ( $ids as $id )
			if ( $this->check_attachment( get_post( $id, ARRAY_A ), $output ) )
				array_push( $issues, $output );
		return $issues;
    }
    
	public function log( $data, $inErrorLog = false ) {
		try {
            error_log( print_r($data, true) );
        } catch (\Throwable $th) {
            //throw $th;
        }
	}

	/**
	 *
	 * GENERATE A NEW FILENAME
	 *
	 */

	public function replace_chars( $str ) {
		$special_chars = array();
		$special_chars = apply_filters( 'carrot_bunnycdn_incoom_plugin_replace_rules', $special_chars );
		if ( !empty( $special_chars ) )
			foreach ( $special_chars as $key => $value )
				$str = str_replace( $key, $value, $str );
		return $str;
	}

	/**
	 * Transform full width hyphens and other variety hyphens in half size into simple hyphen,
	 * and avoid consecutive hyphens and also at the beginning and end as well.
	 */
	public function format_hyphens( $str ) {
		$hyphen = '-';
		$hyphens = [
			'﹣', '－', '−', '⁻', '₋',
			'‐', '‑', '‒', '–', '—',
			'―', '﹘', 'ー','ｰ',
		];
		$str = str_replace( $hyphens, $hyphen, $str );
		// remove at the beginning and end.
		$beginning = mb_substr( $str, 0, 1 );
		if ( $beginning === $hyphen ) {
			$str = mb_substr( $str, 1 );
		}
		$end = mb_substr( $str, -1 );
		if ( $end === $hyphen ) {
			$str = mb_strcut( $str, 0, mb_strlen( $str ) - 1 );
		}
		$str = preg_replace( '/-{2,}/u', '-', $str );
		$str = trim( $str, implode( '', $hyphens ) );
		return $str;
	}

	/**
	 * Computes the ideal filename based on a text
	 * @param array $media
	 * @param string $text
	 * @param string $manual_filename
	 * @return string|NULL If the resulting filename had no any valid characters, NULL is returned
	 */
	public function new_filename( $text, $current_filename, $manual_filename = null, $media = null ) {

		// Gather the base values.

		if ( empty( $current_filename ) && !empty( $media ) ) {
			$current_filename = get_attached_file( $media['ID'] );
		}

		$pp = carrot_bunnycdn_incoom_plugin_pathinfo( $current_filename );
		$new_ext = empty( $pp['extension'] ) ? '' : $pp['extension'];
		$old_filename_no_ext = $pp['filename'];
		$text = empty( $text ) ? $old_filename_no_ext : $text;

		// Generate the new filename.

		if ( !empty( $manual_filename ) ) {
			// Forced filename (manual or undo, basically). Keep this extension in $new_ext.
			$manual_pp = carrot_bunnycdn_incoom_plugin_pathinfo( $manual_filename );
			$manual_filename = $manual_pp['filename'];
			$new_ext = empty( $manual_pp['extension'] ) ? $new_ext : $manual_pp['extension'];
			$new_filename = $manual_filename;
		}
		else {
			// Filename is generated from $text, without an extension.

			// Those are basically errors, when titles are generated from filename
			$text = str_replace( [".png", ".jpeg", ".jpg"], "", $text );
			
			// Related to English
			$text = str_replace( "'s", "", $text );
			$text = str_replace( "n\'t", "nt", $text );
			$text = preg_replace( "/\'m/i", "-am", $text );

			// We probably do not want those neither
			$text = str_replace( "'", "-", $text );
			$text = preg_replace( "/\//s", "-", $text );
			$text = str_replace( ['.','…'], "", $text );

			$text = $this->replace_chars( $text );
			// Changed strolower to mb_strtolower... 
			if ( function_exists( 'mb_strtolower' ) ) {
				$text = mb_strtolower( $text );
			}
			else {
				$text = strtolower( $text );
			}
			$text = sanitize_file_name( $text );
			$new_filename = $this->format_hyphens( $text );
			$new_filename = trim( $new_filename, '-.' );
		}

		if ( empty( $manual_filename ) ) {
			$new_filename = $this->format_hyphens( $new_filename );
		}

		if ( !$manual_filename ) {
			$new_filename = apply_filters( 'carrot_bunnycdn_incoom_plugin_new_filename', $new_filename, $old_filename_no_ext, $media );
			$new_filename = sanitize_file_name( $new_filename );
		}

		// If the resulting filename had no any valid character, return NULL
		if ( empty( $new_filename ) ) {
			return null;
		}

		// We know have a new filename, let's add an extension.
		$new_filename = !empty( $new_ext ) ? ( $new_filename . '.' . $new_ext ) : $new_filename;

		return $new_filename;
	}

	// Only replace the first occurence
	public function str_replace( $needle, $replace, $haystack ) {
		$pos = strpos( $haystack, $needle );
		if ( $pos !== false )
			$haystack = substr_replace( $haystack, $replace, $pos, strlen( $needle ) );
		return $haystack;
	}

	/**
	 *
	 * RENAME FILES + COFFEE TIME
	 */

    // From a url to the shortened and cleaned url (for example '2025/02/file.png')
    public function clean_url( $url ) {
        $dirIndex = strpos( $url, $this->contentDir );
        if ( empty( $url ) || $dirIndex === false ) {
            $finalUrl =  null;
        }
        else {
            $finalUrl = urldecode( substr( $url, 1 + strlen( $this->contentDir ) + $dirIndex ) );
        }
        return $finalUrl;
    }

	public function call_hooks_rename_url( $post, $orig_image_url, $new_image_url  ) {
		// With the full URLs
		do_action( 'carrot_bunnycdn_incoom_plugin_url_renamed', $post, $orig_image_url, $new_image_url );
		// With clean URLs relative to /uploads
		do_action( 'carrot_bunnycdn_incoom_plugin_url_renamed', $post, $this->clean_url( $orig_image_url ), $this->clean_url( $new_image_url ) );
	}

	public function rename_file( $old, $new, $case_issue = false ) {
		// Some plugins can create custom thumbnail folders instead in the same folder, so make sure
		// the thumbnail folders are available.
		wp_mkdir_p( dirname($new) );

		// If there is a case issue, that means the system doesn't make the difference between AA.jpg and aa.jpg even though WordPress does.
		// In that case it is important to rename the file to a temporary filename in between like: AA.jpg ➡️ TMP.jpg ➡️ aa.jpg.
		if ( $case_issue ) {
			if ( !rename( $old, $old . md5( $old ) ) ) {
				$this->log( "🚫 The file couldn't be renamed (case issue) from $old to " . $old . md5( $old ) . "." );
				return false;
			}
			if ( !rename( $old . md5( $old ), $new ) ) {
				$this->log( "🚫 The file couldn't be renamed (case issue) from " . $old . md5( $old ) . " to $new." );
				return false;
			}
		}
		else if ( ( !rename( $old, $new ) ) ) {
			$this->log( "🚫 The file couldn't be renamed from $old to $new." );
			return false;
		}
		return true;
	}

	public function move( $media, $newPath ) {
		$id = null;
		$post = null;

		// Check the arguments
		if ( is_numeric( $media ) ) {
			$id = $media;
			$post = get_post( $media, ARRAY_A );
		}
		else if ( is_array( $media ) ) {
			$id = $media['ID'];
			$post = $media;
		}
		else {
			die( 'Media File Renamer: move() requires the ID or the array for the media.' );
		}

		// Prepare the variables
		$old_filepath = get_attached_file( $id );
		$path_parts = carrot_bunnycdn_incoom_plugin_pathinfo( $old_filepath );
		$old_ext = $path_parts['extension'];
		$upload_dir = wp_upload_dir();
		$old_directory = trim( str_replace( $upload_dir['basedir'], '', $path_parts['dirname'] ), '/' ); // '2011/01'
		$new_directory = trim( $newPath, '/' );
		$filename = $path_parts['basename']; // 'whatever.jpeg'
		$new_filepath = trailingslashit( trailingslashit( $upload_dir['basedir'] ) . $new_directory ) . $filename;

		$this->log( "🏁 Move Media: " . $filename );
		$this->log( "The new directory will be: " . carrot_bunnycdn_incoom_plugin_dirname( $new_filepath ) );

		// Create the directory if it does not exist
		if ( !file_exists( carrot_bunnycdn_incoom_plugin_dirname( $new_filepath ) ) ) {
			mkdir( carrot_bunnycdn_incoom_plugin_dirname( $new_filepath ), 0777, true );
		}

		// There is no support for UNDO (as the current process of Media File Renamer doesn't keep the path for the undo, only the filename... so the move breaks this - let's deal with this later).

		// Move the main media file
		if ( !$this->rename_file( $old_filepath, $new_filepath ) ) {
			$this->log( "🚫 File $old_filepath ➡️ $new_filepath" );
			return false;
		}
		update_attached_file( $id, $new_filepath );
		$this->log( "✅ File $old_filepath ➡️ $new_filepath" );
		do_action( 'carrot_bunnycdn_incoom_plugin_path_renamed', $post, $old_filepath, $new_filepath );

		// Update the attachment meta
		$meta = wp_get_attachment_metadata( $id );

		if ( $meta ) {
			if ( isset( $meta['file'] ) && !empty( $meta['file'] ) )
				$meta['file'] = $this->str_replace( $old_directory, $new_directory, $meta['file'] );
			if ( isset( $meta['url'] ) && !empty( $meta['url'] ) && count( $meta['url'] ) > 4 )
				$meta['url'] = $this->str_replace( $old_directory, $new_directory, $meta['url'] );
			wp_update_attachment_metadata( $id, $meta );
		}

		// Better to check like this rather than with wp_attachment_is_image
		// PDFs also have thumbnails now, since WP 4.7
		$has_thumbnails = isset( $meta['sizes'] );

		if ( $has_thumbnails ) {
			$orig_image_urls = array();
			$orig_image_data = wp_get_attachment_image_src( $id, 'full' );
			$orig_image_urls['full'] = $orig_image_data[0];
			foreach ( $meta['sizes'] as $size => $meta_size ) {
				if ( !isset($meta['sizes'][$size]['file'] ) )
					continue;
				$meta_old_filename = $meta['sizes'][$size]['file'];
				$meta_old_filepath = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $old_directory ) . $meta_old_filename;
				$meta_new_filepath = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $new_directory ) . $meta_old_filename;
				$orig_image_data = wp_get_attachment_image_src( $id, $size );
				$orig_image_urls[$size] = $orig_image_data[0];

				// Double check files exist before trying to rename.
				if ( file_exists( $meta_old_filepath )
						&& ( ( !file_exists( $meta_new_filepath ) ) || is_writable( $meta_new_filepath ) ) ) {
					// WP Retina 2x is detected, let's rename those files as well
					if ( function_exists( 'wr2x_get_retina' ) ) {
						$wr2x_old_filepath = $this->str_replace( '.' . $old_ext, '@2x.' . $old_ext, $meta_old_filepath );
						$wr2x_new_filepath = $this->str_replace( '.' . $old_ext, '@2x.' . $old_ext, $meta_new_filepath );
						if ( file_exists( $wr2x_old_filepath )
							&& ( ( !file_exists( $wr2x_new_filepath ) ) || is_writable( $wr2x_new_filepath ) ) ) {

							// Rename retina file
							if ( !$this->rename_file( $wr2x_old_filepath, $wr2x_new_filepath ) ) {
								$this->log( "🚫 Retina $wr2x_old_filepath ➡️ $wr2x_new_filepath" );
								return $post;
							}
							$this->log( "✅ Retina $wr2x_old_filepath ➡️ $wr2x_new_filepath" );
							do_action( 'carrot_bunnycdn_incoom_plugin_path_renamed', $post, $wr2x_old_filepath, $wr2x_new_filepath );
						}
					}

					// Rename meta file
					if ( !$this->rename_file( $meta_old_filepath, $meta_new_filepath ) ) {
						$this->log( "🚫 File $meta_old_filepath ➡️ $meta_new_filepath" );
						return false;
					}

					// Success, call other plugins
					$this->log( "✅ File $meta_old_filepath ➡️ $meta_new_filepath" );
					do_action( 'carrot_bunnycdn_incoom_plugin_path_renamed', $post, $meta_old_filepath, $meta_new_filepath );

				}
			}
		}
		else {
			$orig_attachment_url = wp_get_attachment_url( $id );
		}

		// Update metadata
		//if ( $meta )
		//	wp_update_attachment_metadata( $id, $meta );
		//update_attached_file( $id, $new_filepath );

		// I wonder about cleaning the cache for this media. It might have no impact, and will not reset the cache for the posts using this media anyway, and it adds processing time. I keep it for now, but there might be something better to do.
		clean_post_cache( $id );

		// Call the actions so that the plugin's plugins can update everything else (than the files)
		if ( $has_thumbnails ) {
			$orig_image_url = $orig_image_urls['full'];
			$new_image_data = wp_get_attachment_image_src( $id, 'full' );
			$new_image_url = $new_image_data[0];
			$this->call_hooks_rename_url( $post, $orig_image_url, $new_image_url );
			if ( !empty( $meta['sizes'] ) ) {
				foreach ( $meta['sizes'] as $size => $meta_size ) {
					$orig_image_url = $orig_image_urls[$size];
					$new_image_data = wp_get_attachment_image_src( $id, $size );
					$new_image_url = $new_image_data[0];
					$this->call_hooks_rename_url( $post, $orig_image_url, $new_image_url );
				}
			}
		}
		else {
			$new_attachment_url = wp_get_attachment_url( $id );
			$this->call_hooks_rename_url( $post, $orig_attachment_url, $new_attachment_url );
		}

		do_action( 'carrot_bunnycdn_incoom_plugin_media_renamed', $post, $old_filepath, $new_filepath, false );
		return true;
	}
	
	public function undo( $mediaId ) {
		$original_filename = get_post_meta( $mediaId, '_original_filename', true );
		if ( empty( $original_filename ) ) {
			return true;
		}
		$res = $this->rename( $mediaId, $original_filename, true );
		if (!!$res) {
			delete_post_meta( $mediaId, '_original_filename' );
		}
		return $res;
	}

	public function rename( $media, $manual_filename = null, $undo = false ) {
		$id = null;
		$post = null;

		// This filter permits developers to allow or not the renaming of certain files.
		$allowed = apply_filters( 'carrot_bunnycdn_incoom_plugin_allow_rename', true, $media, $manual_filename );
		if ( !$allowed ) {
			return $post;
		}

		// Check the arguments
		if ( is_numeric( $media ) ) {
			$id = $media;
			$post = get_post( $media, ARRAY_A );
		}
		else if ( is_array( $media ) ) {
			$id = $media['ID'];
			$post = $media;
		}
		else {
			die( 'Media File Renamer: rename() requires the ID or the array for the media.' );
		}

		$force_rename = apply_filters( 'carrot_bunnycdn_incoom_plugin_force_rename', false );

		// Check attachment
		$need_rename = $this->check_attachment( $post, $output, $manual_filename );
		$this->log( "Check Rename Media: " . $post['ID'] );
		if ( !$need_rename ) {
			delete_post_meta( $id, '_require_file_renaming' );
			return $post;
		}

		$this->log(print_r($output, true));
		// Prepare the variables
		$old_filepath = $output['current_filepath'];
		$case_issue = $output['case_issue'];
		$new_filepath = $output['desired_filepath'];
		$new_filename = $output['proposed_filename'];
		$manual = $output['manual'] || !empty( $manual_filename );
		$path_parts = carrot_bunnycdn_incoom_plugin_pathinfo( $old_filepath );
		$directory = $path_parts['dirname']; // Directory where the files are, under 'uploads', such as '2011/01'
		$old_filename = $path_parts['basename']; // 'whatever.jpeg'
		// Get old extension and new extension
		$old_ext = $path_parts['extension'];
		$new_ext = $old_ext;
		if ( $manual_filename ) {
			$pp = carrot_bunnycdn_incoom_plugin_pathinfo( $manual_filename );
			$new_ext = $pp['extension'];
		}
		$noext_old_filename = $this->str_replace( '.' . $old_ext, '', $old_filename ); // Old filename without extension
		$noext_new_filename = $this->str_replace( '.' . $old_ext, '', $new_filename ); // New filename without extension


		$this->log( "1 Rename Media: " . $old_filename );
		$this->log( "2 New file will be: " . $new_filename );

		// Check for issues with the files
		if ( !file_exists( $old_filepath ) ) {
			$this->log( "The original file ($old_filepath) cannot be found." );
			return $post;
		}

		// Get the attachment meta
		$meta = wp_get_attachment_metadata( $id );

		// Get the information about the original image
		// (which means the current file is a rescaled version of it)
		$is_scaled_image = isset( $meta['original_image'] ) && !empty( $meta['original_image'] );
		$original_is_ideal = $is_scaled_image ? $new_filename === $meta['original_image'] : false;

		if ( !$original_is_ideal && !$case_issue && !$force_rename && file_exists( $new_filepath ) ) {
			$this->log( "The new file already exists ($new_filepath). It is not a case issue. Renaming cancelled." );
			return $post;
		}

		// Keep the original filename (that's for the "Undo" feature)
		$original_filename = get_post_meta( $id, '_original_filename', true );
		if ( empty( $original_filename ) )
			add_post_meta( $id, '_original_filename', $old_filename, true );

		// Support for the original image if it was "-rescaled".
		// We should rename the -rescaled image first, as it could cause an issue
		// if renamed after the main file. In fact, the original file might have already
		// the best filename and evidently, the "-rescaled" one not.
		if ( $is_scaled_image ) {
			$meta_old_filename = $meta['original_image'];
			$meta_old_filepath = trailingslashit( $directory ) . $meta_old_filename;
			// In case of the undo, since we do not have the actual real original filename for that un-scaled image,
			// we make sure the -scaled part of the original filename is not used (that could bring some confusion otherwise).
			$meta_new_filename = preg_replace( '/\-scaled$/', '', $noext_new_filename ) . '-mfrh-original.' . $new_ext;
			$meta_new_filepath = trailingslashit( $directory ) . $meta_new_filename;
			if ( !$this->rename_file( $meta_old_filepath, $meta_new_filepath, $case_issue ) && !$force_rename ) {
				$this->log( "🚫 File $meta_old_filepath ➡️ $meta_new_filepath" );
				return $post;
			}
			// Manual Rename also uses the new extension (if it was not stripped to avoid user mistake)
			if ( $force_rename && !empty( $new_ext ) ) {
				$meta_new_filename = $this->str_replace( $old_ext, $new_ext, $meta_new_filename );
			}
			$this->log( "3 File $old_filepath ➡️ $new_filepath" );
			do_action( 'carrot_bunnycdn_incoom_plugin_path_renamed', $post, $old_filepath, $new_filepath );
			$meta['original_image'] = $meta_new_filename;
		}

		// Rename the main media file.
		if ( !$this->rename_file( $old_filepath, $new_filepath, $case_issue ) && !$force_rename ) {
			$this->log( "4 File $old_filepath ➡️ $new_filepath" );
			return $post;
		}
		$this->log( "5 File $old_filepath ➡️ $new_filepath" );

		// Update key
		$old_attachment_key = get_post_meta( $id, '_wp_attached_file', true );
		$new_attachment_key = str_replace($old_filename, $new_filename, $old_attachment_key);
		update_post_meta( $id, '_wp_attached_file', $new_attachment_key );

		do_action( 'carrot_bunnycdn_incoom_plugin_path_renamed', $post, $old_filepath, $new_filepath );

		if ( $meta ) {
			if ( isset( $meta['file'] ) && !empty( $meta['file'] ) )
				$meta['file'] = $this->str_replace( $noext_old_filename, $noext_new_filename, $meta['file'] );
			if ( isset( $meta['url'] ) && !empty( $meta['url'] ) && strlen( $meta['url'] ) > 4 )
				$meta['url'] = $this->str_replace( $noext_old_filename, $noext_new_filename, $meta['url'] );
			else
				$meta['url'] = $noext_new_filename . '.' . $old_ext;
		}

		// Better to check like this rather than with wp_attachment_is_image
		// PDFs also have thumbnails now, since WP 4.7
		$has_thumbnails = isset( $meta['sizes'] );

		// Loop through the different sizes in the case of an image, and rename them.
		if ( $has_thumbnails ) {

			// In the case of a -scaled image, we need to update the next_old_filename.
			// next_old_filename is based on the filename of the main file, but since
			// it contains '-scaled' but not its thumbnails, we need to modify it here.
			// $noext_new_filename is to support this in case of undo.
			if ( $is_scaled_image ) {
				$noext_new_filename = preg_replace( '/\-scaled$/', '', $noext_new_filename );
				$noext_old_filename = preg_replace( '/\-scaled$/', '', $noext_old_filename );
			}

			$orig_image_urls = array();
			$orig_image_data = wp_get_attachment_image_src( $id, 'full' );
			$orig_image_urls['full'] = $orig_image_data[0];
			foreach ( $meta['sizes'] as $size => $meta_size ) {
				if ( !isset($meta['sizes'][$size]['file'] ) )
					continue;
				$meta_old_filename = $meta['sizes'][$size]['file'];
				$meta_old_filepath = trailingslashit( $directory ) . $meta_old_filename;
				$meta_new_filename = $this->str_replace( $noext_old_filename, $noext_new_filename, $meta_old_filename );

				// Manual Rename also uses the new extension (if it was not stripped to avoid user mistake)
				if ( $force_rename && !empty( $new_ext ) ) {
					$meta_new_filename = $this->str_replace( $old_ext, $new_ext, $meta_new_filename );
				}

				$meta_new_filepath = trailingslashit( $directory ) . $meta_new_filename;
				$orig_image_data = wp_get_attachment_image_src( $id, $size );
				$orig_image_urls[$size] = $orig_image_data[0];

				// Double check files exist before trying to rename.
				if ( $force_rename || ( file_exists( $meta_old_filepath ) && 
						( ( !file_exists( $meta_new_filepath ) ) || is_writable( $meta_new_filepath ) ) ) ) {
					// WP Retina 2x is detected, let's rename those files as well
					if ( function_exists( 'wr2x_get_retina' ) ) {
						$wr2x_old_filepath = $this->str_replace( '.' . $old_ext, '@2x.' . $old_ext, $meta_old_filepath );
						$wr2x_new_filepath = $this->str_replace( '.' . $new_ext, '@2x.' . $new_ext, $meta_new_filepath );
						if ( file_exists( $wr2x_old_filepath )
							&& ( ( !file_exists( $wr2x_new_filepath ) ) || is_writable( $wr2x_new_filepath ) ) ) {

							// Rename retina file
							if ( !$this->rename_file( $wr2x_old_filepath, $wr2x_new_filepath, $case_issue ) && !$force_rename ) {
								$this->log( "6 Retina $wr2x_old_filepath ➡️ $wr2x_new_filepath" );
								return $post;
							}
							$this->log( "7 Retina $wr2x_old_filepath ➡️ $wr2x_new_filepath" );
							do_action( 'carrot_bunnycdn_incoom_plugin_path_renamed', $post, $wr2x_old_filepath, $wr2x_new_filepath );
						}
					}

					// Rename meta file
					if ( !$this->rename_file( $meta_old_filepath, $meta_new_filepath, $case_issue ) && !$force_rename ) {
						$this->log( "8 File $meta_old_filepath ➡️ $meta_new_filepath" );
						return $post;
					}

					$meta['sizes'][$size]['file'] = $meta_new_filename;
					foreach ( $meta['sizes'] as $s => $m ) {
						// Detect if another size has exactly the same filename
						if ( !isset( $meta['sizes'][$s]['file'] ) )
							continue;
						if ( $meta['sizes'][$s]['file'] ==  $meta_old_filename ) {
							$this->log( "✅ Updated $s based on $size, as they use the same file (probably same size)." );
							$meta['sizes'][$s]['file'] = $meta_new_filename;
						}
					}

					// Success, call other plugins
					$this->log( "9 File $meta_old_filepath ➡️ $meta_new_filepath" );
					do_action( 'carrot_bunnycdn_incoom_plugin_path_renamed', $post, $meta_old_filepath, $meta_new_filepath );

				}
			}
		}
		else {
			$orig_attachment_url = wp_get_attachment_url( $id );
		}

		// This media doesn't require renaming anymore
		delete_post_meta( $id, '_require_file_renaming' );

		// If it was renamed manually (including undo), lock the file
		if ( $manual )
			add_post_meta( $id, '_manual_file_renaming', true, true );

		// Update metadata
		if ( $meta )
			wp_update_attachment_metadata( $id, $meta );
		update_attached_file( $id, $new_filepath );

		// I wonder about cleaning the cache for this media. It might have no impact, and will not reset the cache for the posts using this media anyway, and it adds processing time. I keep it for now, but there might be something better to do.
		clean_post_cache( $id );

		// Rename slug/permalink
		//if ( get_option( "carrot_bunnycdn_incoom_plugin_rename_slug" ) ) {
			$oldslug = $post['post_name'];
			$info = carrot_bunnycdn_incoom_plugin_pathinfo( $new_filepath );
			$newslug = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $info['basename'] );
			$post['post_name'] = $newslug;
			if ( wp_update_post( $post ) )
				$this->log( "🚀 Slug $oldslug ➡️ $newslug" );
		//}

		// Call the actions so that the plugin's plugins can update everything else (than the files)
		if ( $has_thumbnails ) {
			$orig_image_url = $orig_image_urls['full'];
			$new_image_data = wp_get_attachment_image_src( $id, 'full' );
			$new_image_url = $new_image_data[0];
			$this->call_hooks_rename_url( $post, $orig_image_url, $new_image_url );
			if ( !empty( $meta['sizes'] ) ) {
				foreach ( $meta['sizes'] as $size => $meta_size ) {
					$orig_image_url = $orig_image_urls[$size];
					$new_image_data = wp_get_attachment_image_src( $id, $size );
					$new_image_url = $new_image_data[0];
					$this->call_hooks_rename_url( $post, $orig_image_url, $new_image_url );
				}
			}
		}
		else {
			$new_attachment_url = wp_get_attachment_url( $id );
			$this->call_hooks_rename_url( $post, $orig_attachment_url, $new_attachment_url );
		}

		// HTTP REFERER set to the new media link
		if ( isset( $_REQUEST['_wp_original_http_referer'] ) &&
			strpos( $_REQUEST['_wp_original_http_referer'], '/wp-admin/' ) === false ) {
			$_REQUEST['_wp_original_http_referer'] = get_permalink( $id );
		}

		do_action( 'carrot_bunnycdn_incoom_plugin_media_renamed', $post, $old_filepath, $new_filepath, $undo );
		return $post;
	}

	/**
	 * Locks a post to be manual-rename only
	 * @param int|WP_Post $post The post to lock
	 * @return True on success, false on failure
	 */
	public function lock( $post ) {
		//TODO: We should probably only take an ID as the argument
		$id = $post instanceof WP_Post ? $post->ID : $post;
		delete_post_meta( $id, '_require_file_renaming' );
		update_post_meta( $id, '_manual_file_renaming', true, true );
		return true;
	}

	/**
	 * Unlocks a locked post
	 * @param int|WP_Post $post The post to unlock
	 * @return True on success, false on failure
	 */
	public function unlock( $post ) {
		delete_post_meta( $post instanceof WP_Post ? $post->ID : $post, '_manual_file_renaming' );
		return true;
	}

	/**
	 * Determines whether a post is locked
	 * @param int|WP_Post $post The post to check
	 * @return Boolean
	 */
	public function is_locked( $post ) {
		return get_post_meta( $post instanceof WP_Post ? $post->ID : $post, '_manual_file_renaming', true ) === true;
	}
	
}
