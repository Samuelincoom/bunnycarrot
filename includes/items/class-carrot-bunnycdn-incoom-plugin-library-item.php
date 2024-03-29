<?php
class carrot_bunnycdn_incoom_plugin_Library_Item extends carrot_bunnycdn_incoom_plugin_Item {
	/**
	 * Source type name
	 *
	 * @var string
	 */
	protected static $source_type_name = 'Media Library Item';

	/**
	 * Internal source type identifier
	 *
	 * @var string
	 */
	protected static $source_type = 'media-library';

	/**
	 * Table that corresponds to this item type
	 *
	 * @var string
	 */
	protected static $source_table = 'posts';

	/**
	 * Foreign key (if any) in the $source_table
	 *
	 * @var string
	 */
	protected static $source_fk = 'id';

	private static $attachment_counts = array();
	private static $attachment_count_skips = array();

	/**
	 * Item constructor.
	 *
	 * @param string $provider              Storage provider key name, e.g. "aws".
	 * @param string $region                Region for item's bucket.
	 * @param string $bucket                Bucket for item.
	 * @param string $path                  Key path for item (full sized if type has thumbnails etc).
	 * @param bool   $is_private            Is the object private in the bucket.
	 * @param int    $source_id             ID that source has.
	 * @param string $source_path           Path that source uses, could be relative or absolute depending on source.
	 * @param string $original_filename     An optional filename with no path that was previously used for the item.
	 * @param array  $extra_info            An optional associative array of extra data to be associated with the item.
	 *                                      Recognised keys:
	 *                                      'objects' => array of ...
	 *                                      -- 'thumbnail' => array of ...
	 *                                      -- -- 'source_file' => 'image-150x150.png'
	 *                                      -- -- 'is_private'  => false
	 *                                      'private_prefix' => 'private/'
	 *                                      For backwards compatibility, if a simple array is supplied it is treated as
	 *                                      private thumbnail sizes that should be private objects in the bucket.
	 * @param int    $id                    Optional Item record ID.
	 * @param int    $originator            Optional originator of record from ORIGINATORS const.
	 * @param bool   $is_verified           Optional flag as to whether Item's objects are known to exist.
	 * @param bool   $use_object_versioning Optional flag as to whether path prefix should use Object Versioning if type allows it.
	 */
	public function __construct(
		$provider,
		$region,
		$bucket,
		$path,
		$is_private,
		$source_id,
		$source_path,
		$original_filename = null,
		$extra_info = array(),
		$id = null,
		$originator = 0,
		$is_verified = true,
		$use_object_versioning = self::CAN_USE_OBJECT_VERSIONING
	) {
		// For Media Library items, the source path should be relative to the Media Library's uploads directory.
		$uploads = wp_upload_dir();

		if ( false === $uploads['error'] && 0 === strpos( $source_path, $uploads['basedir'] ) ) {
			$source_path = static::unleadingslashit( substr( $source_path, strlen( $uploads['basedir'] ) ) );
		}

		$objects        = array();
		$private_prefix = null;

		// Ensure re-hydration is clean.
		if ( ! empty( $extra_info ) && is_array( $extra_info ) ) {
			if ( isset( $extra_info['private_prefix'] ) ) {
				$private_prefix = $extra_info['private_prefix'];
			}
			if ( isset( $extra_info['objects'] ) ) {
				$objects = $extra_info['objects'];
			}
		}

		$extra_info = array(
			'objects'        => $objects,
			'private_prefix' => $private_prefix,
		);

		parent::__construct( $provider, $region, $bucket, $path, $is_private, $source_id, $source_path, $original_filename, $extra_info, $id, $originator, $is_verified, $use_object_versioning );
	}

	/**
	 * Setter for extra_info value.
	 *
	 * @param array $extra_info
	 */
	public function set_extra_info( $extra_info ) {
		$this->extra_info = $extra_info;
	}

	/**
	 * Synthesize a data struct to be used when passing information
	 * about the current item to filters that assume the item is a
	 * media library item.
	 *
	 * @return array
	 */
	public function item_data_for_acl_filter() {
		$item_data               = parent::item_data_for_acl_filter();
		$media_library_item_data = wp_get_attachment_metadata( $this->source_id(), true );

		// Copy over specific elements only as i.e. 'size' may not be populated yet in $media_library_item_data
		foreach ( array( 'file', 'original_image', 'image_meta' ) as $element ) {
			if ( isset( $media_library_item_data[ $element ] ) ) {
				$item_data[ $element ] = $media_library_item_data[ $element ];
			}
		}

		return $item_data;
	}

