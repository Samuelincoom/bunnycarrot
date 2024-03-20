<?php

class carrot_bunnycdn_incoom_plugin_Item {
    
	const CAN_USE_OBJECT_VERSIONING = false;
    const ITEMS_TABLE = carrot_bunnycdn_incoom_plugin_ITEMS_TABLE;
	const ORIGINATORS = array(
		'standard'      => 0,
		'metadata-tool' => 1,
	);

	protected static $source_type = 'media-library';
	protected static $source_table = 'posts';
	protected static $source_fk = 'id';

	private static $checked_table_exists = array();

	protected static $items_cache_by_id = array();
	protected static $items_cache_by_source_id = array();
	protected static $items_cache_by_path = array();
	protected static $items_cache_by_source_path = array();

	/**
	 * @var array Keys with array of fields that can be used for cache lookups.
	 */
	protected static $cache_keys = array(
		'id'          => array( 'id' ),
		'source_id'   => array( 'source_id' ),
		'path'        => array( 'path', 'original_path' ),
		'source_path' => array( 'source_path', 'original_source_path' ),
	);

	private $id;
	private $provider;
	private $region;
	private $bucket;
	private $path;
	private $original_path;
	private $is_private;
	private $source_id;
	private $source_path;
	private $original_source_path;
	private $extra_info;
	private $originator;
	private $is_verified;

    /**
	 * Item constructor.
	 *
	 * @param string $provider          Storage provider key name, e.g. "aws".
	 * @param string $region            Region for item's bucket.
	 * @param string $bucket            Bucket for item.
	 * @param string $path              Key path for item (full sized if type has thumbnails etc).
	 * @param bool   $is_private        Is the object private in the bucket.
	 * @param int    $source_id         ID that source has.
	 * @param string $source_path       Path that source uses, could be relative or absolute depending on source.
	 * @param string $original_filename An optional filename with no path that was previously used for the item.
	 * @param array  $extra_info        An optional associative array of extra data to be associated with the item.
	 *                                  Recognised keys:
	 *                                  'private_sizes' => ['thumbnail', 'medium', ...]
	 *                                  'private_prefix' => 'private/'
	 *                                  For backwards compatibility, if a simple array is supplied it is treated as
	 *                                  private thumbnail sizes that should be private objects in the bucket.
	 * @param int    $id                Optional Item record ID.
	 * @param int    $originator        Optional originator of record from ORIGINATORS const.
	 * @param bool   $is_verified       Optional flag as to whether Item's objects are known to exist.
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
		$is_verified = true
	) {

		// For Media Library items, the source path should be relative to the Media Library's uploads directory.
		$uploads = wp_upload_dir();

		if ( false === $uploads['error'] && 0 === strpos( $source_path, $uploads['basedir'] ) ) {
			$source_path = static::unleadingslashit( substr( $source_path, strlen( $uploads['basedir'] ) ) );
		}

		list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();
		$settings = carrot_bunnycdn_incoom_plugin_whichtype_settings();
		$settingProvider = isset($settings['provider']) ? $settings['provider'] : 'aws';
        
        $this->provider    = (!empty($provider) ? $provider : $settingProvider);
		$this->region      = (!empty($region) ? $region : $Region);
		$this->bucket      = (!empty($bucket) ? $bucket : $Bucket);
		$this->path        = (!empty($path) ? $path : $source_path);
		$this->is_private  = $is_private;
		$this->source_id   = $source_id;
		$this->source_path = $source_path;
		$this->extra_info  = serialize( $extra_info );
		$this->originator  = $originator;
		$this->is_verified = $is_verified;

		if ( empty( $original_filename ) ) {
			$this->original_path        = $path;
			$this->original_source_path = $source_path;
		} else {
			$this->original_path        = str_replace( wp_basename( $path ), $original_filename, $path );
			$this->original_source_path = str_replace( wp_basename( $source_path ), $original_filename, $source_path );
		}

		if(empty($this->original_path)){
			$this->original_path = $source_path;
		}

		if ( ! empty( $id ) ) {
			$this->id = $id;
		}

		static::add_to_items_cache( $this );
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
			$item = new carrot_bunnycdn_incoom_plugin_Item(
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
			$item->save();
			$item->duplicate_filesize_total( $this->source_id() );
		}
	}

	/**
	 * Duplicate '_carrot_filesize_total' meta if it exists for an attachment.
	 *
	 * @param int $attachment_id
	 */
	public function duplicate_filesize_total( $attachment_id ) {
		if ( ! ( $filesize = get_post_meta( $attachment_id, '_carrot_filesize_total', true ) ) ) {
			// No filesize to duplicate.
			return;
		}

		update_post_meta( $this->source_id(), '_carrot_filesize_total', $filesize );
	}

	public function rebuild_key($Key, $custom_prefix=''){
        return carrot_bunnycdn_incoom_plugin_rebuild_key($Key, $custom_prefix);
    }

