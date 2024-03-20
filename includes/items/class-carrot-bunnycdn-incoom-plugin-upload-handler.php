<?php

class carrot_bunnycdn_incoom_plugin_Upload_Handler extends carrot_bunnycdn_incoom_plugin_Item_Handler {
	/**
	 * @var string
	 */
	protected static $item_handler_key = 'upload';

	/**
	 * Keep track of individual files we've already attempted to upload
	 *
	 * @var array
	 */
	protected $attempted_upload = array();

	protected $files_uploaded = array();

	/**
	 * The default options that should be used if none supplied.
	 *
	 * @return array
	 */
	public static function default_options() {
		return array(
			'offloaded_files' => array(),
		);
	}

	public function rebuild_key($Key, $custom_prefix=''){
        return carrot_bunnycdn_incoom_plugin_rebuild_key($Key, $custom_prefix);
    }

	/**
	 * Prepare item for uploading by running filters, updating
	 *
	 * @param Item  $carrot_item
	 * @param array $options
	 *
	 * @return Manifest|WP_Error
	 */
	protected function pre_handle( carrot_bunnycdn_incoom_plugin_Item $carrot_item, array $options ) {
	
		$manifest         = new carrot_bunnycdn_incoom_plugin_Manifest();
		$source_type_name = carrot_bunnycdn_incoom_plugin_get_source_type_name( $carrot_item->source_type() );
		$primary_key      = carrot_bunnycdn_incoom_plugin_Item::primary_object_key();

		// Check for valid file path before attempting upload
		if ( empty( $carrot_item->source_path() ) ) {
			$error_msg = sprintf( __( '%s with id %d does not have a valid file path', 'carrot-bunnycdn-incoom-plugin' ), $source_type_name, $carrot_item->source_id() );

			return $this->return_handler_error( $error_msg );
		}

		// Ensure path is a string
		if ( ! is_string( $carrot_item->source_path() ) ) {
			$error_msg = sprintf( __( '%s with id %d. Provided path is not a string', 'carrot-bunnycdn-incoom-plugin' ), $source_type_name, $carrot_item->source_id() );

			return $this->return_handler_error( $error_msg );
		}

		// Ensure primary source file exists for new offload.
		if ( empty( $carrot_item->id() ) && ! file_exists( $carrot_item->full_source_path( $primary_key ) ) ) {
			$error_msg = sprintf( __( 'Primary file %s does not exist', 'carrot-bunnycdn-incoom-plugin' ), $carrot_item->full_source_path( $primary_key ) );

			return $this->return_handler_error( $error_msg );
		}

		// Get primary file's stats.
		$file_name     = wp_basename( $carrot_item->source_path() );
		$file_type     = wp_check_filetype_and_ext( $carrot_item->source_path(), $file_name );
		$allowed_types = get_allowed_mime_types();

		// check mime type of file is in allowed provider mime types
		if ( ! in_array( $file_type['type'], $allowed_types, true ) ) {
			$error_msg = sprintf( __( 'Mime type %s is not allowed', 'carrot-bunnycdn-incoom-plugin' ), $file_type['type'] );

			return $this->return_handler_error( $error_msg );
		}

		$default_acl = get_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', 'public');
		$private_acl = 'private';

		list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();

		$cacheControl = get_option('incoom_carrot_bunnycdn_incoom_plugin_cache_control', 'public, max-age=31536000');

		foreach ( $carrot_item->objects() as $object_key => $object ) {
			// Avoid attempting uploading to an item that doesn't have the primary file in place.

			if ( $primary_key !== $object_key && empty( $carrot_item->id() ) && ! isset( $manifest->objects[ $primary_key ] ) ) {
				continue;
			}

			$source_path = $carrot_item->full_source_path( $object_key );
			$source_key = $carrot_item->source_key( $object_key );

			// If the file has already been offloaded,
			// don't try and (fail to) re-offload if the file isn't available.
			if ( $this->in_offloaded_files( $object['source_file'], $options ) && ! file_exists( $source_path ) ) {
				continue;
			}

			/**
			 * This filter allows you to change the public/private status of an individual file associated
			 * with an uploaded item before it's uploaded to the provider.
			 *
			 * @param bool   $is_private Should the object be private?
			 * @param string $object_key A unique file identifier for a composite item, e.g. image's "size" such as full, small, medium, large.
			 * @param Item   $carrot_item The item being uploaded.
			 *
			 * @return bool
			 */
			$is_private = apply_filters( 'carrot_upload_object_key_as_private', $carrot_item->is_private( $object_key ), $object_key, $carrot_item );
			$carrot_item->set_is_private( $is_private, $object_key );

			$object_acl = $carrot_item->is_private( $object_key ) ? $private_acl : $default_acl;

			$args = array(
				'Bucket'       => $carrot_item->bucket(),
				'Key'          => $source_key,
				'SourceFile'   => $source_path,
				'ContentType'  => carrot_bunnycdn_incoom_plugin_Utils::get_mime_type( $object['source_file'] ),
				'CacheControl' => $cacheControl,
			);

			// TODO: Remove GZIP functionality.
			// Handle gzip on supported items
			if (
				$this->should_gzip_file( $source_path, $carrot_item->source_type() ) &&
				false !== ( $gzip_body = gzencode( file_get_contents( $source_path ) ) )
			) {
				unset( $args['SourceFile'] );

				$args['Body']            = $gzip_body;
				$args['ContentEncoding'] = 'gzip';
			}

			/**
			 * This filter allows you to change the arguments passed to the cloud storage SDK client when
			 * uploading a file to the bucket.
			 *
			 * Note: It is possible to change the destination 'Bucket' only while processing the primary object_key.
			 *       All other object_keys will use the same bucket as the item's primary object.
			 *       The 'Key' should be the "public" Key path. If a private prefix is configured
			 *       for use with signed CloudFront URLs or similar, that prefix will be added later.
			 *       A change to the 'Key' will only be handled when processing the primary object key.
			 *
			 * @param array  $args        Information to be sent to storage provider during offload (e.g. PutObject)
			 * @param int    $source_id   Original file's unique ID for its source type
			 * @param string $object_key  A unique file identifier for a composite item, e.g. image's "size" such as full, small, medium, large
			 * @param bool   $copy        True if the object is being copied between buckets
			 * @param array  $item_source Item source array containing source type and id
			 *
			 * @return array
			 */
			$args = apply_filters( 'carrot_object_meta', $args, $carrot_item->source_id(), $object_key, false, $carrot_item->get_item_source_array() );

			// If the bucket is changed by the filter while processing the primary object,
			// we should try and use that bucket for the item.
			// If the bucket name is invalid, revert to configured bucket but log it.
			// We don't abort here as ephemeral filesystems need to be accounted for,
			// and the configured bucket is at least known to work.
			if ( $primary_key === $object_key && $carrot_item->bucket() !== $args['Bucket'] && empty( $carrot_item->id() ) ) {

				$bucket = $Bucket;

				if ( $bucket ) {
					$region = $Region;

					if ( is_wp_error( $region ) ) {
						unset( $region );
					}
				}

				if ( empty( $bucket ) || empty( $region ) ) {
					$mesg = sprintf(
						__( 'Bucket name "%1$s" is invalid, using "%2$s" instead.', 'carrot-bunnycdn-incoom-plugin' ),
						$args['Bucket'],
						$carrot_item->bucket()
					);
					error_log( $mesg );
					$args['Bucket'] = $carrot_item->bucket();
				} else {
					$args['Bucket'] = $bucket;
					$carrot_item->set_bucket( $bucket );
					$carrot_item->set_region( $region );
				}

				unset( $bucket, $region );
			} elseif ( $primary_key === $object_key && $carrot_item->bucket() !== $args['Bucket'] && ! empty( $carrot_item->id() ) ) {
				$args['Bucket'] = $carrot_item->bucket();
				error_log( __( 'The bucket may not be changed via filters for a previously offloaded item.', 'carrot-bunnycdn-incoom-plugin' ) );
			} elseif ( $primary_key !== $object_key && $carrot_item->bucket() !== $args['Bucket'] ) {
				$args['Bucket'] = $carrot_item->bucket();
			}

			// If the Key has been changed for the primary object key, then that should be reflected in the item.
			if ( $primary_key === $object_key && $source_key !== $args['Key'] && empty( $carrot_item->id() ) ) {
				$prefix = carrot_bunnycdn_incoom_plugin_Utils::trailingslash_prefix( dirname( $args['Key'] ) );

				if ( $prefix === '.' ) {
					$prefix = '';
				}

				$carrot_item->update_path_prefix( $prefix );

				// If the filter tried to use a different filename too, log it.
				if ( wp_basename( $args['Key'] ) !== wp_basename( $source_key ) ) {
					$mesg = sprintf(
						__( 'The offloaded filename must not be changed, "%1$s" has been used instead of "%2$s".', 'carrot-bunnycdn-incoom-plugin' ),
						wp_basename( $source_key ),
						wp_basename( $args['Key'] )
					);
					error_log( $mesg );
				}
			} elseif ( $primary_key === $object_key && $source_key !== $args['Key'] && ! empty( $carrot_item->id() ) ) {
				$args['Key'] = $source_key;
				error_log( __( 'The key may not be changed via filters for a previously offloaded item.', 'carrot-bunnycdn-incoom-plugin' ) );
			} elseif ( $primary_key !== $object_key && $source_key !== $args['Key'] ) {
				$args['Key'] = $source_key;
			}

			// If ACL has been set, does the object's is_private need updating?
			$is_private = ! empty( $args['ACL'] ) && $private_acl === $args['ACL'] || $carrot_item->is_private( $object_key );
			$carrot_item->set_is_private( $is_private, $object_key );

			// Adjust the actual Key to add the private prefix before uploading.
			if ( $carrot_item->is_private( $object_key ) ) {
				$args['Key'] = $carrot_item->provider_key( $object_key );
			}

			// If we've already attempted to offload this source file, leave it out of the manifest.
			if ( in_array( md5( serialize( $args ) ), $this->attempted_upload ) ) {
				continue;
			}

			if ( $primary_key === $object_key ) {
				/**
				 * Actions fires when an Item's primary file might be offloaded.
				 *
				 * This action gives notice that an Item is being processed for upload to a bucket,
				 * and the given arguments represent the primary file's potential offload location.
				 * However, if the current process is for picking up extra files associated with the item,
				 * the indicated primary file may not actually be offloaded if it does not exist
				 * on the server but has already been offloaded.
				 *
				 * @param Item  $carrot_item The Item whose files are being offloaded.
				 * @param array $args       The arguments that could be used to offload the primary file.
				 */
				do_action( 'carrot_pre_upload_object', $carrot_item, $args );
			}

			if(empty($args['Bucket'])){
				$args['Bucket'] = $Bucket;
			}

			if(empty($args['Region'])){
				$args['Region'] = $Region;
			}

			if(!empty($args['Key'])){
				$args['Key'] = $this->rebuild_key($source_key);
			}

			$manifest->objects[ $object_key ]['args'] = $args;
		}
		
		return $manifest;
	}

