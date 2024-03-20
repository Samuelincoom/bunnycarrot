<?php

class carrot_bunnycdn_incoom_plugin_Download_Handler extends carrot_bunnycdn_incoom_plugin_Item_Handler {
	/**
	 * @var string
	 */
	protected static $item_handler_key = 'download';

	/**
	 * The default options that should be used if none supplied.
	 *
	 * @return array
	 */
	public static function default_options() {
		return array(
			'full_source_paths' => array(),
		);
	}

	/**
	 * Prepare a manifest based on the item.
	 *
	 * @param Item  $carrot_item
	 * @param array $options
	 *
	 * @return Manifest
	 */
	protected function pre_handle( carrot_bunnycdn_incoom_plugin_Item $carrot_item, array $options ) {
		$manifest   = new carrot_bunnycdn_incoom_plugin_Manifest();
		$file_paths = array();

		foreach ( $carrot_item->objects() as $object_key => $object ) {
			$file = $carrot_item->full_source_path( $object_key );

			if ( 0 < count( $options['full_source_paths'] ) && ! in_array( $file, $options['full_source_paths'] ) ) {
				continue;
			}

			$file_paths[ $object_key ] = $file;
		}

		$file_paths = array_unique( $file_paths );

		foreach ( $file_paths as $object_key => $file_path ) {
			if ( ! file_exists( $file_path ) ) {
				$manifest->objects[] = array(
					'args' => array(
						'Bucket' => $carrot_item->bucket(),
						'Key'    => $carrot_item->source_path( $object_key ),
						'SaveAs' => $file_path,
					),
				);
			}
		}

		return $manifest;
	}

	/**
	 * Perform the downloads.
	 *
	 * @param Item     $carrot_item
	 * @param Manifest $manifest
	 * @param array    $options
	 *
	 * @return boolean|WP_Error
	 * @throws Exception
	 */
	protected function handle_item( carrot_bunnycdn_incoom_plugin_Item $carrot_item, carrot_bunnycdn_incoom_plugin_Manifest $manifest, array $options ) {
		
		if ( ! empty( $manifest->objects ) ) {
			// This test is "late" so that we don't raise the error if the local files exist anyway.
			// If the provider of this item is different from what's currently configured,
			// we'll return an error.
			$current_provider = carrot_bunnycdn_incoom_plugin_whichtype();
			if ( ! empty( $current_provider ) && $current_provider::get_provider_key_name() !== $carrot_item->provider() ) {
				$error_msg = sprintf(
					__( '%1$s with ID %d is offloaded to a different provider than currently configured', 'carrot-bunnycdn-incoom-plugin' ),
					carrot_bunnycdn_incoom_plugin_get_source_type_name( $carrot_item->source_type() ),
					$carrot_item->source_id()
				);

				return $this->return_handler_error( $error_msg );
			} else {
				list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();
				$provider_client = $aws_s3_client;

				$is_permission = get_post_meta($carrot_item->source_id(), 'carrot_downloadable_file_permission', true);

				foreach ( $manifest->objects as &$manifest_object ) {
					// Save object to a file.
					if($is_permission == 'yes'){
						$this->set_object_permission( $provider_client, $Bucket, $Region, $manifest_object['args'] );
					}

					$result = $this->download_object( $provider_client, $manifest_object['args'] );
					$manifest_object['download_result']['status'] = self::STATUS_OK;

					if ( is_wp_error( $result ) ) {
						$manifest_object['download_result']['status']  = self::STATUS_FAILED;
						$manifest_object['download_result']['message'] = $result->get_error_message();
					}
				}
			}
		}

		return true;
	}

	/**
	 * Perform post handle tasks. Log errors, update filesize totals etc.
	 *
	 * @param Item     $carrot_item
	 * @param Manifest $manifest
	 * @param array    $options
	 *
	 * @return bool|WP_Error
	 */
	protected function post_handle( carrot_bunnycdn_incoom_plugin_Item $carrot_item, carrot_bunnycdn_incoom_plugin_Manifest $manifest, array $options ) {
		$carrot_item->update_filesize_after_download_local();
		return true;
	}

	/**
	 * Set permission an object from provider.
	 */
	private function set_object_permission( $provider_client, $Bucket, $Region, $object ) {
		try{
			$key = $object['Key'];
			$array_aux = explode( '/', $key );
			$main_file = array_pop( $array_aux );
			$array_files[] = implode( "/", $array_aux );
			$array_files[] = $main_file;

			$provider_client->set_object_permission( $Bucket, $Region, $array_files );
		}catch(Exception $e){}
	}

	/**
	 * Download an object from provider.
	 *
	 * @param Storage_Provider $provider_client
	 * @param array            $object
	 *
	 * @return bool|WP_Error
	 */
	private function download_object( $provider_client, $object ) {
		// Make sure the local directory exists.
		$dir = dirname( $object['SaveAs'] );
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			$error_msg = sprintf( __( 'The local directory %s does not exist and could not be created.', 'carrot-bunnycdn-incoom-plugin' ), $dir );
			$error_msg = sprintf( __( 'There was an error attempting to download the file %1$s from the bucket: %2$s', 'carrot-bunnycdn-incoom-plugin' ), $object['Key'], $error_msg );

			return $this->return_handler_error( $error_msg, true );
		}

		try {
			$provider_client->downloadObject( $object );
		} catch ( Exception $e ) {
			// If storage provider file doesn't exist, an empty local file will be created, clean it up.
			@unlink( $object['SaveAs'] );

			$error_msg = sprintf( __( 'Error downloading %1$s from bucket: %2$s', 'carrot-bunnycdn-incoom-plugin' ), $object['Key'], $e->getMessage() );

			return $this->return_handler_error( $error_msg, true );
		}

		return true;
	}
}