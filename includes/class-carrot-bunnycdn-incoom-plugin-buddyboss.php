<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Buddyboss class.
 */
class carrot_bunnycdn_incoom_plugin_Buddyboss {

	/**
	 * Our item types
	 *
	 * @var object[]
	 */
	private static $source_types;

	/**
	 * Are we inside a crop operation?
	 *
	 * @var bool
	 */
	private static $in_crop = false;

	/**
	 * Did we handle a crop operation?
	 *
	 * @var bool
	 */
	private static $did_crop = false;

	public static function init() {

		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/buddyboss/class-carrot-bunnycdn-incoom-plugin-bboss-item.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/buddyboss/class-carrot-bunnycdn-incoom-plugin-bboss-group-avatar.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/buddyboss/class-carrot-bunnycdn-incoom-plugin-bboss-group-cover.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/buddyboss/class-carrot-bunnycdn-incoom-plugin-bboss-user-avatar.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/buddyboss/class-carrot-bunnycdn-incoom-plugin-bboss-user-cover.php' );

		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/items/class-carrot-bunnycdn-incoom-plugin-library-item.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/items/class-carrot-bunnycdn-incoom-plugin-manifest.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/items/class-carrot-bunnycdn-incoom-plugin-item-handler.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/items/class-carrot-bunnycdn-incoom-plugin-download-handler.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/items/class-carrot-bunnycdn-incoom-plugin-remove-local-handler.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/items/class-carrot-bunnycdn-incoom-plugin-remove-provider-handler.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/items/class-carrot-bunnycdn-incoom-plugin-upload-handler.php' );

		// URL Rewriting.
		add_filter( 'bp_core_fetch_avatar_url_check', array( __CLASS__, 'fetch_avatar' ), 10, 2 );
		add_filter( 'bp_core_fetch_gravatar_url_check', array( __CLASS__, 'fetch_default_avatar' ), 99, 2 );
		add_filter( 'bb_get_default_custom_upload_profile_avatar', array( __CLASS__, 'filter_bb_get_default_custom_upload_profile_avatar' ), 10, 2 );
		add_filter( 'bb_get_default_custom_upload_group_avatar', array( __CLASS__, 'filter_bb_get_default_custom_upload_group_avatar' ), 10, 2 );
		add_filter( 'bp_attachments_pre_get_attachment', array( __CLASS__, 'fetch_cover' ), 10, 2 );
		add_filter( 'bb_get_default_custom_upload_profile_cover', array( __CLASS__, 'filter_bb_get_default_custom_upload_profile_cover' ), 10 );
		add_filter( 'bb_get_default_custom_upload_group_cover', array( __CLASS__, 'filter_bb_get_default_custom_upload_group_cover' ), 10 );
		add_filter( 'bb_video_get_symlink', array( __CLASS__, 'filter_bb_video_get_symlink' ), 10, 3 );
		add_filter( 'bb_video_get_thumb_url', array( __CLASS__, 'filter_bb_video_get_thumb_url' ), 10, 4 );

		
		add_action( 'bp_core_pre_avatar_handle_crop', array( __CLASS__, 'filter_bp_core_pre_avatar_handle_crop', ), 10, 2 );
		add_action( 'xprofile_avatar_uploaded', array( __CLASS__, 'avatar_uploaded', ), 10, 3 );
		add_action( 'groups_avatar_uploaded', array( __CLASS__, 'avatar_uploaded', ), 10, 3 );
		add_action( 'xprofile_cover_image_uploaded', array( __CLASS__, 'user_cover_uploaded', ), 10, 1 );
		add_action( 'groups_cover_image_uploaded', array( __CLASS__, 'groups_cover_uploaded', ), 10, 1 );
		add_action( 'bp_core_delete_existing_avatar', array( __CLASS__, 'delete_existing_avatar' ), 10, 1 );
		add_action( 'xprofile_cover_image_deleted', array( __CLASS__, 'delete_existing_user_cover' ), 10, 1 );
		add_action( 'groups_cover_image_deleted', array( __CLASS__, 'delete_existing_group_cover' ), 10, 1 );
		add_action( 'deleted_user', array( __CLASS__, 'handle_deleted_user' ), 10, 1 );
		add_action( 'groups_delete_group', array( __CLASS__, 'groups_delete_group' ), 10, 1 );
		add_filter( 'bp_attachments_pre_delete_file', array( __CLASS__, 'bp_attachments_pre_delete_file' ), 10, 2 );
		add_filter( 'bp_media_get_preview_image_url', array( __CLASS__, 'bp_media_get_preview_image_url' ), PHP_INT_MAX, 4 );

		add_action( 'carrot_post_upload_item', array( __CLASS__, 'post_upload_item' ), 10, 1 );

		if ( incoom_carrot_bunnycdn_incoom_plugin_is_bb_activate() ) {
			
			add_filter( 'carrot_bunnycdn_incoom_plugin_source_type_classes', array( __CLASS__, 'add_source_type_classes' ), 10, 1 );

			self::$source_types = array(
				'bboss-user-avatar'  => array(
					'class' => carrot_bunnycdn_incoom_plugin_BBoss_Item::get_item_class( 'user', 'avatar' ),
				),
				'bboss-user-cover'   => array(
					'class' => carrot_bunnycdn_incoom_plugin_BBoss_Item::get_item_class( 'user', 'cover' ),
				),
				'bboss-group-avatar' => array(
					'class' => carrot_bunnycdn_incoom_plugin_BBoss_Item::get_item_class( 'group', 'avatar' ),
				),
				'bboss-group-cover'  => array(
					'class' => carrot_bunnycdn_incoom_plugin_BBoss_Item::get_item_class( 'group', 'cover' ),
				),
			);
		}
	}

