<?php
if (!defined('ABSPATH')) {exit;}

/**
 * CLI.
 *
 * @since      2.0.33
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */
class carrot_bunnycdn_incoom_plugin_CLI {
    
    /**
	 * One click
	 *
	 * @since  2.0.33
	 * @author incoom
	 */
	public function one_click($args, $assoc_args) {
        $text_action_error = esc_html__('Please, choose an action: 1 - copy_files_to_bucket, 2 - remove_files_from_server, 3 - remove_files_from_bucket, 4 - download_files_from_bucket', 'carrot-bunnycdn-incoom-plugin');
        if(empty($args[0])){
            WP_CLI::warning( $text_action_error );
            return;
        }else{
            $action_scan = $args[0];
		    if($action_scan < 1 || $action_scan > 4){
                WP_CLI::warning( $text_action_error );
                return;
            }

            try{
				// Clear all scheduled
				carrot_bunnycdn_incoom_plugin_after_action_scheduler_completed();
			} catch (\Throwable $th) {}

            $percentOffload = 0;
			$count = [];
            $text_action = '';

            switch ($action_scan) {
                case 1:
                    $text_action = carrot_bunnycdn_incoom_plugin_get_sync_action_title('copy_files_to_bucket');
                    try {
                        $blog_id = get_current_blog_id();
                        $source_type_classes = carrot_bunnycdn_incoom_plugin_get_source_type_classes();
                        foreach($source_type_classes as $source_type => $class){
                            $items = $class::verify_missing_source_ids(0);
                            $progress = \WP_CLI\Utils\make_progress_bar( "{$text_action}: {$source_type}", count($items) );
                            foreach ( $items as $source_id ) {
                                switch($source_type){
                                    case "media-library":
                                        carrot_bunnycdn_incoom_plugin_copy_to_s3_function( $source_id );
                                        break;
                                    case "bboss-user-avatar":
                                    case "bboss-user-cover":
                                    case "bboss-group-avatar":
                                    case "bboss-group-cover":
                                        if ( incoom_carrot_bunnycdn_incoom_plugin_is_bb_activate() ) {
                                            $source_types 	= carrot_bunnycdn_incoom_plugin_Buddyboss::get_resource_type();
                                            $class 			= $source_types[$source_type]['class'];
                                            $carrot_item 	= $class::create_from_source_id( $source_id );
                        
                                            $upload_handler = carrot_bunnycdn_incoom_plugin_get_item_handler('upload');
                                            $results  = $upload_handler->handle( $carrot_item );
                        
                                            if ( is_wp_error( $results ) ) {
                                                return false;
                                            }
                        
                                            $carrot_item->save();
                                        }
                                        break;
                                    default:
                                        break;
                                }
                                $progress->tick();
                            }
                            $progress->finish();
                        }
                    } catch (\Throwable $th) {}
                    break;
                case 2:
                    # code...
                    $text_action = carrot_bunnycdn_incoom_plugin_get_sync_action_title('remove_files_from_server');
                    try {
                        $blog_id = get_current_blog_id();
                        $source_type_classes = carrot_bunnycdn_incoom_plugin_get_source_type_classes();
                        foreach($source_type_classes as $source_type => $class){
                            $items = carrot_bunnycdn_incoom_plugin_items_local_removed($source_type, 0, false, false);
                            $progress = \WP_CLI\Utils\make_progress_bar( "{$text_action}: {$source_type}", count($items) );
                            foreach ( $items as $source_id ) {
                                carrot_bunnycdn_incoom_plugin_remove_from_server_function($source_id);
                                $progress->tick();
                            }
                            $progress->finish();
                        }
                    } catch (\Throwable $th) {}
                    break;
                case 3:
                    # code...
                    $text_action = carrot_bunnycdn_incoom_plugin_get_sync_action_title('remove_files_from_bucket');
                    try {
                        $blog_id = get_current_blog_id();
                        $source_type_classes = carrot_bunnycdn_incoom_plugin_get_source_type_classes();
                        foreach($source_type_classes as $source_type => $class){
                            $items = $class::get_source_ids(null, 0, false);
                            $progress = \WP_CLI\Utils\make_progress_bar( "{$text_action}: {$source_type}", count($items) );
                            foreach ( $items as $source_id ) {
                                
                                switch($source_type){
                                    case "media-library":
                                        carrot_bunnycdn_incoom_plugin_remove_from_s3_function( $source_id );
                                        break;
                                    case "bboss-user-avatar":
                                    case "bboss-user-cover":
                                    case "bboss-group-avatar":
                                    case "bboss-group-cover":
                                        if ( incoom_carrot_bunnycdn_incoom_plugin_is_bb_activate() ) {
                                            $source_types 	= carrot_bunnycdn_incoom_plugin_Buddyboss::get_resource_type();
                                            $class 			= $source_types[$source_type]['class'];
                                            $carrot_item 	= $class::get_by_source_id( $source_id );
                        
                                            try {
                                                if(empty( $carrot_item->id() )){
                                                    $carrot_item = $class::create_from_source_id( $source_id );
                                                }
                                            } catch( Exception $e){}
                        
                                            $remove_provider_handler = carrot_bunnycdn_incoom_plugin_get_item_handler( carrot_bunnycdn_incoom_plugin_Remove_Provider_Handler::get_item_handler_key_name() );
                                            $remove_provider_handler->handle( $carrot_item );
                                        }
                                        break;
                                    default:
                                        break;
                                }
                                $progress->tick();
                            }
                            $progress->finish();
                        }	
                    } catch (\Throwable $th) {}
                    break;
                case 4:
                    # code...
                    $text_action = carrot_bunnycdn_incoom_plugin_get_sync_action_title('download_files_from_bucket');
                    try {
                        $blog_id = get_current_blog_id();
                        $source_type_classes = carrot_bunnycdn_incoom_plugin_get_source_type_classes();
                        foreach($source_type_classes as $source_type => $class){
            
                            $args = array( 
                                'fields'        	=> 'ids',
                                'post_type' 		=> 'attachment',
                                'post_status' 		=> 'inherit',
                                'posts_per_page' 	=> -1,
                                'meta_query' 		=> [
                                    'relation' => 'OR',
                                    [
                                        'key'     => '_wp_incoom_carrot_bunnycdn_copy_to_server',
                                        'value'   => '1',
                                        'compare' => 'NOT EXISTS',
                                    ],
                                    [
                                        'key'     => '_wp_incoom_carrot_bunnycdn_copy_to_server',
                                        'value'   => '1',
                                        'compare' => '!=',
                                    ],
                                ]
                            );
                            $query = new WP_Query($args);
                            $found_posts = $query->found_posts;
                            if($found_posts > 0){
                                $progress = \WP_CLI\Utils\make_progress_bar( "{$text_action}: {$source_type}", $found_posts );
                                foreach ( carrot_bunnycdn_incoom_plugin_lazy_loop($query) as $post ) {
                                    $source_id = get_the_ID();
                                    carrot_bunnycdn_incoom_plugin_copy_to_server_from_s3_function($source_id);
                                    $progress->tick();
                                }
                                $progress->finish();
                            }
                        }
                    } catch (\Throwable $th) {}
                    break;
                default:
                    # code...
                    break;
            }

        }
	}
}