	/**
	 * Create a new item from the source id.
	 *
	 * @param int   $source_id
	 * @param array $options
	 *
	 * @return Item|WP_Error
	 */
	public static function create_from_source_id( $source_id, $options = array() ) {
		if ( empty( $source_id ) ) {
			return new WP_Error(
				'exception',
				__( 'Empty Attachment ID passed to ' . __FUNCTION__, 'carrot-bunnycdn-incoom-plugin' )
			);
		}

		$default_options = array(
			'originator'            => carrot_bunnycdn_incoom_plugin_Item::ORIGINATORS['standard'],
			'is_verified'           => true,
			'use_object_versioning' => static::can_use_object_versioning(),
		);

		$options = array_merge( $default_options, $options );

		if ( ! in_array( $options['originator'], self::ORIGINATORS ) ) {
			return new WP_Error(
				'exception',
				__( 'Invalid Originator passed to ' . __FUNCTION__, 'carrot-bunnycdn-incoom-plugin' )
			);
		}

		/*
		 * Derive local path.
		 */

		// Verify that get_attached_file will not blow up as it does not check the data it manipulates.
		$attached_file_meta = get_post_meta( $source_id, '_wp_attached_file', true );
		if ( ! is_string( $attached_file_meta ) ) {
			return new WP_Error(
				'exception',
				sprintf( __( 'Media Library item with ID %d has damaged meta data', 'carrot-bunnycdn-incoom-plugin' ), $source_id )
			);
		}
		unset( $attached_file_meta );

		$source_path = get_attached_file( $source_id, true );

		// Check for valid "full" file path otherwise we'll not be able to create offload path or download in the future.
		if ( empty( $source_path ) ) {
			return new WP_Error(
				'exception',
				sprintf( __( 'Media Library item with ID %d does not have a valid file path', 'carrot-bunnycdn-incoom-plugin' ), $source_id )
			);
		}

		/** @var array|false|WP_Error $attachment_metadata */
		$attachment_metadata = wp_get_attachment_metadata( $source_id, true );
		if ( is_wp_error( $attachment_metadata ) ) {
			return $attachment_metadata;
		}

		// Initialize extra info array with empty values
		$extra_info = array(
			'private_prefix' => null,
			'objects'        => array(),
		);

		// There may be an original image that can override the default original filename.
		$original_filename = empty( $attachment_metadata['original_image'] ) ? null : $attachment_metadata['original_image'];

		$file_paths = carrot_bunnycdn_incoom_plugin_Utils::get_attachment_file_paths( $source_id, false, $attachment_metadata );
		foreach ( $file_paths as $size => $size_file_path ) {
			if ( $size === 'file' ) {
				continue;
			}

			$new_object = array(
				'source_file' => wp_basename( $size_file_path ),
				'is_private'  => false,
			);

			$extra_info['objects'][ $size ] = $new_object;
		}

		return new self(
			'',
			'',
			'',
			'',
			false,
			$source_id,
			$source_path,
			$original_filename,
			$extra_info,
			null,
			$options['originator'],
			$options['is_verified'],
			$options['use_object_versioning']
		);
	}