	public static function add_source_type_classes($source_types){

		$new_source_types = [];

		foreach ( self::$source_types as $key => $source_type ) {
			$new_source_types[$key] = $source_type['class'];
		}

		$new_source_types['media-library'] = 'carrot_bunnycdn_incoom_plugin_Library_Item';

		return $new_source_types;
	}

	public static function filter_bb_video_get_thumb_url($attachment_url, $video_id, $size, $attachment_id){
		
		if(!incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
			return $attachment_url;
		}

		$copy_file_s3_checkbox = get_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
		if ( $copy_file_s3_checkbox !== 'on' ) {
			return $attachment_url;
		}

		$source_id = 0;

		if(strpos($attachment_id, 'forbidden_') !== false){
			$explode_arr1 = explode( 'forbidden_', $attachment_id );
			$source_id = (int) $explode_arr1[1];
		}else{
			$source_id = (int) $attachment_id;
		}

		try {
			$carrot_item = carrot_bunnycdn_incoom_plugin_Item::get_by_source_id( $source_id );
			if($carrot_item){
				$key = static::rebuild_key($carrot_item->source_path());
				$newURL = $carrot_item->get_provider_url($key);
				if($newURL){
					return $newURL;
				}
			}
		} catch (\Throwable $th) {
			//throw $th;
		}
		
		return $attachment_url;
	}

	public static function filter_bb_video_get_symlink($attachment_url, $video_id, $attachment_id){

		if(!incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
			return $attachment_url;
		}

		$copy_file_s3_checkbox = get_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
		if ( $copy_file_s3_checkbox !== 'on' ) {
			return $attachment_url;
		}

		$source_id = 0;

		if(strpos($attachment_id, 'forbidden_') !== false){
			$explode_arr1 = explode( 'forbidden_', $attachment_id );
			$source_id = (int) $explode_arr1[1];
		}else{
			$source_id = (int) $attachment_id;
		}

		$carrot_item = carrot_bunnycdn_incoom_plugin_Item::get_by_source_id( $source_id );
		if($carrot_item){
			$key = static::rebuild_key($carrot_item->source_path());
			$newURL = $carrot_item->get_provider_url($key);
			if($newURL){
				return $newURL;
			}
		}
		
		return $attachment_url;
	}

	/**
	 * Handle post upload duties if uploaded item is a media-library item.
	 *
	 * @handles carrot_post_upload_item
	 *
	 * @param carrot_bunnycdn_incoom_plugin_Item $item
	 */
	public static function post_upload_item( $item ) {
		if ( carrot_bunnycdn_incoom_plugin_Item::source_type() !== $item->source_type() ) {
			return;
		}

		// Make sure duplicates are marked as offloaded too.
		$item->offload_duplicate_items();

		/**
		 * Fires after an attachment has been uploaded to the provider.
		 *
		 * @param int  $id         Attachment id
		 * @param Item $item The item that was just uploaded
		 */
		do_action( 'carrot_post_upload_attachment', $item->source_id(), $item );
	}

