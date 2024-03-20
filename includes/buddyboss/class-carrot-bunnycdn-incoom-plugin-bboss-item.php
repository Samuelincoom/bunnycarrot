<?php

class carrot_bunnycdn_incoom_plugin_BBoss_Item extends carrot_bunnycdn_incoom_plugin_Item {
	/**
	 * Buddy Boss images have random and unique names, object versioning not needed
	 *
	 * @var bool
	 */
	const CAN_USE_OBJECT_VERSIONING = false;

	/**
	 * sprintf pattern for creating prefix based on source_id
	 *
	 * @var string
	 */
	protected static $prefix_pattern = '';

	/**
	 * Buddy Boss images are not managed in yearmonth folders
	 *
	 * @var bool
	 */
	protected static $can_use_yearmonth = false;

	/**
	 * @var string
	 */
	protected static $folder = '';

	/**
	 * @var bool
	 */
	protected static $is_cover = false;

	/**
	 * @var bool
	 */
	protected static $is_group = false;

	/**
	 * @var array
	 */
	private static $item_counts = array();

	/**
	 * @var array
	 */
	private static $item_count_skips = array();

	/**
	 * @var int
	 */
	private static $chunk_size = 1000;

	/**
	 * Get a Buddy Boss item object from the database
	 *
	 * @param int    $source_id
	 * @param string $object_type
	 * @param string $image_type
	 *
	 * @return BBoss_Item|false
	 */
	public static function get_buddy_boss_item( $source_id, $object_type, $image_type ) {
		/** @var BBoss_Item $class */
		$class = static::get_item_class( $object_type, $image_type );
		if ( ! empty( $class ) ) {
			return $class::get_by_source_id( $source_id );
		}

		return false;
	}

	/**
	 * Get the appropriate Buddy Boss item sub class based on object and image type
	 *
	 * @param string $object_type user or group
	 * @param string $image_type  avatar or cover image
	 *
	 * @return false|string
	 */
	public static function get_item_class( $object_type, $image_type ) {
		
		$class_map = array(
			'user'  => array(
				'avatar' => 'carrot_bunnycdn_incoom_plugin_BBoss_User_Avatar',
				'cover'  => 'carrot_bunnycdn_incoom_plugin_BBoss_User_Cover',
			),
			'group' => array(
				'avatar' => 'carrot_bunnycdn_incoom_plugin_BBoss_Group_Avatar',
				'cover'  => 'carrot_bunnycdn_incoom_plugin_BBoss_Group_Cover',
			),
		);

		if ( isset( $class_map[ $object_type ][ $image_type ] ) ) {
			return $class_map[ $object_type ][ $image_type ];
		} else {
			return false;
		}
	}

	/**
	 * Create a new Buddy Boss item from the source id.
	 *
	 * @param int   $source_id
	 * @param array $options
	 *
	 * @return BBoss_Item|WP_Error
	 */
	public static function create_from_source_id( $source_id, $options = array() ) {
		$file_paths = static::get_local_files( $source_id );
		if ( empty( $file_paths ) ) {
			return new WP_Error(
				'exception',
				__( 'No local files found in ' . __FUNCTION__, 'carrot-bunnycdn-incoom-plugin' )
			);
		}

		$file_path = static::remove_size_from_filename( $file_paths[ carrot_bunnycdn_incoom_plugin_Item::primary_object_key() ] );

		$extra_info = array( 'objects' => array() );
		foreach ( $file_paths as $key => $path ) {
			$extra_info['objects'][ $key ] = array(
				'source_file' => wp_basename( $path ),
				'is_private'  => false,
			);
		}

		return new static( null, null, null, null, false, $source_id, $file_path, null, $extra_info, self::CAN_USE_OBJECT_VERSIONING );
	}

	/**
	 * Get item's new public prefix path for current settings.
	 *
	 * @param bool $use_object_versioning
	 *
	 * @return string
	 */
	public function get_new_item_prefix( $use_object_versioning = true ) {
		// Base prefix from settings
		$prefix = carrot_bunnycdn_incoom_plugin_get_object_prefix();
		// Buddy Boss specific prefix
		$buddy_boss_prefix = sprintf( static::$prefix_pattern, $this->source_id() );
		$prefix            .= carrot_bunnycdn_incoom_plugin_Utils::trailingslash_prefix( $buddy_boss_prefix );

		return $prefix;
	}

