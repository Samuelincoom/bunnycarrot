<?php

class carrot_bunnycdn_incoom_plugin_Remove_Provider_Handler extends carrot_bunnycdn_incoom_plugin_Item_Handler {
	/**
	 * @var string
	 */
	protected static $item_handler_key = 'remove-provider';

	/**
	 * The default options that should be used if none supplied.
	 *
	 * @return array
	 */
	public static function default_options() {
		return array(
			'object_keys'     => array(),
			'offloaded_files' => array(),
		);
	}

	/**
	 * Create manifest for removal from provider.
	 *
	 * @param Item  $carrot_item
	 * @param array $options
	 *
	 * @return Manifest|WP_Error
	 */
	protected function pre_handle( carrot_bunnycdn_incoom_plugin_Item $carrot_item, array $options ) {
		$manifest = new carrot_bunnycdn_incoom_plugin_Manifest();
		$paths    = array();

		if ( ! empty( $options['object_keys'] ) && ! is_array( $options['object_keys'] ) ) {
			return $this->return_handler_error( __( 'Invalid object_keys option provided.', 'carrot-bunnycdn-incoom-plugin' ) );
		}

		if ( ! empty( $options['offloaded_files'] ) && ! is_array( $options['offloaded_files'] ) ) {
			return $this->return_handler_error( __( 'Invalid offloaded_files option provided.', 'carrot-bunnycdn-incoom-plugin' ) );
		}

		if ( ! empty( $options['object_keys'] ) && ! empty( $options['offloaded_files'] ) ) {
			return $this->return_handler_error( __( 'Providing both object_keys and offloaded_files options is not supported.', 'carrot-bunnycdn-incoom-plugin' ) );
		}

		if ( empty( $options['offloaded_files'] ) ) {
			foreach ( $carrot_item->objects() as $object_key => $object ) {
				if ( 0 < count( $options['object_keys'] ) && ! in_array( $object_key, $options['object_keys'] ) ) {
					continue;
				}
				$paths[ $object_key ] = $carrot_item->full_source_path( $object_key );
			}
		} else {
			foreach ( $options['offloaded_files'] as $filename => $object ) {
				$paths[ $filename ] = $carrot_item->full_source_path_for_filename( $filename );
			}
		}

		/**
		 * Filters array of source files before being removed from provider.
		 *
		 * @param array $paths       Array of local paths to be removed from provider
		 * @param Item  $carrot_item  The Item object
		 * @param array $item_source The item source descriptor array
		 */
		$paths = apply_filters( 'carrot_remove_source_files_from_provider', $paths, $carrot_item, $carrot_item->get_item_source_array() );
		$paths = array_unique( $paths );

		// Remove local source paths that other items may have offloaded.
		$paths = $carrot_item->remove_duplicate_paths( $carrot_item, $paths );

		// Nothing to do, shortcut out.
		if ( empty( $paths ) ) {
			return $manifest;
		}

		foreach ( $paths as $object_key => $path ) {
			$manifest->objects[] = array(
				'Key' => $carrot_item->source_path( $object_key ),
			);
		}

		return $manifest;
	}

	/**
	 * Delete provider objects described in the manifest object array
	 *
	 * @param Item     $carrot_item
	 * @param Manifest $manifest
	 * @param array    $options
	 *
	 * @return bool|WP_Error
	 */
	protected function handle_item( carrot_bunnycdn_incoom_plugin_Item $carrot_item, carrot_bunnycdn_incoom_plugin_Manifest $manifest, array $options ) {
		
		list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();

		$chunks = array_chunk( $manifest->objects, 1000 );

		try{
			foreach ( $chunks as $chunk ) {
				$aws_s3_client->delete_objects( $Bucket, $Region, ['Objects' => $chunk] );
			}
		}catch(Exception $e){
			$error_msg = sprintf( __( 'Error removing files from bucket: %s', 'carrot-bunnycdn-incoom-plugin' ), $e->getMessage() );
			error_log($error_msg);
			return false;
		}

		try {
			delete_post_meta( $carrot_item->source_id(), 'incoom_carrot_verify_offloaded_status');
			delete_post_meta( $carrot_item->source_id(), '_incoom_carrot_bunnycdn_amazonS3_info');
			delete_post_meta( $carrot_item->source_id(), '_wp_incoom_carrot_bunnycdn_s3_wordpress_path');
			delete_post_meta( $carrot_item->source_id(), '_wp_incoom_carrot_bunnycdn_s3_path');
		} catch (\Throwable $e) {}
		
		try {
			$carrot_item->delete();
		} catch (\Throwable $e) {
			$error_msg = sprintf( __( 'Error removing carrot item: %s', 'carrot-bunnycdn-incoom-plugin' ), $e->getMessage() );
			error_log($error_msg);
		}

		return true;
	}

	/**
	 * Perform post handle tasks.
	 *
	 * @param Item     $carrot_item
	 * @param Manifest $manifest
	 * @param array    $options
	 *
	 * @return bool
	 */
	protected function post_handle( carrot_bunnycdn_incoom_plugin_Item $carrot_item, carrot_bunnycdn_incoom_plugin_Manifest $manifest, array $options ) {
		return true;
	}
}