	public static function bp_media_get_preview_image_url( $attachment_url, $media_id, $attachment_id, $size ) {

		if(!incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
			return $attachment_url;
		}

		$copy_file_s3_checkbox = get_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
		if ( $copy_file_s3_checkbox !== 'on' ) {
			return $attachment_url;
		}

		$source_id = 0;

		if(strpos($attachment_id, 'forbidden_') !== false){
			$explode_arr1 = explode( 'forbidden_', $attachment_id );
			$source_id = (int) $explode_arr1[1];
		}else{
			$source_id = (int) $attachment_id;
		}

		$filePath = '';

		$file = image_get_intermediate_size( $source_id, $size );

		if(empty( $file['path'] )){
			$file = wp_get_attachment_metadata( $source_id );
			$filePath = $file['file'];
		}else{
			$filePath = $file['path'];
		}
		
		try {
			if ( ! empty( $filePath ) ) {
				$carrot_item = carrot_bunnycdn_incoom_plugin_Item::get_by_source_id( $source_id );
				if($carrot_item){
					$key = static::rebuild_key($filePath);
					$newURL = $carrot_item->get_provider_url($key);
					if($newURL){
						return $newURL;
					}
				}
			}
		} catch (\Throwable $th) {}

		return $attachment_url;
	}

	/**
	 * Filters default custom upload cover image URL.
	 *
	 * @handles bb_get_default_custom_upload_group_cover
	 *
	 * @param string $value Default custom upload group cover URL.
	 */
	public static function filter_bb_get_default_custom_upload_group_cover( $value ) {
		$params = array(
			'item_id'    => 0,
			'object_dir' => 'groups',
		);

		return static::rewrite_cover_url( $value, $params );
	}

	/**
	 * Filters to change default custom upload cover image.
	 *
	 * @handles bb_get_default_custom_upload_profile_cover
	 *
	 * @param string $value Default custom upload profile cover URL.
	 */
	public static function filter_bb_get_default_custom_upload_profile_cover( $value ) {
		$params = array(
			'item_id'    => 0,
			'object_dir' => 'members',
		);

		return static::rewrite_cover_url( $value, $params );
	}

	/**
	 * Filters to change default custom upload avatar image.
	 *
	 * @handles bb_get_default_custom_upload_profile_avatar
	 *
	 * @param string $custom_upload_profile_avatar Default custom upload avatar URL.
	 * @param string $size                         This parameter specifies whether you'd like the 'full' or 'thumb' avatar.
	 */
	public static function filter_bb_get_default_custom_upload_profile_avatar( $custom_upload_profile_avatar, $size ) {
		$params = array(
			'item_id' => 0,
			'object'  => 'user',
			'type'    => $size,
		);

		return static::rewrite_avatar_url( $custom_upload_profile_avatar, $params );
	}

	/**
	 * Filters to change default custom upload avatar image.
	 *
	 * @handles bb_get_default_custom_upload_group_avatar
	 *
	 * @param string $custom_upload_group_avatar Default custom upload avatar URL.
	 * @param string $size                       This parameter specifies whether you'd like the 'full' or 'thumb' avatar.
	 */
	public static function filter_bb_get_default_custom_upload_group_avatar( $custom_upload_group_avatar, $size ) {
		$params = array(
			'item_id' => 0,
			'object'  => 'group',
			'type'    => $size,
		);

		return static::rewrite_avatar_url( $custom_upload_group_avatar, $params );
	}

	/**
	 * Returns the avatar's remote default URL if gravatar not supplied.
	 *
	 * @handles bp_core_fetch_gravatar_url_check
	 *
	 * @param string $avatar_url
	 * @param array  $params
	 *
	 * @return string
	 */
	public static function fetch_default_avatar( $avatar_url, $params ) {
		return static::rewrite_avatar_url( $avatar_url, $params, 0 );
	}

