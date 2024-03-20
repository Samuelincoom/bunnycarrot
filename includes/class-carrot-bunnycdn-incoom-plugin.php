<?php
if (!defined('ABSPATH')) {exit;}
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */
class carrot_bunnycdn_incoom_plugin {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      carrot_bunnycdn_incoom_plugin_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'carrot_bunnycdn_incoom_plugin_VERSION' ) ) {
			$this->version = carrot_bunnycdn_incoom_plugin_VERSION;
		} else {
			$this->version = '2.0.2';
		}
		$this->plugin_name = carrot_bunnycdn_incoom_plugin_PLUGIN_NAME;

		$this->load_dependencies();
		$this->set_locale();
		$this->include_vendor();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		add_action( 'plugins_loaded', array( $this, 'init_cron_task' ) );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - carrot_bunnycdn_incoom_plugin_Loader. Orchestrates the hooks of the plugin.
	 * - carrot_bunnycdn_incoom_plugin_i18n. Defines internationalization functionality.
	 * - carrot_bunnycdn_incoom_plugin_Admin. Defines all hooks for the admin area.
	 * - carrot_bunnycdn_incoom_plugin_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-carrot-bunnycdn-incoom-plugin-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-carrot-bunnycdn-incoom-plugin-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-carrot-bunnycdn-incoom-plugin-admin.php';

		/**
		 * Public
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-carrot-bunnycdn-incoom-plugin-public.php';

		$this->loader = new carrot_bunnycdn_incoom_plugin_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the carrot_bunnycdn_incoom_plugin_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new carrot_bunnycdn_incoom_plugin_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin 		= new carrot_bunnycdn_incoom_plugin_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_licenser 	= new carrot_bunnycdn_incoom_plugin_Licenser(carrot_bunnycdn_incoom_plugin_DIR_FILE, $this->plugin_name, $this->version );

		$this->loader->add_action( 'admin_notices', $plugin_admin, 'sync_notice' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'setup_notice' );
		$this->loader->add_action( 'admin_notices', $plugin_licenser, 'show_admin_notices' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 10000 );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 10000 );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu' );
		$this->loader->add_action( 'init', $plugin_admin, 'hanlder_settings' );
		$this->loader->add_action( 'init', $plugin_admin, 'hanlder_kill_sync_process' );

		add_action( 'wp_ajax_incoom_carrot_bunnycdn_incoom_plugin_import', array($this, 'import_settings') );

		if(incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup()){

			// Let the media library integration know it should wait for all attachment metadata.
			$this->loader->add_filter( 'carrot_wait_for_generate_attachment_metadata', $plugin_admin, 'wait_for_generate_attachment_metadata' );
			
			$this->loader->add_filter( 'wp_update_attachment_metadata', $plugin_admin, 'wp_update_attachment_metadata', 110, 2 );

			// Wait for WordPress core to tell us it has finished generating thumbnails.
			$this->loader->add_filter( 'wp_generate_attachment_metadata', $plugin_admin, 'generate_attachment_metadata_done', 10, 3 );

			$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'attachment_provider_meta_box', 10, 4 );
			$this->loader->add_filter( 'delete_attachment', $plugin_admin, 'delete_attachment', 20 );
			$this->loader->add_filter( 'media_row_actions', $plugin_admin, 'media_row_actions_extra', 10, 2 );
			$this->loader->add_filter( "bulk_actions-upload", $plugin_admin, 'bulk_actions_extra_options' );
			$this->loader->add_filter( 'handle_bulk_actions-upload', $plugin_admin, 'do_bulk_actions_extra_options', 10, 3 );

			$this->loader->add_action( 'post_action_incoom_carrot_bunnycdn_copy_to_s3', $plugin_admin, 'post_action_copy_to_c3', 10, 1 );
			$this->loader->add_action( 'post_action_incoom_carrot_bunnycdn_remove_from_s3', $plugin_admin, 'post_action_remove_from_s3', 10, 1 );
			$this->loader->add_action( 'post_action_incoom_carrot_bunnycdn_copy_to_server_from_s3', $plugin_admin, 'post_action_copy_to_server_from_c3', 10, 1 );
			$this->loader->add_action( 'post_action_incoom_carrot_bunnycdn_remove_from_server', $plugin_admin, 'post_action_remove_from_server', 10, 1 );
			$this->loader->add_action( 'post_action_incoom_carrot_bunnycdn_build_webp', $plugin_admin, 'post_action_build_webp', 10, 1 );
			$this->loader->add_action( 'restrict_manage_posts', $plugin_admin, 'cloud_served_filtering',10, 1 );
			$this->loader->add_action( 'pre_get_posts', $plugin_admin, 'cloud_served_filter_request_query' , 10);
			
			$this->loader->add_action( 'carrot_bunnycdn_incoom_plugin_changed_provider', $plugin_admin, 'need_verify_offloaded_media');
			$this->loader->add_action( 'carrot_bunnycdn_incoom_plugin_changed_provider_bucket', $plugin_admin, 'need_verify_offloaded_media');


			add_action( 'carrot_post_handle_item_upload', array( $this, 'delete_files_after_item_uploaded' ), 10, 3 );

			$this->loader->add_filter( 'wp_get_attachment_url', $plugin_admin, 'wp_get_attachment_url', 99, 2 );
			$this->loader->add_filter( 'wp_get_attachment_image_src', $plugin_admin, 'maybe_encode_wp_get_attachment_image_src', 99, 4 );
			$this->loader->add_filter( 'get_image_tag', $plugin_admin, 'maybe_encode_get_image_tag', 99, 6 );
			$this->loader->add_filter( 'wp_prepare_attachment_for_js', $plugin_admin, 'maybe_encode_wp_prepare_attachment_for_js', 99, 3 );
			$this->loader->add_filter( 'image_get_intermediate_size', $plugin_admin, 'maybe_encode_image_get_intermediate_size', 99, 3 );
			$this->loader->add_filter( 'get_attached_file', $plugin_admin, 'get_attached_file', 10, 2 );
			$this->loader->add_filter( 'posts_where', $plugin_admin, 'ajax_query_attachments_args', 10 );

			if(class_exists('WP_CLI')){
				add_action( 'cli_init', array($this, 'cli_register_commands') );
			}

		}

	}

	public function cli_register_commands(){
		WP_CLI::add_command( 'carrot-cli', 'carrot_bunnycdn_incoom_plugin_CLI' );
	}

	/**
	 * Handle item after saved
	 *
	 * @handles carrot_after_item_save
	 *
	 * @param carrot_bunnycdn_incoom_plugin_Item $carrot_item
	 */
	public function delete_files_after_item_uploaded( $result, $carrot_item, $options ){
		$remove_local_files_setting = get_option('incoom_carrot_bunnycdn_incoom_plugin_remove_from_server_checkbox');
		if ( $remove_local_files_setting && $result ) {
			try {
				carrot_bunnycdn_incoom_plugin_remove_from_server_function($carrot_item->source_id());
			} catch (\Throwable $th) {}
		}
	}

	public function import_settings(){
		$nonce = $_REQUEST['_wpnonce'];
		if ( wp_verify_nonce( $nonce, 'carrot_bunnycdn_incoom_plugin_nonce' ) ) {
			try {
				$content  = ( isset( $_POST['content'] ) ? $_POST['content'] : '' );
				if($content){
					foreach($content as $key => $value){
						update_option($key, $value);
					}
					wp_send_json_success( 
						array(
							'status' => 'success',
							'data' => []
						) 
					);
				}
			} catch (\Throwable $th) {
				error_log($th->getMessage());
			}
		}
		wp_send_json_error(array('status' => 'fail', 'message' => esc_html__('Security check', 'carrot-bunnycdn-incoom-plugin')));
	}

	public function define_public_hooks(){
		$plugin_public = new carrot_bunnycdn_incoom_plugin_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles', 100000 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts', 100000 );
	}

	public function init_cron_task(){
		new carrot_bunnycdn_incoom_plugin_Sync_Process();
		new carrot_bunnycdn_incoom_plugin_Scan_Attachments_Process();
		new carrot_bunnycdn_incoom_plugin_Copy_To_Cloud_Process();
		new carrot_bunnycdn_incoom_plugin_Remove_Files_Server_Process();
		new carrot_bunnycdn_incoom_plugin_Remove_Files_Bucket_Process();
		new carrot_bunnycdn_incoom_plugin_Download_Files_Bucket_Process();
		new carrot_bunnycdn_incoom_plugin_Verify_Offloaded_Files_Process();
	}

	/**
	 * Register all vendor
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function include_vendor() {
		
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		include_once( ABSPATH . 'wp-includes/theme.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		require_once( ABSPATH . 'wp-admin/includes/screen.php' );

		if (!function_exists('wp_hash')) {
			include_once( ABSPATH . 'wp-includes/pluggable.php' );
		}

		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-messages.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'libraries/bunnycdn/bunnycdn.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/interfaces/class-carrot-bunnycdn-incoom-plugin-queue-interface.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-rename-file.php' );
		
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-storage.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-google.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-aws-client.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-bunny-client.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-wasabi-client.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-do-client.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-cloudflare-r2-client.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-utils.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-filter.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-filter-s3-to-local.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-filter-local-to-s3.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-assets.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-sync.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-background-process.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-sync-process.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-ajax.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-compatibility.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-download.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-webp.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-lazy-query-loop.php' );

		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-scan-attachments-process.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-copy-to-cloud-process.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-remove-files-server-process.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-remove-files-bucket-process.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-download-files-bucket-process.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-verify-offloaded-files-process.php' );
	
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-item.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-buddyboss.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-elementor.php' );
		require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-cli.php' );
		
		$ajax = new carrot_bunnycdn_incoom_plugin_Ajax();

		carrot_bunnycdn_incoom_plugin_Messages::init();
		
		if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {
			
			carrot_bunnycdn_incoom_plugin_Item::init_cache();
			carrot_bunnycdn_incoom_plugin_Buddyboss::init();

			if(incoom_carrot_bunnycdn_incoom_plugin_enable_rewrite_urls()){
				$compatibility = new carrot_bunnycdn_incoom_plugin_Compatibility();
				$GLOBALS['carrot_filter_cloud_to_local'] = new carrot_bunnycdn_incoom_plugin_Filter_S3_To_Local();
				$GLOBALS['carrot_filter_local_to_cloud'] = new carrot_bunnycdn_incoom_plugin_Filter_Local_To_S3();
				carrot_bunnycdn_incoom_plugin_Elementor::init();
			}

			if(class_exists('Easy_Digital_Downloads')){
				require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-edd.php' );
				$edd = new carrot_bunnycdn_incoom_plugin_Edd();
			}

			if (is_plugin_active('woocommerce/woocommerce.php')) {
				require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-woocommerce.php' );
				$woo = new carrot_bunnycdn_incoom_plugin_Woocommerce();
			}

			if (is_plugin_active('woocommerce/woocommerce.php') || class_exists('Easy_Digital_Downloads')) {
				require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-shortcodes.php' );
				$shortcodes = new carrot_bunnycdn_incoom_plugin_Shortcodes();
			}

			$enable_assets = get_option('incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox', '');
			if ($enable_assets) {
				$assets = new carrot_bunnycdn_incoom_plugin_Assets();
			}
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    carrot_bunnycdn_incoom_plugin_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}