	/**
	 * Returns an array keyed by offloaded source file name.
	 *
	 * Each entry is as per objects, but also includes an array of object_keys.
	 *
	 * @return array
	 */
	public function offloaded_files() {
		$offloaded_files = array();

		foreach ( $this->objects() as $object_key => $object ) {
			if ( isset( $offloaded_files[ $object['source_file'] ] ) ) {
				$offloaded_files[ $object['source_file'] ]['object_keys'][] = $object_key;
			} else {
				$object['object_keys']                     = array( $object_key );
				$offloaded_files[ $object['source_file'] ] = $object;
			}
		}

		return $offloaded_files;
	}

	/**
	 * Get the provider URL for an item
	 *
	 * @param string   $object_key
	 * @param null|int $expires
	 * @param array    $headers
	 *
	 * @return string|WP_Error|bool
	 */
	public function get_provider_url( $object_key = null, $expires = null, $headers = array() ) {
		
		if ( is_null( $object_key ) ) {
			$object_key = carrot_bunnycdn_incoom_plugin_Item::primary_object_key();
		}

		if($object_key === carrot_bunnycdn_incoom_plugin_Item::primary_object_key()){
			$object_key = $this->rebuild_key($this->source_path($object_key));
		}

		list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();

		try {
			$url = $aws_s3_client->getObjectUrl( $Bucket, $Region, $object_key);
			
			try {
				$url = carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url($url);
			} catch (\Throwable $th) { error_log($e->getMessage()); }

			return apply_filters( 'carrot_get_item_url', $url, $this, $this->get_item_source_array() );
		} catch ( Exception $e ) {
			return new WP_Error( 'exception', $e->getMessage() );
		}
	}