	/**
	 * Returns the cover's remote URL.
	 *
	 * @handles bp_attachments_pre_get_attachment
	 *
	 * @param string $cover_url
	 * @param array  $params
	 *
	 * @return string
	 */
	public static function fetch_cover( $cover_url, $params ) {
		return static::rewrite_cover_url( $cover_url, $params );
	}

	public static function rebuild_key($Key, $custom_prefix=''){
        return carrot_bunnycdn_incoom_plugin_rebuild_key($Key, $custom_prefix);
    }

	/**
	 * If possible, rewrite local cover URL to remote, possibly using substitute source.
	 *
	 * @param string   $cover_url
	 * @param array    $params
	 * @param null|int $source_id Optional override for the source ID, e.g. default = 0.
	 *
	 * @return string
	 */
	private static function rewrite_cover_url( $cover_url, $params, $source_id = null ) {
		if(!incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
			return $cover_url;
		}

		$copy_file_s3_checkbox = get_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
		if ( $copy_file_s3_checkbox !== 'on' ) {
			return $cover_url;
		}

		if ( ! isset( $params['item_id'] ) || ! is_numeric( $params['item_id'] ) || empty( $params['object_dir'] ) ) {
			return $cover_url;
		}

		$object_type = static::object_type_from_dir( $params['object_dir'] );
		if ( is_null( $object_type ) ) {
			return $cover_url;
		}

		if ( ! empty( $cover_url ) && ! carrot_bunnycdn_incoom_plugin_url_needs_replacing( $cover_url ) ) {
			return $cover_url;
		}

		if ( ! is_numeric( $source_id ) ) {
			$source_id = $params['item_id'];
		}

		$carrot_item = carrot_bunnycdn_incoom_plugin_BBoss_Item::get_buddy_boss_item( $source_id, $object_type, 'cover' );
		if ( false !== $carrot_item ) {

			$new_url = $carrot_item->get_provider_url( carrot_bunnycdn_incoom_plugin_Item::primary_object_key() );
			if ( ! empty( $new_url ) && is_string( $new_url ) ) {
				// We should not supply remote URL during a delete operation,
				// but the delete process will fail if there isn't a local file to delete.
				if ( isset( $_POST['action'] ) && 'bp_cover_image_delete' === $_POST['action'] ) {
					return $cover_url;
				}

				return $new_url;
			}
		}

		return $cover_url;
	}

	/**
	 * Returns the avatar's remote URL.
	 *
	 * @handles bp_core_fetch_avatar_url_check
	 *
	 * @param string $avatar_url
	 * @param array  $params
	 *
	 * @return string
	 */
	public static function fetch_avatar( $avatar_url, $params ) {
		return static::rewrite_avatar_url( $avatar_url, $params );
	}
	
	/**
	 * If possible, rewrite local avatar URL to remote, possibly using substitute source.
	 *
	 * @param string   $avatar_url
	 * @param array    $params
	 * @param null|int $source_id Optional override for the source ID, e.g. default = 0.
	 *
	 * @return string
	 */
	private static function rewrite_avatar_url( $avatar_url, $params, $source_id = null ) {
		if(!incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
			return $avatar_url;
		}

		$copy_file_s3_checkbox = get_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
		if ( $copy_file_s3_checkbox !== 'on' ) {
			return $avatar_url;
		}

		if ( ! isset( $params['item_id'] ) || ! is_numeric( $params['item_id'] ) || empty( $params['object'] ) ) {
			return $avatar_url;
		}

		if ( ! empty( $avatar_url ) && ! carrot_bunnycdn_incoom_plugin_url_needs_replacing( $avatar_url ) ) {
			return $avatar_url;
		}

		if ( ! is_numeric( $source_id ) ) {
			$source_id = $params['item_id'];
		}

		$carrot_item = carrot_bunnycdn_incoom_plugin_BBoss_Item::get_buddy_boss_item( $source_id, $params['object'], 'avatar' );
		if ( false !== $carrot_item ) {

			if(empty($avatar_url)){
				$size = $params['type'];
				$avatar_url = static::rebuild_key($carrot_item->source_path($params['type']));
			}

			$object_key = carrot_bunnycdn_incoom_plugin_remove_upload_base_url($avatar_url);

			$new_url = $carrot_item->get_provider_url( static::rebuild_key($object_key) );

			if ( ! empty( $new_url ) && is_string( $new_url ) ) {
				$avatar_url = $new_url;
			}
		}
		
		return $avatar_url;
	}