	/**
	 * Get attachment local URL.
	 *
	 * This is partly a direct copy of wp_get_attachment_url() from /wp-includes/post.php
	 * as we filter the URL and can't remove this filter using the current implementation
	 * of globals for class instances.
	 *
	 * @param string|null $object_key
	 *
	 * @return string|false
	 */
	public function get_local_url( $object_key = null ) {
		$url = '';

		// Get attached file.
		if ( $file = get_post_meta( $this->source_id(), '_wp_attached_file', true ) ) {
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

		if ( ! empty( $object_key ) ) {
			$meta = get_post_meta( $this->source_id(), '_wp_attachment_metadata', true );
			if ( empty( $meta['sizes'][ $object_key ]['file'] ) ) {
				// No alternative sizes available, return
				return $url;
			}

			$url = str_replace( wp_basename( $url ), $meta['sizes'][ $object_key ]['file'], $url );
		}

		return $url;
	}

	/**
	 * (Re)initialize the static cache used for speeding up queries.
	 */
	public static function init_cache() {
		parent::init_cache();

		self::$attachment_counts      = array();
		self::$attachment_count_skips = array();
	}

	/**
	 * Get the item based on source id.
	 *
	 * @param int $source_id
	 *
	 * @return bool
	 */
	public static function get_by_source_id( $source_id ) {
		$carrot_item = parent::get_by_source_id( $source_id );

		if ( ! $carrot_item ) {
			$provider_object = static::_legacy_get_attachment_provider_info( $source_id );

			if ( is_array( $provider_object ) ) {
				$carrot_item = static::_legacy_provider_info_to_item( $source_id, $provider_object );
			}
		}

		return $carrot_item;
	}

	/**
	 * Full key (path) for given file that belongs to offloaded attachment.
	 *
	 * If no filename given, full sized path returned.
	 * Path is prepended with private prefix if size associated with filename is private,
	 * and a private prefix has been assigned to offload.
	 *
	 * @param string|null $filename
	 *
	 * @return string
	 */
	public function key( $filename = null ) {
		// Public full path.
		if ( empty( $filename ) && empty( $this->private_prefix() ) ) {
			return parent::path();
		}

		if ( empty( $filename ) ) {
			$filename = wp_basename( parent::path() );
		}

		if ( ! empty( $this->private_prefix() ) ) {
			$size = $this->get_object_key_from_filename( $filename );

			// Private path.
			if ( $this->is_private( $size ) ) {
				return $this->private_prefix() . $this->normalized_path_dir() . $filename;
			}
		}

		// Public path.
		return $this->normalized_path_dir() . $filename;
	}

	/**
	 * Get absolute source file paths for offloaded files.
	 *
	 * @return array Associative array of object_key => path
	 */
	public function full_source_paths() {
		return array_intersect_key( carrot_bunnycdn_incoom_plugin_Utils::get_attachment_file_paths( $this->source_id(), false ), $this->objects() );
	}

	/**
	 * Get ACL for intermediate size.
	 *
	 * @param string      $object_key Size name
	 * @param string|null $bucket     Optional bucket that ACL is potentially to be used with.
	 *
	 * @return string|null
	 */
	public function get_acl_for_object_key( $object_key, $bucket = null ) {
		return null;
	}

	/**
	 * Count attachments on current site.
	 *
	 * @param bool $skip_transient Whether to force database query and skip transient, default false
	 * @param bool $force          Whether to force database query and skip static cache, implies $skip_transient, default false
	 *
	 * @return array Keys:
	 *               total: Total media count for site (current blog id)
	 *               offloaded: Count of offloaded media for site (current blog id)
	 *               not_offloaded: Difference between total and offloaded
	 */
	public static function count_items( $skip_transient = false, $force = false ) {
		global $wpdb;

		$keyTransient = carrot_bunnycdn_incoom_plugin_CACHE_KEY_ATTACHMENT_COUNTS;
		$cache_value = incoom_carrot_bunnycdn_incoom_plugin_get_cache_item($keyTransient);
		if ( !empty($cache_value) ) {
			return $cache_value;
		}else{

			// Simplified media counting
			$sql              = "SELECT count(id) FROM {$wpdb->posts} WHERE post_type = 'attachment'";
			$attachment_count = (int) $wpdb->get_var( $sql );

			// Count Removed from server
			$local_removed = carrot_bunnycdn_incoom_plugin_count_items_local_removed(static::$source_type);

			// Copy to server from S3
			$copy_from_cloud = carrot_bunnycdn_incoom_plugin_count_items_downloaded_to_server_from_cloud(static::$source_type);

			// Remove from S3
			$cloud_removed = carrot_bunnycdn_incoom_plugin_count_items_remove_file_cloud(static::$source_type);

			$sql             = 'SELECT count(id) FROM ' . static::items_table() . ' WHERE source_type = %s';
			$sql             = $wpdb->prepare( $sql, static::$source_type );
			$offloaded_count = (int) $wpdb->get_var( $sql );

			$result['total']         		= $attachment_count;

			$result['local_removed'] 		= $local_removed;
			$result['not_local_removed'] 	= max( $attachment_count - $local_removed, 0 );

			$result['copy_from_cloud'] 		= $copy_from_cloud;
			$result['not_copy_from_cloud'] 	= max( $attachment_count - $copy_from_cloud, 0 );

			$result['cloud_removed'] 		= $cloud_removed;
			$result['not_cloud_removed'] 	= max( $attachment_count - $cloud_removed, 0 );

			$result['offloaded']     		= $offloaded_count;
			$result['not_offloaded'] 		= max( $attachment_count - $offloaded_count, 0 );

			ksort( $result );

			incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($keyTransient, $result);

			// One way or another we've skipped the transient.
			self::$attachment_count_skips[ $keyTransient ] = true;

			self::$attachment_counts[ $keyTransient ] = $result;

			return $result;
		}
	}

	/**
	 * Get an array of un-managed source_ids in descending order.
	 *
	 * While source id isn't strictly unique, it is by source type, which is always used in queries based on called class.
	 *
	 * @param int  $upper_bound Returned source_ids should be lower than this, use null/0 for no upper bound.
	 * @param int  $limit       Maximum number of source_ids to return. Required if not counting.
	 * @param bool $count       Just return a count of matching source_ids? Negates $limit, default false.
	 *
	 * @return array|int
	 */
	public static function get_missing_source_ids( $upper_bound, $limit, $count = false ) {
		global $wpdb;

		$args = array( static::$source_type );

		if ( $count ) {
			$sql = 'SELECT COUNT(DISTINCT posts.ID)';
		} else {
			$sql = 'SELECT DISTINCT posts.ID';
		}

		$sql .= "
			FROM {$wpdb->posts} AS posts
			WHERE posts.post_type = 'attachment'
			AND posts.ID NOT IN (
			    SELECT items.source_id
				FROM " . static::items_table() . " AS items
				WHERE items.source_type = %s
				AND items.source_id = posts.ID
			)
		";

		if ( ! empty( $upper_bound ) ) {
			$sql    .= ' AND posts.ID < %d';
			$args[] = $upper_bound;
		}

		/**
		 * Allow users to exclude certain MIME types from attachments to upload.
		 *
		 * @param array
		 */
		$ignored_mime_types = apply_filters( 'carrot_ignored_mime_types', array() );
		if ( is_array( $ignored_mime_types ) && ! empty( $ignored_mime_types ) ) {
			$ignored_mime_types = array_map( 'sanitize_text_field', $ignored_mime_types );
			$sql                .= " AND posts.post_mime_type NOT IN ('" . implode( "','", $ignored_mime_types ) . "')";
		}

		if ( ! $count ) {
			if($limit > 0){
				$sql    .= ' ORDER BY posts.ID DESC LIMIT %d';
				$args[] = $limit;
			}
		}

		$sql = $wpdb->prepare( $sql, $args );

		if ( $count ) {
			return (int) $wpdb->get_var( $sql );
		} else {
			return array_map( 'intval', $wpdb->get_col( $sql ) );
		}
	}

	public static function verify_missing_source_ids( $limit, $count = false ) {
		$ids = [];
		$items = self::get_missing_source_ids(null, $limit);
		if(count($items) > 0){
			$upload_dir = wp_upload_dir();
			$chunks = array_chunk( $items, 50 );
			foreach ( $chunks as $source_ids ) {
				foreach ( $source_ids as $post_id ) {
					$key = get_post_meta( $post_id, '_wp_attached_file', true);
					if($key){
						if(file_exists( "{$upload_dir['basedir']}/{$key}" )){
							$ids[] = $post_id;
						}
					}
				}
			}
		}

		if($count){
			return count($ids);
		}

		return $ids;
	}

	/**
	 * Finds Media Library items with same source_path and sets them as offloaded.
	 */
	public function offload_duplicate_items() {
		global $wpdb;

		$sql = $wpdb->prepare(
			"
				SELECT m.post_id
				FROM " . $wpdb->postmeta . " AS m
				LEFT JOIN " . $wpdb->posts . " AS p ON m.post_id = p.ID AND p.`post_type` = 'attachment'
				WHERE m.meta_key = '_wp_attached_file'
				AND m.meta_value = %s
				AND m.post_id != %d
				AND m.post_id NOT IN (
					SELECT i.source_id
					FROM " . static::items_table() . " AS i
					WHERE i.source_type = %s
					AND i.source_id = m.post_id
				)
				;
			"
			, $this->source_path()
			, $this->source_id()
			, static::$source_type
		);

		$results = $wpdb->get_results( $sql );

		// Nothing found, shortcut out.
		if ( 0 === count( $results ) ) {
			return;
		}

		foreach ( $results as $result ) {
			$carrot_item = new carrot_bunnycdn_incoom_plugin_Item(
				$this->provider(),
				$this->region(),
				$this->bucket(),
				$this->path(),
				$this->is_private(),
				$result->post_id,
				$this->source_path(),
				wp_basename( $this->original_source_path() ),
				$this->extra_info()
			);
			$carrot_item->save();
			$carrot_item->duplicate_filesize_total( $this->source_id() );
		}
	}

	/**
	 * Returns a link to the items edit page in WordPress
	 *
	 * @param object $error
	 *
	 * @return object|null Object containing url and link text
	 */
	public static function admin_link( $error ) {
		return (object) array(
			'url'  => get_edit_post_link( $error->source_id ),
			'text' => __( 'Edit', 'carrot-bunnycdn-incoom-plugin' ),
		);
	}

	/**
	 * Return a year/month string for the item
	 *
	 * @return string
	 */
	protected function get_item_time() {
		return $this->get_attachment_folder_year_month();
	}

	/**
	 * Get the year/month string for attachment's upload.
	 *
	 * Fall back to post date if attached, otherwise current date.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	private function get_attachment_folder_year_month( $data = array() ) {
		if ( empty( $data ) ) {
			$data = wp_get_attachment_metadata( $this->source_id(), true );
		}

		if ( isset( $data['file'] ) ) {
			$time = $this->get_folder_time_from_url( $data['file'] );
		}

		if ( empty( $time ) && ( $local_url = wp_get_attachment_url( $this->source_id() ) ) ) {
			$time = $this->get_folder_time_from_url( $local_url );
		}

		if ( empty( $time ) ) {
			$time = date( 'Y/m' );

			if ( ! ( $attach = get_post( $this->source_id() ) ) ) {
				return $time;
			}

			if ( ! $attach->post_parent ) {
				return $time;
			}

			if ( ! ( $post = get_post( $attach->post_parent ) ) ) {
				return $time;
			}

			if ( substr( $post->post_date_gmt, 0, 4 ) > 0 ) {
				return date( 'Y/m', strtotime( $post->post_date_gmt . ' +0000' ) );
			}
		}

		return $time;
	}

	/**
	 * Get the upload folder time from given URL
	 *
	 * @param string $url
	 *
	 * @return null|string YYYY/MM format.
	 */
	private function get_folder_time_from_url( $url ) {
		if ( ! is_string( $url ) ) {
			return null;
		}

		preg_match( '@[0-9]{4}/[0-9]{2}/@', $url, $matches );

		if ( isset( $matches[0] ) ) {
			return untrailingslashit( $matches[0] );
		}

		return null;
	}

	/**
	 * Cleanup filesize and carrot_filesize_total metadata on the underlying media library item
	 * after downloading a file back from the bucket
	 */
	public function update_filesize_after_download_local() {
		$data = get_post_meta( $this->source_id(), '_wp_attachment_metadata', true );

		/*
		 * Audio and video have a filesize added to metadata by default, but images and anything else don't.
		 * Note: Could have used `wp_generate_attachment_metadata` here to test whether default metadata has 'filesize',
		 * but it not only has side effects it also does a lot of work considering it's not a huge deal for this entry to hang around.
		 */
		if (
			! empty( $data ) &&
			( empty( $data['mime_type'] ) ||
			  0 === strpos( $data['mime_type'], 'image/' ) ||
			  ! ( 0 === strpos( $data['mime_type'], 'audio/' ) || 0 === strpos( $data['mime_type'], 'video/' ) ) )
		) {
			unset( $data['filesize'] );
			update_post_meta( $this->source_id(), '_wp_attachment_metadata', $data );
		}

		delete_post_meta( $this->source_id(), 'carrot_filesize_total' );
	}

	/**
	 * Duplicate 'carrot_filesize_total' meta if it exists for an attachment.
	 *
	 * @param int $attachment_id
	 */
	public function duplicate_filesize_total( $attachment_id ) {
		if ( ! ( $filesize = get_post_meta( $attachment_id, 'carrot_filesize_total', true ) ) ) {
			// No filesize to duplicate.
			return;
		}

		update_post_meta( $this->source_id(), 'carrot_filesize_total', $filesize );
	}

	/**
	 * If another item in current site shares full size *local* paths, only remove remote files not referenced by duplicates.
	 * We reference local paths as they should be reflected one way or another remotely, including backups.
	 *
	 * @params Item  $carrot_item
	 * @params array $paths
	 */
	public function remove_duplicate_paths( carrot_bunnycdn_incoom_plugin_Item $carrot_item, $paths ) {
		$full_size_paths        = carrot_bunnycdn_incoom_plugin_Utils::fullsize_paths( $carrot_item->full_source_paths() );
		$carrot_items_with_paths = static::get_by_source_path( $full_size_paths, array( $carrot_item->source_id() ), false );

		$duplicate_paths = array();

		foreach ( $carrot_items_with_paths as $carrot_item_with_path ) {
			/* @var Media_Library_Item $carrot_item_with_path */
			$duplicate_paths += array_values( carrot_bunnycdn_incoom_plugin_Utils::get_attachment_file_paths( $carrot_item_with_path->source_id(), false, false, true ) );
		}

		if ( ! empty( $duplicate_paths ) ) {
			$paths = array_diff( $paths, $duplicate_paths );
		}

		return $paths;
	}

	/*
	 * >>> LEGACY ROUTINES BEGIN >>>
	 */

	/**
	 * Convert the provider info array for an attachment to item object.
	 *
	 * @param int   $source_id
	 * @param array $provider_info
	 *
	 * @return bool|Media_Library_Item
	 */
	private static function _legacy_provider_info_to_item( $source_id, $provider_info ) {
		$attached_file = get_post_meta( $source_id, '_wp_attached_file', true );

		if ( is_string( $attached_file ) && ! empty( $attached_file ) ) {
			$private_sizes = array();

			if ( ! empty( $provider_info['sizes'] ) && is_array( $provider_info['sizes'] ) ) {
				$private_sizes = array_keys( $provider_info['sizes'] );
			}

			return new static(
				$provider_info['provider'],
				$provider_info['region'],
				$provider_info['bucket'],
				$provider_info['key'],
				isset( $provider_info['acl'] ) && false !== strpos( $provider_info['acl'], 'private' ),
				$source_id,
				$attached_file,
				wp_basename( $attached_file ),
				$private_sizes
			);
		}

		return false;
	}

	/**
	 * Get attachment provider info
	 *
	 * @param int $post_id
	 *
	 * @return bool|array
	 */
	private static function _legacy_get_attachment_provider_info( $post_id ) {
        $settings = carrot_bunnycdn_incoom_plugin_whichtype_settings();
		$settingProvider = isset($settings['provider']) ? $settings['provider'] : 'aws';
		$provider_object = get_post_meta( $post_id, '_incoom_carrot_bunnycdn_amazonS3_info', true );

		if ( ! empty( $provider_object ) && is_array( $provider_object ) && ! empty( $provider_object['bucket'] ) && ! empty( $provider_object['key'] ) ) {
			$provider_object = array_merge( array(
				'provider' => $settingProvider,
			), $provider_object );
		} else {
			return false;
		}

		$provider_object['region'] = static::_legacy_get_provider_object_region( $provider_object );

		if ( is_wp_error( $provider_object['region'] ) ) {
			return false;
		}

		$provider_object = apply_filters( 'carrot_get_attachment_s3_info', $provider_object, $post_id ); // Backwards compatibility

		return apply_filters( 'carrot_get_attachment_provider_info', $provider_object, $post_id );
	}

	/**
	 * Get the region of the bucket stored in the provider metadata.
	 *
	 * @param array $provider_object
	 *
	 * @return string|WP_Error - region name
	 */
	private static function _legacy_get_provider_object_region( $provider_object ) {
		if ( ! isset( $provider_object['region'] ) ) {
			list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();

			$provider_object['region'] = $Region;
		}

		return $provider_object['region'];
	}

	/*
	 * <<< LEGACY ROUTINES END <<<
	 */
}