	/**
	 * Get a Buddy Boss item object from the database
	 *
	 * @param int    $source_id
	 * @param string $object_type
	 * @param string $image_type
	 *
	 * @return carrot_bunnycdn_incoom_plugin_Item|false
	 */
	public static function get_buddy_boss_item( $source_id, $object_type, $image_type ) {
		/** @var carrot_bunnycdn_incoom_plugin_Item $class */
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
	 * Get size name from file name
	 *
	 * @return string
	 */
	public function get_object_key_from_filename( $filename ) {
		return carrot_bunnycdn_incoom_plugin_Utils::get_intermediate_size_from_filename( $this->source_id(), basename( $filename ) );
	}

	/**
	 * Get the full remote key for this item including private prefix when needed
	 *
	 * @param string|null $object_key
	 *
	 * @return string
	 */
	public function provider_key( $object_key = null ) {
		$path = $this->path( $object_key );
		if ( $this->is_private( $object_key ) ) {
			$path = $this->private_prefix() . $path;
		}

		return $path;
	}

	/**
	 * Returns an associative array of provider keys by their object_key.
	 *
	 * NOTE: There may be duplicate keys if object_keys reference same source file/object.
	 *
	 * @return array
	 */
	public function provider_keys() {
		$keys = array();

		foreach ( array_keys( $this->objects() ) as $object_key ) {
			$keys[ $object_key ] = $this->provider_key( $object_key );
		}

		return $keys;
	}

	/**
	 * If another item in current site shares full size *local* paths, only remove remote files not referenced by duplicates.
	 * We reference local paths as they should be reflected one way or another remotely, including backups.
	 *
	 * @params Item  $carrot_item
	 * @params array $paths
	 */
	public function remove_duplicate_paths( carrot_bunnycdn_incoom_plugin_Item $carrot_item, $paths ) {
		return $paths;
	}

	/**
	 * Synthesize a data struct to be used when passing information
	 * about the current item to filters that assume the item is a
	 * media library item.
	 *
	 * @return array
	 */
	public function item_data_for_acl_filter() {
		return array(
			'source_type' => $this->source_type(),
			'file'        => $this->path( carrot_bunnycdn_incoom_plugin_Item::primary_object_key() ),
			'sizes'       => array_keys( $this->objects() ),
		);
	}

	/**
	 * Returns the item source description array for this item
	 *
	 * @return array Array with the format:
	 *               array(
	 *                  'id'          => 1,
	 *                  'source_type' => 'foo-type',
	 *               )
	 */
	public function get_item_source_array() {
		return array(
			'id'          => $this->source_id(),
			'source_type' => $this->source_type(),
		);
	}
	
	/**
	 * Set array of objects (i.e. different sizes of same attachment item)
	 *
	 * @param array $objects
	 */
	public function set_objects( $objects ) {
		$extra_info = $this->extra_info();
		$extra_info['objects'] = $objects;
		$this->set_extra_info( $extra_info );
	}

	/**
	 * Get an absolute source path.
	 *
	 * Default it is based on the WordPress uploads folder.
	 *
	 * @param string|null $object_key Optional, by default the original file's source path is used.
	 *
	 * @return string
	 */
	public function full_source_path( $object_key = null ) {
		/**
		 * Filter the absolute directory path prefix for an item's source files.
		 *
		 * @param string $basedir    Default is WordPress uploads folder.
		 * @param Item   $carrot_item The Item whose full source path is being accessed.
		 */
		$basedir = trailingslashit( wp_upload_dir()['basedir'] );

		return $basedir . $this->source_path( $object_key );
	}

	public function source_key( $object_key = null ) {
		return $this->source_path( $object_key );
	}

	/**
	 * Getter for item's source type.
	 *
	 * @return string
	 */
	public static function source_type() {
		return static::$source_type;
	}

	/**
	 * Returns the standard object key for an items primary object
	 *
	 * @return string
	 */
	public static function primary_object_key() {
		return '__carrot_bunnycdn_primary';
	}

	/**
	 * Returns the string used to group all keys in the object cache by.
	 *
	 * @return string
	 */
	protected static function get_object_cache_group() {
		static $group;

		if ( empty( $group ) ) {
			$group = trim( '' . apply_filters( 'carrot_bunnycdn_incoom_plugin_object_cache_group', 'carrot' ) );
		}

		return $group;
	}

	/**
	 * Get base string for all of current blog's object cache keys.
	 *
	 * @return string
	 */
	protected static function get_object_cache_base_key() {
		$blog_id = get_current_blog_id();

		return static::items_table() . '-' . $blog_id . '-' . static::$source_type;
	}

	/**
	 * Get full object cache key.
	 *
	 * @param string $base_key
	 * @param string $key
	 * @param string $field
	 *
	 * @return string
	 */
	protected static function get_object_cache_full_key( $base_key, $key, $field ) {
		return sanitize_text_field( $base_key . '-' . $key . '-' . $field );
	}

	/**
	 * Add the given item to the object cache.
	 *
	 * @param Item $item
	 */
	protected static function add_to_object_cache( $item ) {
		if ( empty( $item ) || empty( static::$cache_keys ) ) {
			return;
		}

		$base_key = static::get_object_cache_base_key();
		$group    = static::get_object_cache_group();

		$keys = array();

		foreach ( static::$cache_keys as $key => $fields ) {
			foreach ( $fields as $field ) {
				$full_key = static::get_object_cache_full_key( $base_key, $key, $item->{$field}() );

				if ( in_array( $full_key, $keys ) ) {
					continue;
				}

				wp_cache_set( $full_key, $item, $group );

				$keys[] = $full_key;
			}
		}
	}

	/**
	 * Delete the given item from the object cache.
	 *
	 * @param Item $item
	 */
	protected static function remove_from_object_cache( $item ) {
		if ( empty( $item ) || empty( static::$cache_keys ) ) {
			return;
		}

		$base_key = static::get_object_cache_base_key();
		$group    = static::get_object_cache_group();

		$keys = array();

		foreach ( static::$cache_keys as $key => $fields ) {
			foreach ( $fields as $field ) {
				$full_key = static::get_object_cache_full_key( $base_key, $key, $item->{$field}() );

				if ( in_array( $full_key, $keys ) ) {
					continue;
				}

				wp_cache_delete( $full_key, $group );

				$keys[] = $full_key;
			}
		}
	}

	/**
	 * Try and get Item from object cache by known key and value.
	 *
	 * Note: Actual lookup is scoped by blog and item's source_type, so example key may be 'source_id'.
	 *
	 * @param string $key   The base of the key that makes up the lookup, e.g. field for given value.
	 * @param mixed  $value Will be coerced to string for lookup.
	 *
	 * @return bool|Item
	 */
	protected static function get_from_object_cache( $key, $value ) {
		if ( ! array_key_exists( $key, static::$cache_keys ) ) {
			return false;
		}

		$base_key = static::get_object_cache_base_key();
		$full_key = static::get_object_cache_full_key( $base_key, $key, $value );
		$group    = static::get_object_cache_group();
		$force    = false;
		$found    = false;
		$result   = wp_cache_get( $full_key, $group, $force, $found );

		if ( $found ) {
			return $result;
		}

		return false;
	}

	/**
	 * (Re)initialize the static cache used for speeding up queries.
	 */
	public static function init_cache() {
		self::$checked_table_exists = array();

		static::$items_cache_by_id          = array();
		static::$items_cache_by_source_id   = array();
		static::$items_cache_by_path        = array();
		static::$items_cache_by_source_path = array();
	}

	/**
	 * Add an item to the static cache to allow fast retrieval via get_from_items_cache_by_* functions.
	 *
	 * @param Item $item
	 */
	protected static function add_to_items_cache( $item ) {
		$blog_id = get_current_blog_id();

		if ( ! empty( $item->id() ) ) {
			static::$items_cache_by_id[ $blog_id ][ $item->id() ] = $item;
		}

		if ( ! empty( $item->source_id() ) ) {
			static::$items_cache_by_source_id[ $blog_id ][ static::$source_type ][ $item->source_id() ] = $item;
		}

		if ( ! empty( $item->path() ) ) {
			static::$items_cache_by_path[ $blog_id ][ static::$source_type ][ $item->original_path() ] = $item;
			static::$items_cache_by_path[ $blog_id ][ static::$source_type ][ $item->path() ]          = $item;
		}

		if ( ! empty( $item->source_path() ) ) {
			static::$items_cache_by_source_path[ $blog_id ][ static::$source_type ][ $item->original_source_path() ] = $item;
			static::$items_cache_by_source_path[ $blog_id ][ static::$source_type ][ $item->source_path() ]          = $item;
		}
	}

	/**
	 * Remove an item from the static cache that allows fast retrieval via get_from_items_cache_by_* functions.
	 *
	 * @param Item $item
	 */
	protected static function remove_from_items_cache( $item ) {
		$blog_id = get_current_blog_id();

		if ( ! empty( $item->id() ) ) {
			unset( static::$items_cache_by_id[ $blog_id ][ $item->id() ] );
		}

		if ( ! empty( $item->source_id() ) ) {
			unset( static::$items_cache_by_source_id[ $blog_id ][ static::$source_type ][ $item->source_id() ] );
		}

		if ( ! empty( $item->path() ) ) {
			unset( static::$items_cache_by_path[ $blog_id ][ static::$source_type ][ $item->original_path() ] );
			unset( static::$items_cache_by_path[ $blog_id ][ static::$source_type ][ $item->path() ] );
		}

		if ( ! empty( $item->source_path() ) ) {
			unset( static::$items_cache_by_source_path[ $blog_id ][ static::$source_type ][ $item->original_source_path() ] );
			unset( static::$items_cache_by_source_path[ $blog_id ][ static::$source_type ][ $item->source_path() ] );
		}
	}

	/**
	 * Try and get Item from cache by known id.
	 *
	 * @param int $id
	 *
	 * @return bool|Item
	 */
	private static function get_from_items_cache_by_id( $id ) {
		$blog_id = get_current_blog_id();

		if ( ! empty( static::$items_cache_by_id[ $blog_id ][ $id ] ) ) {
			return static::$items_cache_by_id[ $blog_id ][ $id ];
		}

		$item = static::get_from_object_cache( 'id', $id );

		if ( $item ) {
			static::add_to_items_cache( $item );

			return $item;
		}

		return false;
	}

	/**
	 * Try and get Item from cache by known source_id.
	 *
	 * @param int $source_id
	 *
	 * @return bool|Item
	 */
	private static function get_from_items_cache_by_source_id( $source_id ) {
		$blog_id = get_current_blog_id();

		if ( ! empty( static::$items_cache_by_source_id[ $blog_id ][ static::$source_type ][ $source_id ] ) ) {
			return static::$items_cache_by_source_id[ $blog_id ][ static::$source_type ][ $source_id ];
		}

		$item = static::get_from_object_cache( 'source_id', $source_id );

		if ( $item ) {
			static::add_to_items_cache( $item );

			return $item;
		}

		return false;
	}

	/**
	 * Try and get Item from cache by known bucket and path.
	 *
	 * @param string $bucket
	 * @param string $path
	 *
	 * @return bool|Item
	 */
	private static function get_from_items_cache_by_bucket_and_path( $bucket, $path ) {
		$blog_id = get_current_blog_id();

		if ( ! empty( static::$items_cache_by_path[ $blog_id ][ static::$source_type ][ $path ] ) ) {
			/** @var Item $item */
			$item = static::$items_cache_by_path[ $blog_id ][ static::$source_type ][ $path ];

			if ( $item->bucket() === $bucket ) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * The full items table name for current blog.
	 *
	 * @return string
	 */
	protected static function items_table() {
		global $wpdb;
		$table_name = $wpdb->get_blog_prefix() . static::ITEMS_TABLE;

		if ( empty( self::$checked_table_exists[ $table_name ] ) ) {
			self::$checked_table_exists[ $table_name ] = true;

			$schema_version = get_option( 'carrot_schema_version', '0.0.0' );

			if ( version_compare( $schema_version, carrot_bunnycdn_incoom_plugin_VERSION, '<' ) ) {
				update_option( 'carrot_schema_version', carrot_bunnycdn_incoom_plugin_VERSION );
			}
		}

		return $table_name;
	}

	/**
	 * Get item's data as an array, optionally with id if available.
	 *
	 * @param bool $include_id Default false.
	 *
	 * @return array
	 */
	public function key_values( $include_id = false ) {
		$key_values = array(
			'provider'             => $this->provider,
			'region'               => $this->region,
			'bucket'               => $this->bucket,
			'path'                 => $this->path,
			'original_path'        => empty($this->original_path) ? $this->source_path : $this->original_path,
			'is_private'           => $this->is_private,
			'source_type'          => static::$source_type,
			'source_id'            => $this->source_id,
			'source_path'          => $this->source_path,
			'original_source_path' => $this->original_source_path,
			'extra_info'           => $this->extra_info,
			'originator'           => $this->originator,
			'is_verified'          => $this->is_verified,
		);

		if ( $include_id && ! empty( $this->id ) ) {
			$key_values['id'] = $this->id;
		}

		ksort( $key_values );

		return $key_values;
	}

	/**
	 * All the item's property names in an array, optionally with id if available.
	 *
	 * @param bool $include_id Default false.
	 *
	 * @return array
	 */
	private function keys( $include_id = false ) {
		return array_keys( $this->key_values( $include_id ) );
	}

	/**
	 * All the item's property values in an array, optionally with id if available.
	 *
	 * @param bool $include_id Default false.
	 *
	 * @return array
	 */
	private function values( $include_id = false ) {
		return array_values( $this->key_values( $include_id ) );
	}

	/**
	 * Get item's column formats as an associative array, optionally with id if available.
	 *
	 * @param bool $include_id Default false.
	 *
	 * @return array
	 */
	private function key_formats( $include_id = false ) {
		$key_values = array(
			'provider'             => '%s',
			'region'               => '%s',
			'bucket'               => '%s',
			'path'                 => '%s',
			'original_path'        => '%s',
			'is_private'           => '%d',
			'source_type'          => '%s',
			'source_id'            => '%d',
			'source_path'          => '%s',
			'original_source_path' => '%s',
			'extra_info'           => '%s',
			'originator'           => '%d',
			'is_verified'          => '%d',
		);

		if ( $include_id && ! empty( $this->id ) ) {
			$key_values['id'] = '%d';
		}

		ksort( $key_values );

		return $key_values;
	}

	/**
	 * All the item's column formats in an indexed array, optionally with id if available.
	 *
	 * @param bool $include_id Default false.
	 *
	 * @return array
	 */
	private function formats( $include_id = false ) {
		return array_values( $this->key_formats( $include_id ) );
	}

	/**
	 * Save the item's current data.
	 *
	 * @return int|WP_Error
	 */
	public function save() {
		global $wpdb;
		
		$wpdb->hide_errors();
		
		$result = false;

		try {
			if ( empty( $this->id ) ) {

				$item = static::get_by_source_id( $this->source_id );
				if($item && !empty($item->id)){
					$this->id = $item->id;
					return $this->id;
				}
				
				try {
					$result = $wpdb->insert( static::items_table(), $this->key_values(), $this->formats() );
	
					if ( $result ) {
						$this->id = $wpdb->insert_id;
	
						// Now that the item has an ID it should be (re)cached.
						static::add_to_items_cache( $this );
					}
				} catch (\Throwable $th) {}
			} else {
				// Make sure object cache does not have stale items.
				$old_item = static::get_from_object_cache( 'id', $this->id() );
				static::remove_from_object_cache( $old_item );
				unset( $old_item );
	
				$result = $wpdb->update( static::items_table(), $this->key_values(), array( 'id' => $this->id ), $this->formats(), array( '%d' ) );
			}
	
			if ( false !== $result ) {
				// Now that the item has an ID it should be (re)cached.
				static::add_to_object_cache( $this );
			} else {
				static::remove_from_items_cache( $this );
	
				return new WP_Error( 'item_save', 'Error saving item:- ' . $wpdb->last_error );
			}
		} catch (\Throwable $th) {}

		/**
		 * Fires action after item save finishes
		 *
		 * @param Item $carrot_item The item that was just updated
		 */
		do_action( 'carrot_after_item_save', $this );

		return $this->id;
	}

	/**
	 * Delete the current item.
	 *
	 * @return bool|WP_Error
	 */
	public function delete() {
		global $wpdb;
		
		$wpdb->hide_errors();

		static::remove_from_items_cache( $this );
		static::remove_from_object_cache( $this );

		if ( empty( $this->id ) ) {
			return new WP_Error( 'item_delete', 'Error trying to delete item with no id.' );
		} else {
			$result = $wpdb->delete( static::items_table(), array( 'id' => $this->id ), array( '%d' ) );
		}

		if ( ! $result ) {
			return new WP_Error( 'item_delete', 'Error deleting item:- ' . $wpdb->last_error );
		}

		return true;
	}

	/**
	 * Creates an item based on object from database.
	 *
	 * @param object $object
	 * @param bool   $add_to_object_cache Should this object be added to the object cache too?
	 *
	 * @return Item
	 */
	protected static function create( $object, $add_to_object_cache = false ) {
		$extra_info = array();

		if ( ! empty( $object->extra_info ) ) {
			$extra_info = unserialize( $object->extra_info );
		}

		$item = new static(
			$object->provider,
			$object->region,
			$object->bucket,
			$object->path,
			$object->is_private,
			$object->source_id,
			$object->source_path,
			wp_basename( $object->original_source_path ),
			$extra_info,
			$object->id,
			$object->originator,
			$object->is_verified
		);

		if ( $add_to_object_cache ) {
			static::add_to_object_cache( $item );
		}

		return $item;
	}

	/**
	 * Get an item by its id.
	 *
	 * @param integer $id
	 *
	 * @return bool|Item
	 */
	public static function get_by_id( $id ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return false;
		}

		$item = static::get_from_items_cache_by_id( $id );

		if ( ! empty( $item ) ) {
			return $item;
		}

		$sql = $wpdb->prepare( "SELECT * FROM " . static::items_table() . " WHERE source_type = %s AND id = %d", static::$source_type, $id );

		$object = $wpdb->get_row( $sql );

		if ( empty( $object ) ) {
			return false;
		}

		return static::create( $object, true );
	}

	/**
	 * Get an item by its source id.
	 *
	 * While source id isn't strictly unique, it is by source type, which is always used in queries based on called class.
	 *
	 * @param integer $source_id
	 *
	 * @return bool|Item
	 */
	public static function get_by_source_id( $source_id ) {
		global $wpdb;

		if ( ! is_numeric( $source_id ) ) {
			return false;
		}

		$source_id = (int) $source_id;

		if ( $source_id < 0 ) {
			return false;
		}

		$item = static::get_from_items_cache_by_source_id( $source_id );

		if ( ! empty( $item ) && ! empty( $item->id() ) ) {
			return $item;
		}

		$sql = $wpdb->prepare( "SELECT * FROM " . static::items_table() . " WHERE source_id = %d AND source_type = %s", $source_id, static::$source_type );

		$object = $wpdb->get_row( $sql );

		if ( empty( $object ) ) {
			return false;
		}

		return static::create( $object, true );
	}

	/**
	 * Getter for item's id value.
	 *
	 * @return integer
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * Getter for item's provider value.
	 *
	 * @return string
	 */
	public function provider() {
		return $this->provider;
	}

	/**
	 * Getter for item's region value.
	 *
	 * @return string
	 */
	public function region() {
		return $this->region;
	}

	/**
	 * Getter for item's bucket value.
	 *
	 * @return string
	 */
	public function bucket() {
		return $this->bucket;
	}

	/**
	 * Getter for item's path value.
	 *
	 * @return string
	 */
	public function path() {
		return $this->path;
	}

	/**
	 * Getter for item's original_path value.
	 *
	 * @return string
	 */
	public function original_path() {
		return $this->original_path;
	}

	/**
	 * Getter for item's is_private value.
	 *
	 * @return bool
	 */
	public function is_private() {
		return (bool) $this->is_private;
	}

	/**
	 * Setter for item's is_private value
	 *
	 * @param bool $private
	 */
	public function set_is_private( $private ) {
		$this->is_private = (bool) $private;
	}

	/**
	 * Getter for item's source_id value.
	 *
	 * @return integer
	 */
	public function source_id() {
		return $this->source_id;
	}

	/**
	 * Get array of objects (i.e. different sizes of same attachment item)
	 *
	 * @return array
	 */
	public function objects() {
		$extra_info = $this->extra_info();
		if ( isset( $extra_info['objects'] ) && is_array( $extra_info['objects'] ) ) {
			// Make sure that the primary object key, if exists, comes first
			$array_keys  = array_keys( $extra_info['objects'] );
			$primary_key = carrot_bunnycdn_incoom_plugin_Item::primary_object_key();
			if ( in_array( $primary_key, $array_keys ) && $primary_key !== $array_keys[0] ) {
				$extra_info['objects'] = array_merge( array( $primary_key => null ), $extra_info['objects'] );
			}

			return $extra_info['objects'];
		}

		return array();
	}

	/**
	 * Getter for item's source_path value.
	 *
	 * @param string|null $object_key
	 *
	 * @return string
	 */
	public function source_path( $object_key = null ) {
		if ( ! empty( $object_key ) ) {
			$objects = $this->objects();
			if ( isset( $objects[ $object_key ] ) ) {
				$object_file = $objects[ $object_key ]['source_file'];

				return str_replace( wp_basename( $this->source_path ), $object_file, $this->source_path );
			}
		}

		return $this->source_path;
	}

	/**
	 * Getter for item's original_source_path value.
	 *
	 * @return string
	 */
	public function original_source_path() {
		return $this->original_source_path;
	}

	/**
	 * Getter for item's extra_info value.
	 *
	 * @return array
	 */
	public function extra_info() {
		return unserialize( $this->extra_info );
	}

	/**
	 * Setter for extra_info value
	 *
	 * @param array $extra_info
	 */
	protected function set_extra_info( $extra_info ) {
		$this->extra_info = serialize( $extra_info );
	}

	/**
	 * Getter for item's originator value.
	 *
	 * @return integer
	 */
	public function originator() {
		return $this->originator;
	}

	/**
	 * Getter for item's is_verified value.
	 *
	 * @return bool
	 */
	public function is_verified() {
		return (bool) $this->is_verified;
	}

	/**
	 * Setter for item's is_verified value
	 *
	 * @param bool $is_verified
	 */
	public function set_is_verified( $is_verified ) {
		$this->is_verified = (bool) $is_verified;
	}

	/**
	 * Get normalized object path dir.
	 *
	 * @return string
	 */
	public function normalized_path_dir() {
		$directory = dirname( $this->path );

		return ( '.' === $directory ) ? '' : static::trailingslash_prefix( $directory );
	}

	/**
	 * Get the first source id for a bucket and path.
	 *
	 * @param string $bucket
	 * @param string $path
	 *
	 * @return int|bool
	 */
	public static function get_source_id_by_bucket_and_path( $bucket, $path ) {
		global $wpdb;

		if ( empty( $bucket ) || empty( $path ) ) {
			return false;
		}

		$item = static::get_from_items_cache_by_bucket_and_path( $bucket, $path );

		if ( ! empty( $item ) ) {
			return $item->source_id();
		}

		$sql = $wpdb->prepare(
			"
				SELECT source_id FROM " . static::items_table() . "
				WHERE source_type = %s
				AND bucket = %s
				AND (path = %s OR original_path = %s)
				ORDER BY source_id LIMIT 1
			",
			static::$source_type,
			$bucket,
			$path,
			$path
		);

		$result = $wpdb->get_var( $sql );

		return empty( $result ) ? false : (int) $result;
	}

	/**
	 * Get the source id for a given remote URL.
	 *
	 * @param string $url
	 *
	 * @return int|bool
	 */
	public static function get_source_id_by_remote_url( $url ) {
		global $wpdb;

		$parts = carrot_bunnycdn_incoom_plugin_Utils::parse_url( $url );
		$path  = incoom_carrot_bunnycdn_incoom_plugin_decode_filename_in_path( ltrim( $parts['path'], '/' ) );

		// Remove the first directory to cater for bucket in path domain settings.
		if ( false !== strpos( $path, '/' ) ) {
			$path = explode( '/', $path );
			array_shift( $path );
			$path = implode( '/', $path );
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM " . static::items_table() . " WHERE source_type = %s AND (path LIKE %s OR original_path LIKE %s);"
			, static::$source_type
			, '%' . $path
			, '%' . $path
		);

		$results = $wpdb->get_results( $sql );

		// Nothing found, shortcut out.
		if ( 0 === count( $results ) ) {
			return false;
		}

		// Regardless of whether 1 or many items found, must validate match.
		$path = incoom_carrot_bunnycdn_incoom_plugin_decode_filename_in_path( ltrim( $parts['path'], '/' ) );

		foreach ( $results as $result ) {
			$carrot_item = static::create( $result );

			// If item's bucket matches first segment of URL path, remove it from URL path before checking match.
			if ( 0 === strpos( $path, trailingslashit( $carrot_item->bucket() ) ) ) {
				$match_path = ltrim( substr_replace( $path, '', 0, strlen( $carrot_item->bucket() ) ), '/' );
			} else {
				$match_path = $path;
			}

			// If item's private prefix matches first segment of URL path, remove it from URL path before checking match.
			if ( ! empty( $carrot_item->private_prefix() ) && 0 === strpos( $match_path, $carrot_item->private_prefix() ) ) {
				$match_path = ltrim( substr_replace( $match_path, '', 0, strlen( $carrot_item->private_prefix() ) ), '/' );
			}

			// Exact match, return ID.
			if ( $carrot_item->path() === $match_path || $carrot_item->original_path() === $match_path ) {
				return $carrot_item->source_id();
			}
		}

		return false;
	}

	/**
	 * Get the private prefix for attachment's private objects.
	 *
	 * @return string
	 */
	public function private_prefix() {
		$extra_info = $this->extra_info();

		if ( ! empty( $extra_info['private_prefix'] ) ) {
			return static::trailingslash_prefix( $extra_info['private_prefix'] );
		}

		return '';
	}

	/**
	 * Get an array of managed source_ids in descending order.
	 *
	 * While source id isn't strictly unique, it is by source type, which is always used in queries based on called class.
	 *
	 * @param integer $upper_bound Returned source_ids should be lower than this, use null/0 for no upper bound.
	 * @param integer $limit       Maximum number of source_ids to return. Required if not counting.
	 * @param bool    $count       Just return a count of matching source_ids? Negates $limit, default false.
	 * @param int     $originator  Optionally restrict to only records with given originator type from ORIGINATORS const.
	 * @param bool    $is_verified Optionally restrict to only records that either are or are not verified.
	 *
	 * @return array|int
	 */
	public static function get_source_ids( $upper_bound, $limit, $count = false, $originator = null, $is_verified = null ) {
		global $wpdb;

		$args = array( static::$source_type );

		if ( $count ) {
			$sql = 'SELECT COUNT(DISTINCT source_id)';
		} else {
			$sql = 'SELECT DISTINCT source_id';
		}

		$sql .= ' FROM ' . static::items_table() . ' WHERE source_type = %s';

		if ( ! empty( $upper_bound ) ) {
			$sql    .= ' AND source_id < %d';
			$args[] = $upper_bound;
		}

		// If an originator type given, check that it is valid before continuing and using.
		if ( null !== $originator ) {
			if ( is_int( $originator ) && in_array( $originator, self::ORIGINATORS ) ) {
				$sql    .= ' AND originator = %d';
				$args[] = $originator;
			} else {
				return $count ? 0 : array();
			}
		}

		// If an is_verified value given, check that it is valid before continuing and using.
		if ( null !== $is_verified ) {
			if ( is_bool( $is_verified ) ) {
				$sql    .= ' AND is_verified = %d';
				$args[] = (int) $is_verified;
			} else {
				return $count ? 0 : array();
			}
		}

		if ( ! $count ) {
			if($limit > 0){
				$sql    .= ' ORDER BY source_id DESC LIMIT %d';
				$args[] = $limit;
			}
		}

		$sql = $wpdb->prepare( $sql, $args );

		if ( $count ) {
			return $wpdb->get_var( $sql );
		} else {
			return array_map( 'intval', $wpdb->get_col( $sql ) );
		}
	}

	/**
	 * Get an array of un-managed source_ids in descending order.
	 *
	 * While source id isn't strictly unique, it is by source type, which is always used in queries based on called class.
	 *
	 * @param integer $upper_bound Returned source_ids should be lower than this, use null/0 for no upper bound.
	 * @param integer $limit       Maximum number of source_ids to return. Required if not counting.
	 * @param bool    $count       Just return a count of matching source_ids? Negates $limit, default false.
	 *
	 * @return array|int
	 *
	 * NOTE: Must be overridden by subclass, only reason this is not abstract is because static is preferred.
	 */
	public static function get_missing_source_ids( $upper_bound, $limit, $count = false ) {
		if ( $count ) {
			return 0;
		} else {
			return array();
		}
	}

	public static function verify_missing_source_ids( $limit, $count = false ) {
		if ( $count ) {
			return 0;
		} else {
			return array();
		}
	}

	/**
	 * Get absolute file paths associated with source item.
	 *
	 * @param integer $id
	 *
	 * @return array
	 */
	protected function source_paths( $id ) {
		$paths = array();

		return $paths;
	}

    /**
     * Trailing slash prefix string ensuring no leading slashes.
     *
     * @param $string
     *
     * @return string
     */
    public static function trailingslash_prefix( $string ) {
        return static::unleadingslashit( trailingslashit( trim( $string ) ) );
    }

    /**
     * Ensure string has a leading slash, like in absolute paths.
     *
     * @param $string
     *
     * @return string
     */
    public static function leadingslashit( $string ) {
        return '/' . static::unleadingslashit( $string );
    }

    /**
     * Ensure string has no leading slash, like in relative paths.
     *
     * @param $string
     *
     * @return string
     */
    public static function unleadingslashit( $string ) {
        return carrot_bunnycdn_incoom_plugin_Utils::unleadingslashit($string);
    }

	/**
	 * Does this item type use object versioning?
	 *
	 * @return bool
	 */
	public static function can_use_object_versioning() {
		$object_versioning = get_option('incoom_carrot_bunnycdn_incoom_plugin_object_versioning');
		if(empty($object_versioning)){
			return false;
		}
		return true;
	}

	/**
	 * Search for all items that have the source path(s).
	 *
	 * @param array|string $paths              Array of relative source paths.
	 * @param array|int    $exclude_source_ids Array of source_ids to exclude from search. Default, none.
	 * @param bool         $exact_match        Use paths as supplied (true, default), or greedy match on path without extension (e.g. find edited too).
	 * @param bool         $first_only         Only return first matched item sorted by source_id. Default false.
	 *
	 * @return array
	 */
	public static function get_by_source_path( $paths, $exclude_source_ids = array(), $exact_match = true, $first_only = false ) {
		global $wpdb;

		if ( ! is_array( $paths ) && is_string( $paths ) && ! empty( $paths ) ) {
			$paths = array( $paths );
		}

		if ( ! is_array( $paths ) || empty( $paths ) ) {
			return array();
		}

		$paths = carrot_bunnycdn_incoom_plugin_Utils::make_upload_file_paths_relative( array_unique( $paths ) );

		$sql = '
			SELECT DISTINCT items.*
			FROM ' . static::items_table() . ' AS items USE INDEX (uidx_source_path, uidx_original_source_path)
			WHERE 1=1
		';

		if ( ! empty( $exclude_source_ids ) ) {
			if ( ! is_array( $exclude_source_ids ) ) {
				$exclude_source_ids = array( $exclude_source_ids );
			}

			$sql .= ' AND items.source_id NOT IN (' . join( ',', $exclude_source_ids ) . ')';
		}

		if ( $exact_match ) {
			$sql .= " AND (items.source_path IN ('" . join( "','", $paths ) . "')";
			$sql .= " OR items.original_source_path IN ('" . join( "','", $paths ) . "'))";
		} else {
			$likes = array_map( function ( $path ) {
				$ext  = '.' . pathinfo( $path, PATHINFO_EXTENSION );
				$path = substr_replace( $path, '%', -strlen( $ext ) );

				return "items.source_path LIKE '" . $path . "' OR items.original_source_path LIKE '" . $path . "'";
			}, $paths );

			$sql .= ' AND (' . join( ' OR ', $likes ) . ')';
		}

		if ( $first_only ) {
			$sql .= ' ORDER BY items.source_id LIMIT 1';
		}

		return array_map( 'static::create', $wpdb->get_results( $sql ) );
	}

	/**
	 * Update filesize and carrot_filesize_total metadata on the underlying media library item
	 * after removing the local file.
	 *
	 * @param int $original_size
	 * @param int $total_size
	 */
	public function update_filesize_after_remove_local( $original_size, $total_size ) {
		update_post_meta( $this->source_id(), 'carrot_filesize_total', $total_size );

		if ( 0 < $original_size && ( $data = get_post_meta( $this->source_id(), '_wp_attachment_metadata', true ) ) ) {
			if ( empty( $data['filesize'] ) ) {
				$data['filesize'] = $original_size;

				// Update metadata with filesize
				update_post_meta( $this->source_id(), '_wp_attachment_metadata', $data );
			}
		}
	}
}