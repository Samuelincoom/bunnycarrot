<?php
if (!defined('ABSPATH')) {exit;}

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */
class carrot_bunnycdn_incoom_plugin_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		self::unscheduled_background_tasks();
	}

	public static function unscheduled_background_tasks() {
		if ( function_exists( 'carrot_bunnycdn_incoom_plugin_unschedule_action' ) ) {
			carrot_bunnycdn_incoom_plugin_after_action_scheduler_completed();
			carrot_bunnycdn_incoom_plugin_unschedule_action( 'incoom_carrot_bunnycdn_incoom_plugin_cronjob_verify_offloaded_files' );
		}
	}
}
