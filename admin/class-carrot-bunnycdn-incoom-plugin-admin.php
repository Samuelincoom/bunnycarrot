<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/admin
 * @author     incoom <incoomsamuel@gmail.com>
 */
class carrot_bunnycdn_incoom_plugin_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Keep track of items that are being updated multiple times in one request. I.e. when WP
	 * calls wp_update_attachment_metadata repeatedly during thumbnail generation
	 *
	 * @var array
	 */
	private $items_in_progress = [];

	/**
	 * Keep track of items that has been replaced by an edit image operation
	 *
	 * @var array
	 */
	protected $replaced_object_keys = [];

	/**
	 * @var bool
	 */
	private $wait_for_generate_attachment_metadata = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->wait_for_generate_attachment_metadata = true;
	}

	public function cloud_served_filtering($post){
		$current_screen = get_current_screen();
		if ( $current_screen->base !== 'upload' ) return;
		$selected = 'all';
	    $request_attr = 'carrot_served';
	    if ( isset($_REQUEST[$request_attr]) ) {
	      $selected = $_REQUEST[$request_attr];
	    }

	    echo '<select id="carrot_served" name="carrot_served">';
	    echo '<option value="all">' . esc_html__( 'All files cloud vs server', 'carrot-bunnycdn-incoom-plugin' ) . ' </option>';
	    echo '<option value="1" '.selected($selected, 1, false).'>' . esc_html__( 'All files stored in the server', 'carrot-bunnycdn-incoom-plugin' ) . ' </option>';
	    echo '<option value="2" '.selected($selected, 2, false).'>' . esc_html__( 'All files stored in the cloud', 'carrot-bunnycdn-incoom-plugin' ) . ' </option>';
	    echo '</select>';
	}

	public function ajax_query_attachments_args($where){
		
		if(isset($_REQUEST['query'])){

			$request_query = $_REQUEST['query'];
			
			if( !isset($request_query['carrot_served']) ){
		    	return $where;
		    }

			global $wpdb;
			$table_name = $wpdb->get_blog_prefix() . carrot_bunnycdn_incoom_plugin_ITEMS_TABLE;
			$table_posts = $wpdb->get_blog_prefix() . "posts";

			// Stored cloud
		    if( 1 == $request_query['carrot_served'] ){
		    	$where .= " AND ( " . $table_posts . ".ID NOT IN (
					SELECT items.source_id
					FROM " . $table_name . " AS items
					WHERE items.source_id = " . $table_posts . ".ID
				))";
		    }

			// Stored local
		    if( 2 == $request_query['carrot_served'] ){
		    	$where .= " AND (" . $table_posts . ".ID IN (
					SELECT items.source_id
					FROM " . $table_name . " AS items
					WHERE items.source_id = " . $table_posts . ".ID
				))";
		    }
		}

		return $where;
	}

	public function cloud_served_filter_request_query($query){
		if( !(is_admin() AND $query->is_main_query()) ){ 
	      return $query;
	    }

	    if( isset($query->query['post_type']) && 'attachment' !== $query->query['post_type'] ){
	      	return $query;
	    }

	    if( !isset($_REQUEST['carrot_served']) ){
	    	return $query;
	    }

	    if( 1 == $_REQUEST['carrot_served'] ){
		    $query->set( 'meta_query', array(
	    		'relation' => 'OR',
		        array(
		            'key'     => '_wp_incoom_carrot_bunnycdn_s3_path',
		            'compare' => 'NOT EXISTS',
		        ),
		        array(
		            'key'     => '_wp_incoom_carrot_bunnycdn_s3_path',
		            'value'   => '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used',
		            'compare' => '==',
		        ),
		    ) );
	    }

	    if( 2 == $_REQUEST['carrot_served'] ){
		    $query->set( 'meta_query', array(
	            array(
	                'key'     => '_wp_incoom_carrot_bunnycdn_s3_path',
	                'compare' => '!=',
	                'value'   => '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used',
	                'type'    => 'CHAR',
	            )
	        ) );
	    }
	    
		return $query;
	}

	public function sync_notice(){
		$page = isset($_GET['page']) && !empty($_GET['page']) ? esc_html($_GET['page']) : '';
		if($page !== 'carrot_bunnycdn_incoom_plugin_one_click'){
			$has_bk_option = false;
			$sync_status = get_option('incoom_carrot_bunnycdn_incoom_plugin_synced_status', 0);
			if($sync_status > 0){
				$has_bk_option = true;
			}

			if($has_bk_option){
				$media_count = carrot_bunnycdn_incoom_plugin_get_media_counts();
				$percen = $media_count['total'] > 0 ? round($media_count['offloaded'] / $media_count['total'] * 100) : 0;
				$class 	 = 'carrot-sync-notice notice notice-error';
				$text    = esc_html__( 'Kill sync process.', 'carrot-bunnycdn-incoom-plugin' );
				$link    = esc_url( add_query_arg( array('page' => 'carrot_bunnycdn_incoom_plugin', 'carrot_action' => 'kill_sync'), admin_url('admin.php') ) );
				$message = esc_html__( 'carrot-bunnycdn-incoom-plugin Synchronize:', 'carrot-bunnycdn-incoom-plugin' );
				echo '<div class="'.esc_attr($class).'">
						<div class="carrot-sync-notice-wrap">
							<div class="carrot-sync-notice-title">'.esc_html($message).'</div>
							<div class="iziToastloading spin_loading"></div>
							<div class="progress-bar">
								<span id="percent" style="line-height: 9px;height: 9px;right: -5%;"></span>
								<span class="bar" style="height: 9px;"><span class="progress"></span></span>
							</div>
							<div class="current-sync-process progress_count">'.esc_html($percen).'%</div> 
							<a href="'.esc_url($link).'" class="button-primary"><strong>'.esc_html($text).'</strong></a>
						</div>
					</div>';
			}
		}
	}

	public function hanlder_kill_sync_process(){
		if(isset($_GET['carrot_action']) && $_GET['carrot_action'] == 'kill_sync'){
			
			update_option('incoom_carrot_bunnycdn_incoom_plugin_synced_data', []);
			update_option('incoom_carrot_bunnycdn_incoom_plugin_synced_status', 0);

			try {
				if ( function_exists( 'as_unschedule_all_actions' ) ) {
					as_unschedule_all_actions( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_sync_between_cloud' );
				}
			} catch (\Throwable $th) {}
			
			wp_redirect(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin&tab=sync'));
			exit;
		}
	}

	/**
	 * Warning if setup not complete.
	 *
	 * @since    1.0.0
	 */
	public function setup_notice(){
		
		$class = 'notice notice-error';
		$page = isset($_GET['page']) && !empty($_GET['page']) ? esc_html($_GET['page']) : '';

		if(!incoom_carrot_bunnycdn_incoom_plugin_version_check()){
			$message = wp_kses( __( "<strong>carrot-bunnycdn-incoom-plugin</strong>: Your PHP version is low. Please, upgrade PHP 7.0 or higher.", 'carrot-bunnycdn-incoom-plugin' ), array( 'strong' => array() ) );
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}else{

			$active 		= get_option('incoom_carrot_bunnycdn_incoom_plugin_license_active');
			$emailAddress 	= get_option('incoom_carrot_bunnycdn_incoom_plugin_license_email');
			$purchase_key 	= get_option('incoom_carrot_bunnycdn_incoom_plugin_license_key');
		    if ( empty($purchase_key) || empty($emailAddress) || $active != '1' ){
		    	return '';
		    }

			if ( ! incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {
				
				if(empty($page) || ($page != 'carrot_bunnycdn_incoom_plugin')){
					$text    = esc_html__( 'Click here!', 'carrot-bunnycdn-incoom-plugin' );
					$link    = esc_url( add_query_arg( array('page' => 'carrot_bunnycdn_incoom_plugin'), admin_url('admin.php') ) );

					$message = wp_kses( __( "<strong>carrot-bunnycdn-incoom-plugin</strong>: Either your settings are incorrect, or the Bunny CDN bucket does not exist. go check it out.", 'carrot-bunnycdn-incoom-plugin' ), array( 'strong' => array() ) );
					
					printf( '<div class="%1$s"><p>%2$s <a href="%3$s"><strong>%4$s</strong></a></p></div>', $class, $message, $link, $text );
				}
			}
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in carrot_bunnycdn_incoom_plugin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The carrot_bunnycdn_incoom_plugin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/carrot-bunnycdn-incoom-plugin-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'izitoast', plugin_dir_url( __FILE__ ) . 'css/iziToast.min.css', array(), $this->version, 'all' );
		
		$page = isset($_GET['page']) && !empty($_GET['page']) ? esc_html($_GET['page']) : '';
		if(!empty($page) && $page === 'carrot_bunnycdn_incoom_plugin_one_click'){
			wp_enqueue_style( 'smart_wizard_all', plugin_dir_url( __FILE__ ) . 'css/smart_wizard_all.min.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in carrot_bunnycdn_incoom_plugin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The carrot_bunnycdn_incoom_plugin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$page = isset($_GET['page']) && !empty($_GET['page']) ? esc_html($_GET['page']) : '';
		$provider = carrot_bunnycdn_incoom_plugin_whichtype();
		$baseUrl = get_option('incoom_carrot_bunnycdn_incoom_plugin_aws_connection_bucket_base_url');
		$folderPath = $provider->getBucketMainFolder();
		if($folderPath){
			$baseUrl = "{$baseUrl}/{$folderPath}/";
		}else{
			$baseUrl = "{$baseUrl}/";
		}

		wp_enqueue_script( 'izitoast', plugin_dir_url( __FILE__ ) . 'js/iziToast.min.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name.'-media', plugin_dir_url( __FILE__ ) . 'js/media.js', array('jquery',
				'media-editor', // Used in image filters.
				'media-views',
				'media-grid',
				'wp-util',
				'wp-api'), $this->version, true );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/carrot-bunnycdn-incoom-plugin-admin.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'carrot-settings', plugin_dir_url( __FILE__ ) . 'js/settings.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'carrot-cloud-filter', plugin_dir_url( __FILE__ ) . 'js/cloud-filter.js', array( 'jquery' ), $this->version, true );

		if(!empty($page) && $page === 'carrot_bunnycdn_incoom_plugin_one_click'){
			wp_enqueue_script( 'jquery.smartWizard', plugin_dir_url( __FILE__ ) . 'js/jquery.smartWizard.min.js', array( 'jquery' ), $this->version, true );
			wp_enqueue_script( 'carrot-one-click', plugin_dir_url( __FILE__ ) . 'js/one-click.js', array( 'jquery', 'jquery.smartWizard' ), $this->version, true );
		}

		wp_localize_script( 'izitoast', 'carrot_bunnycdn_incoom_plugin_params', array(
			'ajax_url' 				=> esc_url( admin_url( 'admin-ajax.php' ) ),
			'ajax_nonce' 			=> wp_create_nonce( 'carrot_bunnycdn_incoom_plugin_nonce' ),
			'is_plugin_setup' 			=> (incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ? 1 : 0),
			'sync_provider_required'             => esc_html__('Please, select provider.', 'carrot-bunnycdn-incoom-plugin'),
			'scan_title'             => esc_html__('Scanning...', 'carrot-bunnycdn-incoom-plugin'),
			'sync_title'             => esc_html__('May take a few minutes to scan your bucket.', 'carrot-bunnycdn-incoom-plugin'),
			'download_title'             => esc_html__('carrot - Download all files from bucket to server.', 'carrot-bunnycdn-incoom-plugin'),
			'remove_all_files_from_server_title'             => esc_html__('carrot - Remove all files from server.', 'carrot-bunnycdn-incoom-plugin'),
			'remove_all_files_from_bucket_title'             => esc_html__('carrot - Remove all files from bucket.', 'carrot-bunnycdn-incoom-plugin'),
			'copy_all_files_to_bucket_title'             => esc_html__('carrot - bunnycdn incoom plugin library items to bucket.', 'carrot-bunnycdn-incoom-plugin'),
			'confirm_kill_process' => esc_html__('Are you sure you want to kill this process?', 'carrot-bunnycdn-incoom-plugin'),
			'sync_percen'             => carrot_bunnycdn_incoom_plugin_calculator_sync_processed(),
			'sync_processed'             => get_option('incoom_carrot_bunnycdn_incoom_plugin_processed_sync_data'),
			'uploading_title'             => esc_html__('Uploading...', 'carrot-bunnycdn-incoom-plugin'),
			'upload_title'             => esc_html__('Upload', 'carrot-bunnycdn-incoom-plugin'),
			'close_title'             => esc_html__('Close', 'carrot-bunnycdn-incoom-plugin'),
			'create_title'             => esc_html__('Create', 'carrot-bunnycdn-incoom-plugin'),
			'confirm_kill_process_btn' => esc_html__('Kill process', 'carrot-bunnycdn-incoom-plugin'),
			'popup_title'             => esc_html__('carrot - bunnycdn incoom plugin library', 'carrot-bunnycdn-incoom-plugin'),
			'copy_to_s3_text'             => carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_copy_to_s3'),
			'remove_from_s3_text'         => carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_remove_from_s3'),
			'copy_to_server_from_s3_text' => carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_copy_to_server_from_s3'),
			'remove_from_server_text'     => carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_remove_from_server'),
			'build_webp_text'     => carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_build_webp'),
			'current_provider'     => $provider::name(),
			'strings' => array(
				'provider'      => _x( 'Storage Provider', 'Storage provider key name', 'carrot-bunnycdn-incoom-plugin' ),
				'provider_name' => _x( 'Storage Provider', 'Storage provider name', 'carrot-bunnycdn-incoom-plugin' ),
				'bucket'        => _x( 'Bucket', 'Bucket name', 'carrot-bunnycdn-incoom-plugin' ),
				'key'           => _x( 'Path', 'Path to file in bucket', 'carrot-bunnycdn-incoom-plugin' ),
				'region'        => _x( 'Region', 'Location of bucket', 'carrot-bunnycdn-incoom-plugin' ),
				'url'           => esc_html__( 'URL', 'carrot-bunnycdn-incoom-plugin' ),
				),
			'filter_all' 		=> esc_html__( 'All files cloud vs server', 'carrot-bunnycdn-incoom-plugin' ),
			'filter_cloud_served' => array(
				[
					'slug' => '1',
					'name' => esc_html__( 'All files stored in the server', 'carrot-bunnycdn-incoom-plugin' )
				],
				[
					'slug' => '2',
					'name' => esc_html__( 'All files stored in the cloud', 'carrot-bunnycdn-incoom-plugin' )
				]
			),
			'imported_ok' => esc_html__('Successfully imported settings!', 'carrot-bunnycdn-incoom-plugin'), 
			'imported_error' => esc_html__('Failed to import settings!', 'carrot-bunnycdn-incoom-plugin'),
			'scanning_step' => esc_html__('Step 1: Scan attachments on your media library.', 'carrot-bunnycdn-incoom-plugin'),
			'add_cdn_error' => esc_html__('Please, add your CDN domain.', 'carrot-bunnycdn-incoom-plugin'),
			'waiting' => esc_html__('Please, wait a moment...', 'carrot-bunnycdn-incoom-plugin'),
			'base_url' => $baseUrl,
			'cname_url' => carrot_bunnycdn_incoom_plugin_get_cname_url(),
			'scan_links_download_text' => esc_html__('Scan successfully. Please, wait a moment...', 'carrot-bunnycdn-incoom-plugin'),
			'btn_next' => esc_html__('Next', 'carrot-bunnycdn-incoom-plugin'),
			'btn_previous' => esc_html__('Previous', 'carrot-bunnycdn-incoom-plugin'),
		) );
	}


	/**
	 * Add menu item.
	 *
	 * @since    1.0.0
	 */
	public function admin_menu() {

		$active 		= get_option('incoom_carrot_bunnycdn_incoom_plugin_license_active');
		$emailAddress 	= get_option('incoom_carrot_bunnycdn_incoom_plugin_license_email');
		$purchase_key 	= get_option('incoom_carrot_bunnycdn_incoom_plugin_license_key');


		$default = carrot_bunnycdn_incoom_plugin_whichtype_settings();
		$provider = isset($default['provider']) ? $default['provider'] : 'bunnycdn';

	    if ( empty($purchase_key) || empty($emailAddress) || $active != '1' ) {
			add_menu_page( 
				esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ),
				esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ), 
				'edit_theme_options', 
				'carrot_bunnycdn_incoom_plugin_licenser', 
				array($this, 'license_menu_callback'),
				plugin_dir_url( __FILE__ ). 'images/logo.png'
			);
		}else{
			add_menu_page( 
				esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ),
				esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ), 
				'edit_theme_options', 
				'carrot_bunnycdn_incoom_plugin', 
				array($this, 'admin_menu_callback'), 
				plugin_dir_url( __FILE__ ). 'images/logo.png'
			);

			$status = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_success', 0);
		    if($status == 1):
				add_submenu_page( 
					'carrot_bunnycdn_incoom_plugin', 
					esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ), 
					esc_html__( 'BunnyCDN credentials', 'carrot-bunnycdn-incoom-plugin' ),
					'edit_theme_options', 
					'carrot_bunnycdn_incoom_plugin', 
					array($this, 'admin_menu_callback')
				);
				add_submenu_page( 
					'carrot_bunnycdn_incoom_plugin', 
					esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ), 
					esc_html__( 'Bucket Settings', 'carrot-bunnycdn-incoom-plugin' ),
					'edit_theme_options', 
					'carrot_bunnycdn_incoom_plugin&tab=generalsettings', 
					array($this, 'admin_menu_callback')
				);
				add_submenu_page( 
					'carrot_bunnycdn_incoom_plugin', 
					esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ), 
					esc_html__( 'Assets', 'carrot-bunnycdn-incoom-plugin' ),
					'edit_theme_options', 
					'carrot_bunnycdn_incoom_plugin&tab=assets', 
					array($this, 'admin_menu_callback')
				);
				

				if($provider !== 'bunnycdn'):
					add_submenu_page( 
						'carrot_bunnycdn_incoom_plugin', 
						esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ), 
						esc_html__( 'CORS', 'carrot-bunnycdn-incoom-plugin' ),
						'edit_theme_options', 
						'carrot_bunnycdn_incoom_plugin&tab=cors', 
						array($this, 'admin_menu_callback')
					);
				endif;

				if( class_exists('WooCommerce') || class_exists('Easy_Digital_Downloads') ):
				
					add_submenu_page( 
						'carrot_bunnycdn_incoom_plugin', 
						esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ), 
						esc_html__( 'Download', 'carrot-bunnycdn-incoom-plugin' ),
						'edit_theme_options', 
						'carrot_bunnycdn_incoom_plugin&tab=download', 
						array($this, 'admin_menu_callback')
					);

				endif;
				
				add_submenu_page( 
					'carrot_bunnycdn_incoom_plugin', 
					esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ), 
					esc_html__( 'Advanced', 'carrot-bunnycdn-incoom-plugin' ),
					'edit_theme_options', 
					'carrot_bunnycdn_incoom_plugin&tab=advanced', 
					array($this, 'admin_menu_callback')
				);
				
				
				add_submenu_page( 
					'carrot_bunnycdn_incoom_plugin', 
					esc_html__( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' ), 
					esc_html__( 'System status', 'carrot-bunnycdn-incoom-plugin' ),
					'edit_theme_options', 
					'carrot_bunnycdn_incoom_plugin_scheduled_actions', 
					array($this, 'admin_menu_status_callback')
				);
			endif;
			
			
		}	
	}


	/**
	 * Menu item callback.
	 *
	 * @since    1.0.0
	 */
	public function admin_menu_callback(){
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/carrot-bunnycdn-incoom-plugin-admin-display.php';
	}


	/**
	 * Menu item callback.
	 *
	 * @since    1.0.0
	 */
	public function admin_menu_status_callback(){
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/carrot-bunnycdn-incoom-plugin-admin-status.php';
	}


	/**
	 * Menu item callback.
	 *
	 * @since    1.0.0
	 */
	public function license_menu_callback(){
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/carrot-bunnycdn-incoom-plugin-admin-license.php';
	}

	public function one_click_menu_callback(){
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/carrot-bunnycdn-incoom-plugin-admin-oneclick.php';
	}

	public function activate_license(){
		if ( is_admin() ) {
		    $error = '';
		    $message = '';
		    $licenser = new carrot_bunnycdn_incoom_plugin_Licenser(carrot_bunnycdn_incoom_plugin_DIR_FILE, $this->plugin_name, $this->version );
		    $licenser->active_plugin($error, $message);
		}    
	}

	public function deactivate_license(){
		if ( is_admin() ) {
		    $licenser = new carrot_bunnycdn_incoom_plugin_Licenser(carrot_bunnycdn_incoom_plugin_DIR_FILE, $this->plugin_name, $this->version );
		    $licenser->deactivate();
		}    
	}

	public function checkConnect(){

		if(isset($_POST['incoom_carrot_bunnycdn_connection_access_key_text']) || isset($_POST['incoom_carrot_bunnycdn_connection_secret_access_key_text']) || isset($_POST['incoom_carrot_bunnycdn_connection_provider']) || isset($_POST['incoom_carrot_bunnycdn_connection_credentials'])){
          			
			$default = carrot_bunnycdn_incoom_plugin_whichtype_settings();
			$provider = isset($_POST['incoom_carrot_bunnycdn_connection_provider']) ? $_POST['incoom_carrot_bunnycdn_connection_provider'] : 'aws';
			$setup = true;
			switch($provider){
				
				case "bunnycdn":
					if(!empty($_POST['incoom_carrot_bunnycdn_connection_access_key_text']) && !empty($_POST['incoom_carrot_bunnycdn_connection_bunny_storage_key']) && !empty($_POST['incoom_carrot_bunnycdn_connection_bunny_storage_path'])){
						$default['access_key'] = $_POST['incoom_carrot_bunnycdn_connection_access_key_text'];
						update_option('incoom_carrot_bunnycdn_connection_bunny_storage_key', $_POST['incoom_carrot_bunnycdn_connection_bunny_storage_key']);
						update_option('incoom_carrot_bunnycdn_connection_bunny_storage_path', $_POST['incoom_carrot_bunnycdn_connection_bunny_storage_path']);
					}else{
						$setup = false;
						carrot_bunnycdn_incoom_plugin_Messages::add_error(esc_html__('Please, add API key, storage key, storage path to your application.', 'carrot-bunnycdn-incoom-plugin'));
					}
					break;
				
				default:
					break;
			}
				
			if($setup){
				$default['provider'] = $provider;
				update_option('incoom_carrot_bunnycdn_incoom_plugin', $default);
				update_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select', '');
				$this->checking_credentials();
				do_action( 'carrot_bunnycdn_incoom_plugin_changed_provider' );
			}
	  	}
	}
	/**
	 * Hanlder settings save data.
	 *
	 * @since    1.0.0
	 */
	public function hanlder_settings(){

		if( isset( $_POST['incoom_carrot_bunnycdn_reset_nonce'] ) ) {
			if ( wp_verify_nonce( $_POST['incoom_carrot_bunnycdn_reset_nonce'], 'incoom_carrot_bunnycdn_reset_nonce' ) ) {
				carrot_bunnycdn_incoom_plugin_after_action_scheduler_completed();
				carrot_bunnycdn_incoom_plugin_Messages::add_message(esc_html__('Reset scheduled actions completed.', 'carrot-bunnycdn-incoom-plugin'));
			}
		}

		if( isset( $_POST['incoom_carrot_bunnycdn_settings_nonce'] ) ) {
          	if ( wp_verify_nonce( $_POST['incoom_carrot_bunnycdn_settings_nonce'], 'incoom_carrot_bunnycdn_settings_nonce' ) ) {

				$this->checkConnect();

				if( isset($_POST['incoom_carrot_bunnycdn_cronjob_timed_from']) && isset($_POST['incoom_carrot_bunnycdn_cronjob_timed_to']) ){
					if($_POST['incoom_carrot_bunnycdn_cronjob_timed_from'] == 24 && $_POST['incoom_carrot_bunnycdn_cronjob_timed_to'] == 24){
						update_option('incoom_carrot_bunnycdn_cronjob_timed_from', $_POST['incoom_carrot_bunnycdn_cronjob_timed_from']);
						update_option('incoom_carrot_bunnycdn_cronjob_timed_to', $_POST['incoom_carrot_bunnycdn_cronjob_timed_to']);
					}else{
						if($_POST['incoom_carrot_bunnycdn_cronjob_timed_from'] < $_POST['incoom_carrot_bunnycdn_cronjob_timed_to']){
							update_option('incoom_carrot_bunnycdn_cronjob_timed_from', $_POST['incoom_carrot_bunnycdn_cronjob_timed_from']);
							update_option('incoom_carrot_bunnycdn_cronjob_timed_to', $_POST['incoom_carrot_bunnycdn_cronjob_timed_to']);
						}else{
							carrot_bunnycdn_incoom_plugin_Messages::add_error(esc_html__('Time From must be less time To.', 'carrot-bunnycdn-incoom-plugin'));
						}
					}
				}

				if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_send_email_task'])){
					update_option('incoom_carrot_bunnycdn_incoom_plugin_send_email_task', $_POST['incoom_carrot_bunnycdn_incoom_plugin_send_email_task']);
				}else{
					update_option('incoom_carrot_bunnycdn_incoom_plugin_send_email_task', '');
				}

          		if( isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_license_key']) || isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_license_email']) ){
          			update_option('incoom_carrot_bunnycdn_incoom_plugin_license_key', $_POST['incoom_carrot_bunnycdn_incoom_plugin_license_key']);
          			update_option('incoom_carrot_bunnycdn_incoom_plugin_license_email', $_POST['incoom_carrot_bunnycdn_incoom_plugin_license_email']);

          			$this->activate_license();
          		}

          		if( isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_deactivate_license']) && $_POST['incoom_carrot_bunnycdn_incoom_plugin_deactivate_license'] === 'ok' ){
          			$this->deactivate_license();
          		}

          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_general_tab'])){

	          		if(!empty($_POST['incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select'])){

						$oldSettings = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select');
						$newSettings = $_POST['incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select'];

						if($oldSettings != $newSettings){
	          				update_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select', $newSettings);
							$this->bucket_base_url();
							do_action('carrot_bunnycdn_incoom_plugin_changed_provider_bucket');
						}

	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_bucket_folder_main'])){
						$path = str_replace(' ', '-', trim($_POST['incoom_carrot_bunnycdn_incoom_plugin_bucket_folder_main']));
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_bucket_folder_main', $path);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', $_POST['incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_remove_from_server_checkbox'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_remove_from_server_checkbox', $_POST['incoom_carrot_bunnycdn_incoom_plugin_remove_from_server_checkbox']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_remove_from_server_checkbox', '');
					}

					if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_object_versioning'])){
						update_option('incoom_carrot_bunnycdn_incoom_plugin_object_versioning', $_POST['incoom_carrot_bunnycdn_incoom_plugin_object_versioning']);
					}else{
						update_option('incoom_carrot_bunnycdn_incoom_plugin_object_versioning', '');
				  	}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', $_POST['incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button']);
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_cache_control'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cache_control', $_POST['incoom_carrot_bunnycdn_incoom_plugin_cache_control']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cache_control', '');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_accepted_filetypes'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_accepted_filetypes', $_POST['incoom_carrot_bunnycdn_incoom_plugin_accepted_filetypes']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_accepted_filetypes', '');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_gzip'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_gzip', $_POST['incoom_carrot_bunnycdn_incoom_plugin_gzip']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_gzip', '');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_cache_control'])){
	          			if(isset($_POST['incoom_carrot_bunnycdn_update_cache_control']) && $_POST['incoom_carrot_bunnycdn_update_cache_control'] == '1'){
		          			$this->update_cache_control();
		          		}
	          		}
	          		
	          	}
				  
				if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_time_valid_number'])){
					update_option('incoom_carrot_bunnycdn_incoom_plugin_time_valid_number', $_POST['incoom_carrot_bunnycdn_incoom_plugin_time_valid_number']);
				}else{
					update_option('incoom_carrot_bunnycdn_incoom_plugin_time_valid_number', 5);
				}

          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_order_link_checkbox'])){
          			update_option('incoom_carrot_bunnycdn_incoom_plugin_order_link_checkbox', $_POST['incoom_carrot_bunnycdn_incoom_plugin_order_link_checkbox']);
          		}

          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_textarea_email_link'])){
          			update_option('incoom_carrot_bunnycdn_incoom_plugin_textarea_email_link', $_POST['incoom_carrot_bunnycdn_incoom_plugin_textarea_email_link']);
          		}

          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_aws_path'])){
          			update_option('incoom_carrot_bunnycdn_incoom_plugin_aws_path', $_POST['incoom_carrot_bunnycdn_incoom_plugin_aws_path']);
          		}

          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_url_tab'])){
	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox', $_POST['incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox', '');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_cname'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cname', $_POST['incoom_carrot_bunnycdn_incoom_plugin_cname']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cname', '');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_force_https_checkbox'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_force_https_checkbox', $_POST['incoom_carrot_bunnycdn_incoom_plugin_force_https_checkbox']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_force_https_checkbox', '');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_cdn'])){
						update_option('incoom_carrot_bunnycdn_incoom_plugin_cdn', $_POST['incoom_carrot_bunnycdn_incoom_plugin_cdn']);
						
						if($_POST['incoom_carrot_bunnycdn_incoom_plugin_cdn'] != 'default'){
							$cdn = $this->updateCDN();
							if(!$cdn){
								update_option('incoom_carrot_bunnycdn_incoom_plugin_cdn', 'default');
							}
						}

	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cdn', 'default');
					}

					if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_cdn_exclude_filetypes'])){
						update_option('incoom_carrot_bunnycdn_incoom_plugin_cdn_exclude_filetypes', $_POST['incoom_carrot_bunnycdn_incoom_plugin_cdn_exclude_filetypes']);
					}else{
						update_option('incoom_carrot_bunnycdn_incoom_plugin_cdn_exclude_filetypes', '');
					}
					
	          	}

          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_url_tab_assets'])){
	          		
	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox', $_POST['incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox', '');
	          		}

					if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_offload_css'])){
						update_option('incoom_carrot_bunnycdn_incoom_plugin_offload_css', $_POST['incoom_carrot_bunnycdn_incoom_plugin_offload_css']);
					}else{
						update_option('incoom_carrot_bunnycdn_incoom_plugin_offload_css', '');
					}

					if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_offload_js'])){
						update_option('incoom_carrot_bunnycdn_incoom_plugin_offload_js', $_POST['incoom_carrot_bunnycdn_incoom_plugin_offload_js']);
					}else{
						update_option('incoom_carrot_bunnycdn_incoom_plugin_offload_js', '');
					}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_pull_assets_path'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_pull_assets_path', $_POST['incoom_carrot_bunnycdn_incoom_plugin_pull_assets_path']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_pull_assets_path', 'pull-assets/');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_minify_css'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_minify_css', $_POST['incoom_carrot_bunnycdn_incoom_plugin_minify_css']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_minify_css', '');
	          		}
	          		
	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_minify_js'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_minify_js', $_POST['incoom_carrot_bunnycdn_incoom_plugin_minify_js']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_minify_js', '');
	          		}
	          	}

          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_cors_tab'])){
	          		
	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_cors_origin'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cors_origin', $_POST['incoom_carrot_bunnycdn_incoom_plugin_cors_origin']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cors_origin', '*');
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_cors_allow_methods'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cors_allow_methods', $_POST['incoom_carrot_bunnycdn_incoom_plugin_cors_allow_methods']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cors_allow_methods', array('GET', 'HEAD', 'OPTIONS'));
	          		}

	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_cors_maxageseconds'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cors_maxageseconds', $_POST['incoom_carrot_bunnycdn_incoom_plugin_cors_maxageseconds']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_cors_maxageseconds', '3600');
	          		}

	          		// Update CORS
	          		$this->putBucketCors();
	          	}

          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_advanced_tab'])){
	          		
	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_emoji'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_emoji', $_POST['incoom_carrot_bunnycdn_incoom_plugin_emoji']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_emoji', '');
	          		}
	          		
	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_minify_html'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_minify_html', $_POST['incoom_carrot_bunnycdn_incoom_plugin_minify_html']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_minify_html', '');
	          		}
	          		
	          		if(isset($_POST['incoom_carrot_bunnycdn_incoom_plugin_webp'])){
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_webp', $_POST['incoom_carrot_bunnycdn_incoom_plugin_webp']);
	          		}else{
	          			update_option('incoom_carrot_bunnycdn_incoom_plugin_webp', '');
	          		}
	          	}
          		
          	}
     	}
	}

	public function updateCDN(){
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-cdn.php' );
		$cdn = new carrot_bunnycdn_incoom_plugin_CDN();
		$content = $cdn->putHostingContent();
		if($content){
			$content = $cdn->putBucketPolicy();
		}

		return $content;
	}

	public function putBucketCors(){
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-cors.php' );
		$cors = new carrot_bunnycdn_incoom_plugin_Cors();
		$cors->putBucketCors();
	}

	public function checking_credentials(){
		$aws_s3_client = carrot_bunnycdn_incoom_plugin_whichtype();
		$buckets = $aws_s3_client->Checking_Credentials();
		wp_redirect(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin&tab=generalsettings'));
		exit;
	}

	public function update_cache_control(){
		$Bucket_Selected = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select');
		$aws_s3_client = carrot_bunnycdn_incoom_plugin_whichtype();
		$region = 'none';
		
		if($aws_s3_client::identifier() == 'google'){
			$bucket = $Bucket_Selected;
		}else{

			$Array_Bucket_Selected = explode( "_incoom_wc_as3s_separator_", $Bucket_Selected );

	        if ( count( $Array_Bucket_Selected ) == 2 ){
	            $bucket = $Array_Bucket_Selected[0];
	            $region = $Array_Bucket_Selected[1];
	        }
	        else{
	            $bucket = 'none';
	        }

	    }
	    
		//$aws_s3_client->update_cache_control_objects($bucket, $region);
	}

	public function bucket_base_url(){
		carrot_bunnycdn_incoom_plugin_bucket_base_url();
	}

	public function get_all_image_sizes(){
		global $_wp_additional_image_sizes;

		$sizes = [];

		foreach ( get_intermediate_image_sizes() as $s ) {
			$sizes[$s] = array(
				'name'   => '',
				'width'  => '',
				'height' => '',
				'crop'   => false
			);

			/* Read theme added sizes or fall back to default sizes set in options... */

			$sizes[$s]['name'] = $s;

			if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) {
				$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] );
			}
			else {
				$sizes[$s]['width'] = get_option( "{$s}_size_w" );
			}

			if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) {
				$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] );
			}
			else {
				$sizes[$s]['height'] = get_option( "{$s}_size_h" );
			}

			if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) ) {
				if( ! is_array( $sizes[$s]['crop'] ) ) {
					$sizes[$s]['crop'] = intval( $_wp_additional_image_sizes[$s]['crop'] );
				}
				else {
					$sizes[$s]['crop'] = $_wp_additional_image_sizes[$s]['crop'];
				}
			}
			else {
				$sizes[$s]['crop'] = get_option( "{$s}_crop" );
			}
		}

		$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );

		return $sizes;
	}

	/**
	 * Are we waiting for the wp_generate_attachment_metadata filter to fire?
	 *
	 * @handles carrot_wait_for_generate_attachment_metadata
	 *
	 * @param bool $wait
	 *
	 * @return bool
	 */
	public function wait_for_generate_attachment_metadata( $wait ) {
		if ( $this->wait_for_generate_attachment_metadata ) {
			return true;
		}

		return $wait;
	}

	/**
	 * Update internal state for waiting for attachment_metadata.
	 *
	 * @handles wp_generate_attachment_metadata
	 *
	 * @param array $metadata
	 *
	 * @return array
	 */
	public function generate_attachment_metadata_done( $metadata, $attachment_id, $context ) {
		$this->wait_for_generate_attachment_metadata = false;
		return $metadata;
	}

	/**
	 * Copying files to S3 and removing from server
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function wp_update_attachment_metadata( $data, $post_id ) {

		if(!incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup()){
			return $data;
		}

		$copy_file_s3_checkbox = get_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', '');
		if ( $copy_file_s3_checkbox !== 'on' ) {
			return $data;
		}

		// Some other filter may already have corrupted $data
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		try {
			if ( incoom_carrot_bunnycdn_incoom_plugin_is_bb_activate() ) {
				if(strpos($data['file'], 'suspended-mystery-man') !== false){
					return $data;
				}
			}
		} catch (\Throwable $th) {}

		if ( ! empty( $data ) && function_exists( 'wp_get_registered_image_subsizes' ) && function_exists( 'wp_get_missing_image_subsizes' ) ) {

			/**
			 * Plugin compat may require that we wait for wp_generate_attachment_metadata
			 * to be run before proceeding with uploading. I.e. Regenerate Thumbnails requires this.
			 *
			 * @param bool True if we should wait AND generate_attachment_metadata hasn't run yet
			 */
			if ( apply_filters( 'carrot_wait_for_generate_attachment_metadata', false ) ) {
				return $data;
			}

			if ( empty( $data['sizes'] ) && wp_attachment_is_image( $post_id ) ) {

				// There is no unified way of checking whether subsizes are expected, so we have to duplicate WordPress code here.
				$new_sizes     = wp_get_registered_image_subsizes();
				$new_sizes     = apply_filters( 'intermediate_image_sizes_advanced', $new_sizes, $data, $post_id );
				$missing_sizes = wp_get_missing_image_subsizes( $post_id );

				if ( ! empty( $new_sizes ) && ! empty( $missing_sizes ) && array_intersect_key( $missing_sizes, $new_sizes ) ) {
					return $data;
				}
			}
		}
			
		$class = carrot_bunnycdn_incoom_plugin_get_source_type_name();
		// Is this a new item that we're already started working on in this request?
		if ( ! empty( $this->items_in_progress[ $post_id ] ) ) {
			$carrot_item = $this->items_in_progress[ $post_id ];
		}

		// Is this an update for an existing item.
		if ( empty( $carrot_item ) || is_wp_error( $carrot_item ) ) {
			$carrot_item = $class::get_by_source_id( $post_id );
		}

		if ( empty( $carrot_item ) ) {
			$carrot_item = null;
		}

		$offloaded_files = [];

		// If we still don't have a valid item, create one from scratch.
		if ( empty( $carrot_item ) || is_wp_error( $carrot_item ) ) {
			$carrot_item = $class::create_from_source_id( $post_id );
			$carrot_item->save();
		} else {
			$offloaded_files = $carrot_item->offloaded_files();
		}

		// Did we get a WP_Error?
		if ( is_wp_error( $carrot_item ) ) {
			return $data;
		}

		// Update item's expected objects from attachment's new metadata.
		$this->update_item_from_new_metadata( $carrot_item, $data );
		$this->upload_item( $carrot_item, $offloaded_files );

		$this->items_in_progress[ $post_id ] = $carrot_item;

		return $data;
	}

	/**
	 * Upload item.
	 *
	 * @param carrot_bunnycdn_incoom_plugin_Item $carrot_item
	 * @param array              $offloaded_files An array of files previously offloaded for the item.
	 */
	protected function upload_item( carrot_bunnycdn_incoom_plugin_Item $carrot_item, array $offloaded_files ) {
		$upload_handler = carrot_bunnycdn_incoom_plugin_get_item_handler( carrot_bunnycdn_incoom_plugin_Upload_Handler::get_item_handler_key_name() );
		$upload_handler->handle( $carrot_item, array( 'offloaded_files' => $offloaded_files ) );
	}

	/**
	 * Update an existing item's expected objects from attachment's new metadata.
	 *
	 * @param carrot_bunnycdn_incoom_plugin_Item $carrot_item
	 * @param array              $metadata
	 */
	protected function update_item_from_new_metadata( $carrot_item, $metadata ) {
		if ( empty( $metadata ) || ! is_array( $metadata ) ) {
			return;
		}

		$files             = carrot_bunnycdn_incoom_plugin_Utils::get_attachment_file_paths( $carrot_item->source_id(), false, $metadata );
		$existing_basename = wp_basename( $carrot_item->path() );
		$existing_objects  = $carrot_item->objects();

		if ( ! isset( $this->replaced_object_keys[ $carrot_item->source_id() ] ) ) {
			$this->replaced_object_keys[ $carrot_item->source_id() ] = array();
		}

		foreach ( $files as $object_key => $file ) {
			$new_filename = wp_basename( $file );

			if ( ! empty( $existing_objects[ $object_key ]['source_file'] ) && $existing_objects[ $object_key ]['source_file'] !== $new_filename ) {
				$this->replaced_object_keys[ $carrot_item->source_id() ][ $object_key ] = $existing_objects[ $object_key ];
			}

			if ( carrot_bunnycdn_incoom_plugin_Item::primary_object_key() === $object_key && $existing_basename !== $new_filename ) {
				$carrot_item->set_path( str_replace( $existing_basename, $new_filename, $carrot_item->path() ) );
				$carrot_item->set_source_path( str_replace( $existing_basename, $new_filename, $carrot_item->source_path() ) );
			}

			$existing_objects[ $object_key ] = array(
				'source_file' => $new_filename,
				'is_private'  => isset( $existing_objects[ $object_key ]['is_private'] ) ? $existing_objects[ $object_key ]['is_private'] : false,
			);
		}

		$extra_info            = $carrot_item->extra_info();
		$extra_info['objects'] = $existing_objects;
		$carrot_item->set_extra_info( $extra_info );
	}

	/**
	 * Removes an attachment and intermediate image size files from provider
	 *
	 * @param int  $post_id
	 * @param bool $force_new_provider_client if we are deleting in bulk, force new provider client
	 *                                        to cope with possible different regions
	 */
	public function delete_attachment( $post_id, $force_new_provider_client = false ) {
		if ( ! incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup( true ) ) {
			return;
		}

		if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_get_attachment_provider_info( $post_id ) ) ) {
			return;
		}

		carrot_bunnycdn_incoom_plugin_remove_from_s3_function($post_id);

		try {
			$class = carrot_bunnycdn_incoom_plugin_get_source_type_name();
			$carrot_item = $class::get_by_source_id( $post_id );
			if ( !empty( $carrot_item->id() ) ) {
				$carrot_item->delete();
			}
		} catch (\Throwable $th) {
			//throw $th;
		}

		delete_post_meta( $post_id, '_incoom_carrot_bunnycdn_amazonS3_info' );
		delete_post_meta( $post_id, '_incoom_carrot_bunnycdn_webp_info' );
	}

	/**
	 * Show individual options
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function media_row_actions_extra( $actions, $post ) { // 3º parameter $this->detached

		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {

			$post_id = get_the_ID( $post );

			return carrot_bunnycdn_incoom_plugin_row_actions_extra($actions, $post_id);

		}

		return $actions;

	}

	/**
	 * Show bulk actions
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function bulk_actions_extra_options( $actions ) {

		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {

			$actions['incoom_carrot_bunnycdn_copy_to_s3']             = carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_copy_to_s3');
			$actions['incoom_carrot_bunnycdn_remove_from_s3']         = carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_remove_from_s3');
			$actions['incoom_carrot_bunnycdn_copy_to_server_from_s3'] = carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_copy_to_server_from_s3');
			$actions['incoom_carrot_bunnycdn_remove_from_server']     = carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_remove_from_server');
			$actions['incoom_carrot_bunnycdn_build_webp']     		   = carrot_bunnycdn_incoom_plugin_text_actions('incoom_carrot_bunnycdn_build_webp');

		}

		return $actions;

	}

	/**
	 * Do bulk actions
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function do_bulk_actions_extra_options( $location, $doaction, $post_ids ) {
		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {
			incoom_carrot_bunnycdn_incoom_plugin_do_bulk_actions_extra_options_function( $doaction, $post_ids );
		}

		return $location;

	}


	/**
	 * Copy to S3
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function post_action_copy_to_c3( $post_id ) {

		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {
			$radio_private_or_public = get_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', 'public');
			carrot_bunnycdn_incoom_plugin_copy_to_s3_function( $post_id, $radio_private_or_public );
		}

		$sendback = wp_get_referer();

		wp_redirect( $sendback );

		die();

	}


	/**
	 * Remove from S3
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function post_action_remove_from_s3( $post_id ) {

		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {
			carrot_bunnycdn_incoom_plugin_copy_to_server_from_s3_function( $post_id );
			carrot_bunnycdn_incoom_plugin_remove_from_s3_function( $post_id );
		}

		$sendback = wp_get_referer();

		wp_redirect( $sendback );

		die();

	}


	/**
	 * Copy to server from S3
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function post_action_copy_to_server_from_c3( $post_id ) {

		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {

			carrot_bunnycdn_incoom_plugin_copy_to_server_from_s3_function( $post_id );

		}

		$sendback = wp_get_referer();

		wp_redirect( $sendback );

		die();

	}

	/**
	 * Remove from server
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function post_action_remove_from_server( $post_id ) {

		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {

			carrot_bunnycdn_incoom_plugin_remove_from_server_function( $post_id );

		}

		$sendback = wp_get_referer();

		wp_redirect( $sendback );

		die();

	}

	/**
	 * Build WebP
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function post_action_build_webp( $post_id ) {

		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {

			carrot_bunnycdn_incoom_plugin_build_webp_function( $post_id );

		}

		$sendback = wp_get_referer();

		wp_redirect( $sendback );

		die();

	}

	/**
	 * Get attachment url
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function wp_get_attachment_url( $url, $post_id ) {
		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {
			if(incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
				$s3_path = get_post_meta( $post_id, '_wp_incoom_carrot_bunnycdn_s3_path', true);
				$new_url = false;
				
				if($s3_path == '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used'){
					try {
						$class = carrot_bunnycdn_incoom_plugin_get_source_type_name();
						$carrot_item = $class::get_by_source_id( $post_id );
						if ( !empty( $carrot_item->id() ) ) {
							$carrot_item->delete();
						}
					} catch (\Throwable $th) {}
				}

				try {
					if ( $s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' && $s3_path != null ) {
						$provider_object = carrot_bunnycdn_incoom_plugin_get_real_provider($post_id);
						$new_url = $provider_object['base_url'];
					}
				} catch (\Throwable $th) {}

				if ( is_wp_error( $new_url ) || false === $new_url ) {
					return $url;
				}

				$enable_webp = carrot_bunnycdn_incoom_plugin_can_use_webp();
				if($enable_webp){
					$key = carrot_bunnycdn_incoom_plugin_Utils::get_key_from_url($new_url, false);
					$webp_url = carrot_bunnycdn_incoom_plugin_Utils::get_webp_url_with_key($post_id, $key);
					if(!empty($webp_url)){
						$provider_url = carrot_bunnycdn_incoom_plugin_get_real_url($webp_url);
						$new_url = $provider_url;
					}
				}
				
				$new_url = carrot_bunnycdn_incoom_plugin_s3_to_cloudfront_url($new_url);

				return apply_filters( 'carrot_bunnycdn_incoom_plugin_get_attachment_url', $new_url, $post_id );
			}
		}
		return $url;
	}


	/**
	 * Maybe encode URLs for images that represent an attachment
	 *
	 * @param array|bool   $image
	 * @param int          $attachment_id
	 * @param string|array $size
	 * @param bool         $icon
	 *
	 * @return array
	 */
	public function maybe_encode_wp_get_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_is_attachment_served_by_provider( $attachment_id ) ) ) {
			// Not served by provider, return
			return $image;
		}

		if ( isset( $image[0] ) ) {
			$url = carrot_bunnycdn_incoom_plugin_maybe_sign_intermediate_size( $image[0], $attachment_id, $size, $provider_object );
			$url = incoom_carrot_bunnycdn_incoom_plugin_encode_filename_in_path( $url );

			$image[0] = $url;
		}

		return $image;
	}
	

	/**
	 * Maybe encode attachment URLs when retrieving the image tag
	 *
	 * @param string $html
	 * @param int    $id
	 * @param string $alt
	 * @param string $title
	 * @param string $align
	 * @param string $size
	 *
	 * @return string
	 */
	public function maybe_encode_get_image_tag( $html, $id, $alt, $title, $align, $size ) {
		if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_is_attachment_served_by_provider( $id ) ) ) {
			// Not served by provider, return
			return $html;
		}

		if ( ! is_string( $html ) ) {
			return $html;
		}

		preg_match( '@\ssrc=[\'\"]([^\'\"]*)[\'\"]@', $html, $matches );

		if ( ! isset( $matches[1] ) ) {
			// Can't establish img src
			return $html;
		}

		$img_src     = $matches[1];
		$new_img_src = carrot_bunnycdn_incoom_plugin_maybe_sign_intermediate_size( $img_src, $id, $size, $provider_object );
		$new_img_src = incoom_carrot_bunnycdn_incoom_plugin_encode_filename_in_path( $new_img_src );

		return str_replace( $img_src, $new_img_src, $html );
	}

	/**
	 * Maybe encode URLs when outputting attachments in the media grid
	 *
	 * @param array      $response
	 * @param int|object $attachment
	 * @param array      $meta
	 *
	 * @return array
	 */
	public function maybe_encode_wp_prepare_attachment_for_js( $response, $attachment, $meta ) {
		if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_is_attachment_served_by_provider( $attachment->ID ) ) ) {
			// Not served by provider, return
			return $response;
		}

		if ( isset( $response['url'] ) ) {
			$response['url'] = incoom_carrot_bunnycdn_incoom_plugin_encode_filename_in_path( $response['url'] );
		}

		$sizes = '';

		if ( isset( $response['sizes'] ) && is_array( $response['sizes'] ) ) {
			$sizes = $response['sizes'];
		}else{
			$meta = get_post_meta($attachment->ID, '_incoom_carrot_bunnycdn_amazonS3_info', true);
			if ( isset( $meta['data']['sizes'] ) && is_array( $meta['data']['sizes'] ) ) {
				$sizes = $meta['data']['sizes'];
			}
		}
		
		if ($sizes) {
			foreach ( $sizes as $size => $value ) {
				if(isset($value['url'])){
					$url = carrot_bunnycdn_incoom_plugin_maybe_sign_intermediate_size( $value['url'], $attachment->ID, $size, $provider_object );
					$url = incoom_carrot_bunnycdn_incoom_plugin_encode_filename_in_path( $url );

					$response['sizes'][ $size ]['url'] = $url;
				}
			}
		}
		$response['carrot_served'] = 'all';
		$s3_path = get_post_meta( $attachment->ID, '_wp_incoom_carrot_bunnycdn_s3_path', true );
		if ( $s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' && $s3_path != null ) {
			$response['carrot-cloud-class'] = "carrot-served-by-provider";
			$response['carrot_served'] = 1;
		}else{
			$response['carrot-cloud-class'] = "carrot-not-served";
		}

		return $response;
	}

	/**
	 * Maybe encode URLs when retrieving intermediate sizes.
	 *
	 * @param array        $data
	 * @param int          $post_id
	 * @param string|array $size
	 *
	 * @return array
	 */
	public function maybe_encode_image_get_intermediate_size( $data, $post_id, $size ) {
		if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_is_attachment_served_by_provider( $post_id ) ) ) {
			// Not served by provider, return
			return $data;
		}

		if ( isset( $data['url'] ) ) {
			$url = carrot_bunnycdn_incoom_plugin_maybe_sign_intermediate_size( $data['url'], $post_id, $size, $provider_object );
			$url = incoom_carrot_bunnycdn_incoom_plugin_encode_filename_in_path( $url );

			$data['url'] = $url;
		}

		return $data;
	}

	/**
	 * Return the provider URL when the local file is missing
	 * unless we know the calling process is and we are happy
	 * to copy the file back to the server to be used
	 *
	 * @param string $file
	 * @param int    $attachment_id
	 *
	 * @return string
	 */
	function get_attached_file( $file, $attachment_id ) {
		if($attachment_id > 0){
			$post_type = get_post_type( $attachment_id );
			if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() && $post_type == 'attachment' ) {
				if ( file_exists( $file ) || ! ( $provider_object = carrot_bunnycdn_incoom_plugin_is_attachment_served_by_provider( $attachment_id ) ) ) {
					return $file;
				}

				$s3_path = get_post_meta( $attachment_id, '_wp_incoom_carrot_bunnycdn_s3_path', true);
				if ( $s3_path != '_wp_incoom_carrot_bunnycdn_s3_path_not_in_used' && $s3_path != null ) {
					$provider_object = carrot_bunnycdn_incoom_plugin_get_real_provider($attachment_id);
					$url = $provider_object['base_url'];
					return apply_filters( 'carrot_bunnycdn_incoom_plugin_get_attached_file', $url, $file, $attachment_id, $provider_object );
				}
			}
		}
		return $file;
	}

	/**
	 * Allow processes to update the file on provider via update_attached_file()
	 *
	 * @param string $file
	 * @param int    $attachment_id
	 *
	 * @return string
	 */
	function update_attached_file( $file, $attachment_id ) {
		if ( !incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {
			return $file;
		}

		if ( ! ( $provider_object = carrot_bunnycdn_incoom_plugin_get_attachment_provider_info( $attachment_id ) ) ) {
			return $file;
		}

		$file = apply_filters( 'carrot_bunnycdn_incoom_plugin_update_attached_file', $file, $attachment_id, $provider_object );

		return $file;
	}

	public function attachment_provider_meta_box(){
		if(incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup()){
			add_meta_box(
				'nou-s3-actions',
				esc_html__( 'carrot - Offload media', 'carrot-bunnycdn-incoom-plugin' ),
				array( $this, 'attachment_provider_actions_meta_box' ),
				'attachment',
				'side',
				'core'
			);
		}	
	}

	/**
	 * Render the S3 attachment meta box
	 */
	public function attachment_provider_actions_meta_box() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/carrot-bunnycdn-incoom-plugin-admin-metabox.php';
	}

	public function need_verify_offloaded_media(){
		global $wpdb;

		$table_name = $wpdb->get_blog_prefix() . 'carrot_items';

		try {
			$wpdb->update( $table_name, [ 'is_verified' => false ], [ 'is_verified' => true ] );
		} catch (\Throwable $th) {
			error_log($th->getMessage());
		}
	}

}
