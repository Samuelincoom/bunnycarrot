<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */
class carrot_bunnycdn_incoom_plugin_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::clear_cache();
		self::create_options();
		self::create_files();
		self::create_tables();
		self::unschedule_actions();
		self::create_table_logs();
		self::security_key();
		self::create_table_items();
		self::activeByDomain();
		self::create_background_tasks();
	}

	public static function create_background_tasks() {
		if ( function_exists( 'as_schedule_recurring_action' ) ) {
			as_schedule_recurring_action( strtotime( 'now' ), 30, 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_verify_offloaded_files' );
		}
	}

	public static function activeByDomain() {
		try {
			if ( is_admin() ) {
				$licenser = new carrot_bunnycdn_incoom_plugin_Licenser(carrot_bunnycdn_incoom_plugin_DIR_FILE, carrot_bunnycdn_incoom_plugin_PLUGIN_NAME, carrot_bunnycdn_incoom_plugin_VERSION );
				$licenser->active_plugin_by_domain();
			}
		} catch (\Throwable $th) {}
	}

	public static function clear_cache() 
	{
		try {
			wp_cache_flush();
		} catch (\Throwable $th) {}
	}

	public static function security_key()
	{
		update_option('incoom_carrot_bunnycdn_incoom_plugin_security_key', wp_generate_password(30, true));
	}

	public static function unschedule_actions()
	{
		carrot_bunnycdn_incoom_plugin_after_action_scheduler_completed();
	}

	public static function create_tables()
	{
		global $wpdb;

		$table_list = array(
			'actionscheduler_actions',
			'actionscheduler_logs',
			'actionscheduler_groups',
			'actionscheduler_claims',
		);

		$found_tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}actionscheduler%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $table_list as $table_name ) {
			if ( ! in_array( $wpdb->prefix . $table_name, $found_tables ) ) {
				
				$store_schema  = new ActionScheduler_StoreSchema();
				$logger_schema = new ActionScheduler_LoggerSchema();
				$store_schema->register_tables( true );
				$logger_schema->register_tables( true );

				return;
			}
		}
	}

	/**
	 * Create logs table.
	 */
	public static function create_table_logs() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$wpdb->hide_errors();

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		// Max DB index length. See wp_get_db_schema().
		$max_index_length = 191;

		try {
			$tables = "
			CREATE TABLE {$wpdb->prefix}carrot_offload_stats (
				post_id bigint(20) unsigned NOT NULL,
				date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				num_items int(11) DEFAULT 0 NOT NULL,
				action varchar(200) NOT NULL,
				KEY date_created (date_created),
				KEY action (action({$max_index_length}))
			) $collate;";

			dbDelta( $tables );
		} catch (\Throwable $th) {}

		try {

			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}carrot_offload_stats';" ) ) {
				if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}carrot_offload_stats` LIKE 'id';" ) ) {
					$wpdb->query( "ALTER TABLE {$wpdb->prefix}carrot_offload_stats DROP PRIMARY KEY, ADD `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT;" );
				}
			}
		} catch (\Throwable $th) {}
	}

	/**
	 * Create options
	 *
	 * @since    1.0.0
	 */
	public static function create_options() {
		$default = carrot_bunnycdn_incoom_plugin_whichtype_settings();
		if(empty($default)){
			$options = array(
				'provider' => 'aws',
				'access_key' => '',
				'secret_access_key' => '',
				'credentials' => ''
				);
			update_option('incoom_carrot_bunnycdn_incoom_plugin', $options);
			update_option('incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox', 'on');
			update_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox', 'on');
			update_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', 'public');
		}

		$accepted_filetypes = get_option('incoom_carrot_bunnycdn_incoom_plugin_accepted_filetypes');
		if(empty($accepted_filetypes)){
			update_option('incoom_carrot_bunnycdn_incoom_plugin_accepted_filetypes', '');
		}

		$cdn = get_option('incoom_carrot_bunnycdn_incoom_plugin_cdn');
		if(empty($cdn)){
			update_option('incoom_carrot_bunnycdn_incoom_plugin_cdn', 'default');
		}
	}

	public static function create_files(){
		$files = array(
			array(
				'base'    => carrot_bunnycdn_incoom_plugin_CACHE_PATH,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			)
		);

		incoom_carrot_bunnycdn_incoom_plugin_create_files($files);
	}

	/**
	 * Create the table Items.
	 * @since    2.0.29
	 */
	public static function create_table_items() {
		global $wpdb;

		$table_name = $wpdb->get_blog_prefix() . 'carrot_items';

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$wpdb->hide_errors();

		$charset_collate = $wpdb->get_charset_collate();

		try {
			$sql = "
				CREATE TABLE {$table_name} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				provider VARCHAR(18) NOT NULL,
				region VARCHAR(255) NOT NULL,
				bucket VARCHAR(255) NOT NULL,
				path VARCHAR(1024) NOT NULL,
				original_path VARCHAR(1024) NOT NULL,
				is_private BOOLEAN NOT NULL DEFAULT 0,
				source_type VARCHAR(18) NOT NULL,
				source_id BIGINT(20) UNSIGNED NOT NULL,
				source_path VARCHAR(1024) NOT NULL,
				original_source_path VARCHAR(1024) NOT NULL,
				extra_info LONGTEXT,
				originator TINYINT UNSIGNED NOT NULL DEFAULT 0,
				is_verified BOOLEAN NOT NULL DEFAULT 1,
				PRIMARY KEY  (id),
				UNIQUE KEY uidx_path (path(190), id),
				UNIQUE KEY uidx_original_path (original_path(190), id),
				UNIQUE KEY uidx_source_path (source_path(190), id),
				UNIQUE KEY uidx_original_source_path (original_source_path(190), id),
				UNIQUE KEY uidx_source (source_type, source_id),
				UNIQUE KEY uidx_provider_bucket (provider, bucket(190), id),
				UNIQUE KEY uidx_is_verified_originator (is_verified, originator, id)
				) $charset_collate;
				";
			dbDelta( $sql );
		} catch (\Throwable $th) {}
	}
}