    public static function get_resource_type() {
        return self::$source_types;
    }

	/**
	 * Handle / override Buddy Boss attempt to delete a local file that we have already removed
	 *
	 * @handles bp_attachments_pre_delete_file
	 *
	 * @param bool  $pre
	 * @param array $args
	 *
	 * @return bool
	 */
	public static function bp_attachments_pre_delete_file( $pre, $args ) {
		if ( empty( $args['object_dir'] ) || empty( $args['item_id'] ) ) {
			return $pre;
		}

		$object_type = static::object_type_from_dir( $args['object_dir'] );
		if ( is_null( $object_type ) ) {
			return $pre;
		}

		$class      = carrot_bunnycdn_incoom_plugin_BBoss_Item::get_item_class( $object_type, 'cover' );
		$carrot_item = $class::get_by_source_id( (int) $args['item_id'] );

		if ( ! $carrot_item ) {
			return $pre;
		}

		$source_file = $carrot_item->full_source_path( carrot_bunnycdn_incoom_plugin_Item::primary_object_key() );
		if ( file_exists( $source_file ) ) {
			return $pre;
		}

		return false;
	}

	/**
	 * Return object_type (user or group) based on object_dir passed in from Buddy Boss
	 *
	 * @param string $object_dir
	 *
	 * @return string|null
	 */
	private static function object_type_from_dir( $object_dir ) {
		switch ( $object_dir ) {
			case 'members':
				return 'user';
			case 'groups':
				return 'group';
		}

		return null;
	}

	/**
	 * Handle a newly uploaded avatar
	 *
	 * @handles xprofile_avatar_uploaded
	 * @handles groups_avatar_uploaded
	 *
	 * @param int    $source_id
	 * @param string $avatar_type
	 * @param array  $params
	 *
	 * @throws Exception
	 */
	public static function avatar_uploaded( $source_id, $avatar_type, $params ) {
		if ( self::$did_crop ) {
			return;
		}

		$copy_file_s3_checkbox = get_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
		if ( $copy_file_s3_checkbox !== 'on' ) {
			return;
		}

		if ( empty( $params['object'] ) ) {
			return;
		}

		$object_type = $params['object'];
		$image_type  = 'avatar';

		$carrot_item = carrot_bunnycdn_incoom_plugin_BBoss_Item::get_buddy_boss_item( $source_id, $object_type, $image_type );
		if ( false !== $carrot_item ) {
			static::delete_existing_avatar( array( 'item_id' => $source_id, 'object' => $object_type ) );
		}

		$class      = carrot_bunnycdn_incoom_plugin_BBoss_Item::get_item_class( $object_type, $image_type );
		$carrot_item = $class::create_from_source_id( $source_id );

		$upload_handler = carrot_bunnycdn_incoom_plugin_get_item_handler('upload');
		$upload_result  = $upload_handler->handle( $carrot_item );

		if ( is_wp_error( $upload_result ) ) {
			return;
		}

		$carrot_item->save();
	}

	/**
	 * Handle when a new user cover image is uploaded
	 *
	 * @handles xprofile_cover_image_uploaded
	 *
	 * @param int $source_id
	 *
	 * @throws Exception
	 */
	public static function user_cover_uploaded( $source_id ) {
		static::cover_uploaded( $source_id, 'user' );
	}

	/**
	 * Filters whether or not to handle cropping.
	 *
	 * But we use it to catch a successful crop so we can offload
	 * and later supply the correct remote URL.
	 *
	 * @handles bp_core_pre_avatar_handle_crop
	 *
	 * @param bool  $value Whether or not to crop.
	 * @param array $r     Array of parsed arguments for function.
	 *
	 * @throws Exception
	 */
	public static function filter_bp_core_pre_avatar_handle_crop( $value, $r ) {
		if ( ! function_exists( 'bp_core_avatar_handle_crop' ) ) {
			return $value;
		}

		self::$in_crop = ! self::$in_crop;

		if ( self::$in_crop ) {
			if ( bp_core_avatar_handle_crop( $r ) ) {
				static::avatar_uploaded( $r['item_id'], 'crop', $r );
				self::$did_crop = true;
			}

			// We handled the crop.
			return false;
		}

		// Don't cancel operation when we call it above.
		return $value;
	}