	/**
	 * Upload item files to remote storage provider
	 *
	 * @param Item     $carrot_item
	 * @param Manifest $manifest
	 * @param array    $options
	 *
	 * @return bool|WP_Error
	 */
	protected function handle_item( carrot_bunnycdn_incoom_plugin_Item $carrot_item, carrot_bunnycdn_incoom_plugin_Manifest $manifest, array $options ) {
		
		list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();

		try {
			$provider_client = $aws_s3_client;
		} catch ( Exception $e ) {
			return false;
		}

		$urlOffloaded = '';

		try {
			foreach ( $manifest->objects as $object_key => &$object ) {
				$args = $object['args'];
	
				$args['Bucket'] = $Bucket;
				$args['Region'] = $Region;
	
				$object['upload_result'] = array(
					'status'  => null,
					'message' => null,
				);

				if ( ! file_exists( $args['SourceFile'] ) ) {
					$error_msg = sprintf( __( 'File %s does not exist', 'carrot-bunnycdn-incoom-plugin' ), $args['SourceFile'] );
	
					$object['upload_result']['status']  = self::STATUS_FAILED;
					$object['upload_result']['message'] = $error_msg;
	
					// If the missing source file is the primary file, abort the whole process.
					if ( carrot_bunnycdn_incoom_plugin_Item::primary_object_key() === $object_key ) {
						return false;
					}
	
					continue;
				}

				$this->attempted_upload[] = md5( serialize( $args ) );
	
				// Try to do the upload
				try {
					$result = $provider_client->upload_object( $args );
					if(!empty($result)){
						$pos = strpos($args['SourceFile'], $carrot_item->source_path());
						if ($pos !== false) {
							$urlOffloaded = $provider_client->getObjectUrl( $Bucket, $Region, $args['Key']);
						}

						$this->files_uploaded[] = $args['SourceFile'];
						$object['upload_result']['status'] = self::STATUS_OK;
					}
				} catch ( Exception $e ) {
					$error_msg = sprintf( __( 'Error uploading %1$s to provider: %2$s', 'carrot-bunnycdn-incoom-plugin' ), $args['SourceFile'], $e->getMessage() );
					$object['upload_result']['status']  = self::STATUS_FAILED;
					$object['upload_result']['message'] = $error_msg;
				}
			}
		} catch ( Exception $e ) {
			return false;
		}

		try {
			if($urlOffloaded != ''){
				$settings = carrot_bunnycdn_incoom_plugin_whichtype_settings();
				$provider = isset($settings['provider']) ? $settings['provider'] : 'aws';
				$data = wp_get_attachment_metadata( $carrot_item->source_id() );
	
				$provider_object = array(
					'provider' => $provider,
					'region'   => $Region,
					'bucket'   => $Bucket,
					'key' 	   => $carrot_item->source_path(),
					'data'     => $data
				);
				update_post_meta( $carrot_item->source_id(), '_incoom_carrot_bunnycdn_amazonS3_info', $provider_object );
				update_post_meta( $carrot_item->source_id(), '_wp_incoom_carrot_bunnycdn_s3_wordpress_path', '1' );
				update_post_meta( $carrot_item->source_id(), '_wp_incoom_carrot_bunnycdn_s3_path', $urlOffloaded );

				try {
					carrot_bunnycdn_incoom_plugin_build_webp_function( $carrot_item->source_id() );
				} catch (\Throwable $th) {}
			}
		} catch (\Throwable $th) {}

		return true;
	}

