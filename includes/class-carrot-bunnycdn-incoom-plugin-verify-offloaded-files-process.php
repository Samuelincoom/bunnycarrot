<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Verify Offloaded Files Background Process
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.22
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Verify_Offloaded_Files_Process extends carrot_bunnycdn_incoom_plugin_Background_Process{

	/**
	 * @var string
	 */
	protected $action = 'verify_offloaded_files';

	/**
	 * @var int
	 */
	private $offloaded = 0;

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		add_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_verify_offloaded_files', [ $this, 'task' ] );
	}

	/**
	 * Process items chunk.
	 *
	 * @param string $source_type
	 * @param array  $source_ids
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	protected function process_items_chunk( $source_type, $source_ids ) {
		$processed = array();

		foreach ( $source_ids as $source_id ) {
			try {
				$this->handle_item( $source_type, $source_id );
			} catch (Exception $e) {}

			// Whether actually offloaded or not, we've processed the item.
			$processed[] = $source_id;
		}

		return $processed;
	}

	/**
	 * Verify Uploaded item to provider.
	 *
	 * @param string $source_type
	 * @param int    $source_id
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function handle_item( $source_type, $source_id ) {

		$class      = carrot_bunnycdn_incoom_plugin_get_source_type_name();
		$carrot_item = null;

		try {
			$carrot_item = $class::get_by_source_id( $source_id );
		} catch (\Throwable $th) {}

		try {
			if ( empty( $carrot_item ) || empty( $carrot_item->id() ) ) {
				$carrot_item = $class::create_from_source_id( $source_id );
				if ( is_wp_error( $carrot_item ) ) {
					return false;
				}else{
					$carrot_item->save();
				}
			}
		} catch (\Throwable $th) {}

		if($carrot_item){

			$paths = carrot_bunnycdn_incoom_plugin_Utils::get_attachment_file_paths( $source_id, false );
			if ( empty( $paths ) ) {
				return false;
			}

			$fullsize_paths = carrot_bunnycdn_incoom_plugin_Utils::fullsize_paths( $paths );
			if ( empty( $fullsize_paths ) ) {
				return false;
			}

			$fullsize_exists  = false;
			$fullsize_missing = false;
			$urlOffloaded = '';

			list( $aws_s3_client, $Bucket, $Region, $basedir_absolute ) = carrot_bunnycdn_incoom_plugin_whichtype_info();

			foreach ( $fullsize_paths as $path ) {
				$key = $carrot_item->key( wp_basename( $path ) );
	
				if ( $aws_s3_client->does_object_exist( $Bucket, $Region, $key ) ) {
					$fullsize_exists = true;
					$urlOffloaded = $aws_s3_client->getObjectUrl( $Bucket, $Region, $key);
				} else {
					$fullsize_missing = true;
				}
			}

			// A full sized file has not been found, remove metadata.
			if ( ! $fullsize_exists ) {
				$carrot_item->delete();
				delete_post_meta( $carrot_item->source_id(), 'incoom_carrot_verify_offloaded_status');
				delete_post_meta( $carrot_item->source_id(), '_incoom_carrot_bunnycdn_amazonS3_info');
				delete_post_meta( $carrot_item->source_id(), '_wp_incoom_carrot_bunnycdn_s3_wordpress_path');
				delete_post_meta( $carrot_item->source_id(), '_wp_incoom_carrot_bunnycdn_s3_path');
				return false;
			}

			try {
				if($urlOffloaded != ''){
					// At least one full sized file has been found, set as verified.
					$carrot_item->set_is_verified( true );
					$carrot_item->save();

					update_post_meta( $carrot_item->source_id(), 'incoom_carrot_verify_offloaded_status', 1 );

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
				}
			} catch (\Throwable $th) {}

			if ( $fullsize_missing ) {
				return false;
			}
		}
	}

	/**
	 * Task
	 * 
	 * @return mixed
	 */
	public function task() {

		if(!incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup()){
			return false;
		}

		$action_scan = get_option('incoom_carrot_bunnycdn_incoom_plugin_action');
		if(!empty($action_scan)){
			return false;
		}

		try {
			$blog_id = get_current_blog_id();
			$source_type_classes = carrot_bunnycdn_incoom_plugin_get_source_type_classes();
			foreach($source_type_classes as $source_type => $class){
				$items = $class::verify_missing_source_ids($this->limit);
				if(count($items) == 0){
					$items = $class::get_source_ids(null, $this->limit, false, null, false);
				}

				$chunks = array_chunk( $items, $this->chunk );
				foreach ( $chunks as $chunk ) {
					try {
						$this->process_items_chunk($source_type, $chunk);
					} catch (\Throwable $th) {
						error_log("Error copy_attachments_to_cloud: ". $th->getMessage());
					}
				}
			}
		} catch (\Throwable $th) {
			error_log("Error copy_attachments_to_cloud: ". $th->getMessage());
		}

		return false;
	}
}
?>