	/**
	 * Handle when a new group cover image is uploaded
	 *
	 * @handles xprofile_cover_image_uploaded
	 *
	 * @param int $source_id
	 *
	 * @throws Exception
	 */
	public static function groups_cover_uploaded( $source_id ) {
		static::cover_uploaded( $source_id, 'group' );
	}

	/**
	 * Handle a new group or user cover image
	 *
	 * @param int    $source_id
	 * @param string $object_type
	 *
	 * @throws Exception
	 */
	private static function cover_uploaded( $source_id, $object_type ) {
		$copy = get_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
		if ( $copy !== 'on' ) {
			return;
		}

		$carrot_item = carrot_bunnycdn_incoom_plugin_BBoss_Item::get_buddy_boss_item( $source_id, $object_type, 'cover' );
		if ( false !== $carrot_item ) {
			static::delete_existing_cover( $source_id, $object_type );
		}

		$class      = carrot_bunnycdn_incoom_plugin_BBoss_Item::get_item_class( $object_type, 'cover' );
		$carrot_item = $class::create_from_source_id( $source_id );

		$upload_handler = carrot_bunnycdn_incoom_plugin_get_item_handler('upload');
		$upload_handler->handle( $carrot_item );
	}

	/**
	 * Removes a user cover image from the remote bucket
	 *
	 * @handles xprofile_cover_image_deleted
	 *
	 * @param int $source_id
	 *
	 */
	public static function delete_existing_user_cover( $source_id ) {
		static::delete_existing_cover( $source_id, 'user' );
	}

	/**
	 * Removes a group cover image from the remote bucket
	 *
	 * @handles groups_cover_image_deleted
	 *
	 * @param int $source_id
	 *
	 */
	public static function delete_existing_group_cover( $source_id ) {
		static::delete_existing_cover( $source_id, 'group' );
	}

	/**
	 * Removes a cover image from the remote bucket
	 *
	 * @handles bp_core_delete_existing_avatar
	 *
	 * @param int    $source_id
	 * @param string $object_type
	 *
	 */
	public static function delete_existing_cover( $source_id, $object_type ) {
		
		$carrot_item = carrot_bunnycdn_incoom_plugin_BBoss_Item::get_buddy_boss_item( $source_id, $object_type, 'cover' );
		if ( ! empty( $carrot_item ) ) {
			$remove_provider = carrot_bunnycdn_incoom_plugin_get_item_handler('remove-provider');
			$remove_provider->handle( $carrot_item, array( 'verify_exists_on_local' => false ) );
			$carrot_item->delete();
		}
	}

	/**
	 * Removes avatar and cover from remote bucket when a user is deleted
	 *
	 * @handles deleted_user
	 *
	 * @param int $user_id
	 *
	 */
	public static function handle_deleted_user( $user_id ) {
		$args = array( 'item_id' => $user_id, 'object' => 'user' );
		static::delete_existing_avatar( $args );
		static::delete_existing_cover( $user_id, 'user' );
	}

	/**
	 * Removes avatar and cover when a group is deleted
	 *
	 * @handles groups_delete_group
	 *
	 * @param int $group_id
	 */
	public static function groups_delete_group( $group_id ) {
		$args = array( 'item_id' => $group_id, 'object' => 'group' );
		static::delete_existing_avatar( $args );
		static::delete_existing_cover( $group_id, 'group' );
	}

	/**
	 * Removes an avatar from the remote bucket
	 *
	 * @handles bp_core_delete_existing_avatar
	 *
	 * @param array $args
	 */
	public static function delete_existing_avatar( $args ) {
		if ( ! isset( $args['item_id'] ) || ! is_numeric( $args['item_id'] ) || empty( $args['object'] ) ) {
			return;
		}

		$carrot_item = carrot_bunnycdn_incoom_plugin_BBoss_Item::get_buddy_boss_item( $args['item_id'], $args['object'], 'avatar' );
		if ( ! empty( $carrot_item ) ) {
			$remove_provider = carrot_bunnycdn_incoom_plugin_get_item_handler('remove-provider');
			$remove_provider->handle( $carrot_item, array( 'verify_exists_on_local' => false ) );
			$carrot_item->delete();
		}
	}
}
?>