	/**
	 * Return all buddy boss file sizes from the source folder
	 *
	 * @param int $source_id
	 *
	 * @return array
	 */
	public static function get_local_files( $source_id ) {
		$basedir = bp_core_get_upload_dir( 'upload_path' );

		// Get base path and apply filters
		if ( static::$is_cover ) {
			// Call filters indirectly via bp_attachments_cover_image_upload_dir()
			$args       = array(
				'object_id'        => $source_id,
				'object_directory' => str_replace( 'buddypress/', '', static::$folder ),
			);
			$upload_dir = bp_attachments_cover_image_upload_dir( $args );
			$image_path = $upload_dir['path'];
		} else {
			// Call apply_filters directly
			$image_path  = trailingslashit( $basedir ) . trailingslashit( static::$folder ) . $source_id;
			$object_type = static::$is_group ? 'group' : 'user';
			$image_path  = apply_filters( 'bp_core_avatar_folder_dir', $image_path, $source_id, $object_type, static::$folder );
		}

		$result = array();

		if ( ! file_exists( $image_path ) ) {
			return $result;
		}

		$files = new DirectoryIterator( $image_path );

		foreach ( $files as $file ) {
			if ( $file->isDot() ) {
				continue;
			}

			$base_name = $file->getFilename();
			$file_name = substr( $file->getPathname(), strlen( $basedir ) );
			$file_name = carrot_bunnycdn_incoom_plugin_Utils::unleadingslashit( $file_name );

			if ( false !== strpos( $base_name, '-bp-cover-image' ) ) {
				$result[ carrot_bunnycdn_incoom_plugin_Item::primary_object_key() ] = $file_name;
			}
			if ( false !== strpos( $base_name, '-bpfull' ) ) {
				$result[ carrot_bunnycdn_incoom_plugin_Item::primary_object_key() ] = $file_name;
			}
			if ( false !== strpos( $base_name, '-bpthumb' ) ) {
				$result['thumb'] = $file_name;
			}
		}

		return $result;
	}

	/**
	 * Buddy Boss specific size removal from URL and convert it to a neutral
	 * (mock) file name with the correct file extension
	 *
	 * @param string $file_name The file
	 *
	 * @return string
	 */
	public static function remove_size_from_filename( $file_name ) {
		$path_info = pathinfo( $file_name );

		return trailingslashit( $path_info['dirname'] ) . 'bp.' . $path_info['extension'];
	}

	/**
	 * Count avatars and cover images on current site.
	 *
	 * @param bool $skip_transient Whether to force database query and skip transient, default false
	 * @param bool $force          Whether to force database query and skip static cache, implies $skip_transient, default false
	 *
	 * @return array Keys:
	 *               total: Total item count for site (current blog id)
	 *               offloaded: Count of offloaded items for site (current blog id)
	 *               not_offloaded: Difference between total and offloaded
	 */
	public static function count_items( $skip_transient, $force ) {
		global $wpdb;

		$transient_key = 'carrot_' . get_current_blog_id() . '_attachment_counts_' . static::$source_type;

		// Been here, done it, won't do it again!
		// Well, unless this is the first transient skip for the prefix, then we need to do it.
		if ( ! $force && ! empty( self::$item_counts[ $transient_key ] ) && ( false === $skip_transient || ! empty( self::$item_count_skips[ $transient_key ] ) ) ) {
			return self::$item_counts[ $transient_key ];
		}

		if ( $force || $skip_transient || false === ( $result = get_site_transient( $transient_key ) ) ) {
			$sql             = 'SELECT count(id) FROM ' . static::items_table() . ' WHERE source_type = %s';
			$sql             = $wpdb->prepare( $sql, static::$source_type );
			$offloaded_count = (int) $wpdb->get_var( $sql );
			$missing_count   = static::verify_missing_source_ids(0, true);

			$result = array(
				'total'         => $offloaded_count + $missing_count,
				'offloaded'     => $offloaded_count,
				'not_offloaded' => $missing_count,
			);

			set_site_transient( $transient_key, $result, 5 * MINUTE_IN_SECONDS );
			self::$item_count_skips[ $transient_key ] = true;
		}

		self::$item_counts[ $transient_key ] = $result;

		return $result;
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

		// Bail out with empty values if we are a group class and the groups component is not active
		if ( static::$is_group ) {
			$active_bp_components = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );
			if ( empty ( $active_bp_components['groups'] ) ) {
				return $count ? 0 : array();
			}
		}

		$source_table  = $wpdb->prefix . static::$source_table;
		$basedir       = bp_core_get_upload_dir( 'upload_path' );
		$dir           = trailingslashit( $basedir ) . static::$folder . '/';
		$missing_items = array();
		$missing_count = 0;