	/**
	 * Handle local housekeeping after uploads.
	 *
	 * @param Item     $carrot_item
	 * @param Manifest $manifest
	 * @param array    $options
	 *
	 * @return bool|WP_Error
	 */
	protected function post_handle( carrot_bunnycdn_incoom_plugin_Item $carrot_item, carrot_bunnycdn_incoom_plugin_Manifest $manifest, array $options ) {
		$item_objects = $carrot_item->objects();
		$errors       = new WP_Error;
		$i            = 1;

		// Reconcile the Item's objects with their manifest status.
		foreach ( $item_objects as $object_key => $object ) {
			// If there was no attempt made to offload the file,
			// then remove it from list of offloaded objects.
			// However, if the source file has previously been offloaded,
			// we should just skip any further processing of it
			// as the associated objects are still offloaded.
			if ( ! isset( $manifest->objects[ $object_key ]['upload_result']['status'] ) ) {
				if ( empty( $options['offloaded_files'][ $object['source_file'] ] ) ) {
					unset( $item_objects[ $object_key ] );
				}
				continue;
			}

			// If the upload didn't succeed, we need to remove the object/size from the item.
			// However, if the source file has previously been offloaded, we should just log the error.
			if ( $manifest->objects[ $object_key ]['upload_result']['status'] !== self::STATUS_OK ) {
				if ( empty( $options['offloaded_files'][ $object['source_file'] ] ) ) {
					unset( $item_objects[ $object_key ] );
				}
				$errors->add( 'upload-object-' . $i++, $manifest->objects[ $object_key ]['upload_result']['message'] );
			}
		}

		// Set the potentially changed list of offloaded objects.
		$carrot_item->set_objects( $item_objects );

		// Only save if we have the primary file uploaded.
		
		if ( isset( $item_objects[ carrot_bunnycdn_incoom_plugin_Item::primary_object_key() ] ) ) {
			$carrot_item->save();
		}

		/**
		 * Fires action after uploading finishes
		 *
		 * @param Item $carrot_item The item that was just uploaded
		 */
		do_action( 'carrot_post_upload_item', $carrot_item );

		if ( count( $errors->get_error_codes() ) ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Should gzip file
	 *
	 * @param string $file_path
	 * @param string $source_type
	 *
	 * @return bool
	 */
	protected function should_gzip_file( $file_path, $source_type ) {

		$gzip = get_option('incoom_carrot_bunnycdn_incoom_plugin_gzip', '');
	
		if(empty($gzip)){
			return false;
		}

		$file_type = wp_check_filetype_and_ext( $file_path, $file_path );
		$mimes     = $this->get_mime_types_to_gzip( $source_type );

		if ( in_array( $file_type, $mimes ) && is_readable( $file_path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get mime types to gzip
	 *
	 * @param string $source_type
	 *
	 * @return array
	 */
	protected function get_mime_types_to_gzip( $source_type ) {
		/**
		 * Return array of mime types that needs to be gzipped before upload
		 *
		 * @param array  $mime_types    The array of mime types
		 * @param bool   $media_library If the uploaded file is part of the media library
		 * @param string $source_type   The source type of the uploaded item
		 */
		return carrot_bunnycdn_incoom_plugin_get_mime_types_to_gzip('media_library' === $source_type ? true : false);
	}

	/**
	 * Has the given file name already been offloaded?
	 *
	 * @param string $filename
	 * @param array  $options
	 *
	 * @return bool
	 */
	private function in_offloaded_files( $filename, $options ) {
		if ( empty( $options['offloaded_files'] ) ) {
			return false;
		}

		return array_key_exists( $filename, $options['offloaded_files'] );
	}
}