		// Establish an upper bound if needed
		if ( empty( $upper_bound ) ) {
			$sql         = "SELECT max(id) from $source_table";
			$max_id      = (int) $wpdb->get_var( $sql );
			$upper_bound = $max_id + 1;
		}

		if($limit === 0){
			$limit = $upper_bound + 1;
		}

		for ( $i = $upper_bound; $i >= 0; $i -= self::$chunk_size ) {
			$args   = array();
			$sql    = " 
			SELECT t.id as ID from $source_table as t 
              LEFT OUTER JOIN " . static::items_table() . " as i 
                ON (i.source_id = t.ID AND i.source_type=%s)";
			$args[] = static::source_type();

			$sql    .= ' WHERE i.ID IS NULL AND t.id < %d';
			$args[] = $upper_bound;
			$sql    .= ' ORDER BY t.ID DESC LIMIT %d, %d';
			$args[] = $upper_bound - $i;
			$args[] = self::$chunk_size;
			$sql    = $wpdb->prepare( $sql, $args );

			$items_without_managed_offload = array_map( 'intval', $wpdb->get_col( $sql ) );

			foreach ( $items_without_managed_offload as $item_without_managed_offload ) {
				$target = $dir . $item_without_managed_offload;
				if ( is_dir( $target ) ) {
					if ( $count ) {
						$missing_count++;
					} else {
						$missing_items[] = $item_without_managed_offload;

						// If we have enough items, bail out
						if ( count( $missing_items ) >= $limit ) {
							break 2;
						}
					}
				}
			}
		}

		// Add custom default if available for offload.
		if ( ( $count || count( $missing_items ) < $limit ) && is_dir( $dir . '0' ) ) {
			if ( ! static::get_by_source_id( 0 ) && ! empty( static::get_local_files( 0 ) ) ) {
				if ( $count ) {
					$missing_count++;
				} else {
					$missing_items[] = 0;
				}
			}
		}

		if ( $count ) {
			return $missing_count;
		} else {
			return $missing_items;
		}
	}

	public static function verify_missing_source_ids( $limit, $count = false ) {
		$ids = [];
		$items = self::get_missing_source_ids(null, $limit);
		if(count($items) > 0){
			$chunks = array_chunk( $items, 50 );
			foreach ( $chunks as $source_ids ) {
				foreach ( $source_ids as $source_id ) {
					if( !empty(static::get_local_files( $source_id )) ){
						$ids[] = $source_id;
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
	 * Setter for item's path & original path values
	 *
	 * @param $path
	 */
	public function set_path( $path ) {
		$path = static::remove_size_from_filename( $path );
		parent::set_path( $path );
		parent::set_original_path( $path );
	}

	/**
	 * Get absolute source file paths for offloaded files.
	 *
	 * @return array Associative array of object_key => path
	 */
	public function full_source_paths() {
		$basedir     = bp_core_get_upload_dir( 'upload_path' );
		$item_folder = dirname( $this->source_path() );

		$objects = $this->objects();
		$sizes   = array();
		foreach ( $objects as $size => $object ) {
			$sizes[ $size ] = trailingslashit( $basedir ) . trailingslashit( $item_folder ) . $object['source_file'];
		}

		return $sizes;
	}

	/**
	 * Get the local URL for an item
	 *
	 * @param string|null $object_key
	 *
	 * @return string
	 */
	public function get_local_url( $object_key = null ) {
		if ( static::$is_cover ) {
			return $this->get_local_cover_url( $object_key );
		} else {
			return $this->get_local_avatar_url( $object_key );
		}
	}

	/**
	 * Get the local URL for an avatar item
	 *
	 * @param string|null $object_key
	 *
	 * @return string
	 */
	protected function get_local_avatar_url( $object_key = null ) {
		$uploads = wp_upload_dir();
		$url     = trailingslashit( $uploads['baseurl'] );
		$url     .= $this->source_path( $object_key );

		return $url;
	}

	/**
	 * Get the local URL for a cover item
	 *
	 * @param string|null $object_key
	 *
	 * @return string
	 */
	protected function get_local_cover_url( $object_key = null ) {
		$uploads = wp_upload_dir();
		$url     = trailingslashit( $uploads['baseurl'] );
		$url     .= $this->source_path( $object_key );

		return $url;
	}

	/**
	 * Get the prefix pattern
	 *
	 * @return string
	 */
	public static function get_prefix_pattern() {
		return static::$prefix_pattern;
	}
}