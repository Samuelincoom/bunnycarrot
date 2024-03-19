<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Samuelincoom/bunnycarrot
 * @since             1.0.0
 * @package           carrot_bunnycdn_incoom_plugin
 *
 * @wordpress-plugin
 * Plugin Name:       carrot-bunnycdn-incoom-plugin
 * Plugin URI:        https://github.com/Samuelincoom/bunnycarrot
 * Description:       this is a test plugin. it works though. it works to automatically pull all media files and static files from a website to bunny cdn.. This was made by samuel incoom and not yet official in our bunnycdn team yet. just a private internal plugin. but you can use it to acheive what has been described. thank you 
 * Version:           1.0.0
 * Author:            Samuel incoom
 * Author URI:        https://github.com/Samuelincoom/bunnycarrot
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       carrot-bunnycdn-incoom-plugin
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'carrot_bunnycdn_incoom_plugin_VERSION', '1.0.0' );
define( "carrot_bunnycdn_incoom_plugin_PLUGIN_DIR", plugin_dir_path(__FILE__) );
define( "carrot_bunnycdn_incoom_plugin_PLUGIN_URI", plugin_dir_url(__FILE__) );
define( "carrot_bunnycdn_incoom_plugin_DEFAULT_EXPIRES", 900 );
define( "carrot_bunnycdn_incoom_plugin_DIR_FILE", __FILE__ );
define( "carrot_bunnycdn_incoom_plugin_MINIMUM_PHP_VERSION", '7.3' );
define( "carrot_bunnycdn_incoom_plugin_PLUGIN_NAME", 'carrot-bunnycdn-incoom-plugin' );

if ( ! defined( 'FS_CHMOD_FILE' ) ) {
	define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
}
//raz0r
update_option('incoom_carrot_bunnycdn_incoom_plugin_license_active', 1);
update_option('incoom_carrot_bunnycdn_incoom_plugin_license_key', '*************');
update_option('incoom_carrot_bunnycdn_incoom_plugin_license_email', 'email@mail.com');
define( 
	"carrot_bunnycdn_incoom_plugin_CORS_AllOWED_METHODS", 
	array(
		'GET', 
		'HEAD',
		'PUT',
		'POST',
		'DELETE'
	) 
);

define( 
	"carrot_bunnycdn_incoom_plugin_whichtype", 
	array(
		'aws' => esc_html__('Bunny stream', 'carrot-bunnycdn-incoom-plugin'), 
		'bunnycdn' => esc_html__('Bunny CDN', 'carrot-bunnycdn-incoom-plugin'),
	
	) 
);

define( 
	"carrot_bunnycdn_incoom_plugin_whichtype_SYNC", 
	carrot_bunnycdn_incoom_plugin_whichtype 
);

define( 
	"carrot_bunnycdn_incoom_plugin_DO_REGIONS", 
	array(
        'nyc3' => 'New York City, United States',
        'sfo2' => 'San Francisco, United States',
        'sgp1' => 'Singapore',
        'fra1' => 'Frankfurt, Germany',
        'ams3' => 'Amsterdam',
		'sfo3' => 'San Francisco 3, United States',
	) 
);

$upload_dir = wp_upload_dir();

define( 
	"carrot_bunnycdn_incoom_plugin_CACHE_PATH", 
	$upload_dir['basedir'] . '/carrot-wordpress-offload' 
);

define( 
	"carrot_bunnycdn_incoom_plugin_CACHE_KEY_ATTACHED_FILE", 
	'carrot_posturl_'
);

define( 
	"carrot_bunnycdn_incoom_plugin_CACHE_TIMEOUT_ATTACHED_FILE", 
	30
);

define( 
	"carrot_bunnycdn_incoom_plugin_ITEMS_TABLE", 
	'carrot_items'
);

/**
 * Cache Key
 * Get the total attachment and total offloaded/not offloaded attachment counts
*/
define( 
	"carrot_bunnycdn_incoom_plugin_CACHE_KEY_MEDIA_COUNTS", 
	'___carrot_' . get_current_blog_id() . '_media_counts_'
);

/**
 * Cache Key
 * Count attachments on current site.
*/
define( 
	"carrot_bunnycdn_incoom_plugin_CACHE_KEY_ATTACHMENT_COUNTS", 
	'___carrot_' . get_current_blog_id() . '_attachment_counts'
);

define( 
	"carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY", 
	'__carrot_flash_messages'
);

require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'libraries/action-scheduler/action-scheduler.php' );
require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'includes/class-carrot-bunnycdn-incoom-plugin-licenser.php' );

function incoom_carrot_bunnycdn_incoom_plugin_increase_time_limit( $time_limit ) {
	return 120;
}
add_filter( 'action_scheduler_queue_runner_time_limit', 'incoom_carrot_bunnycdn_incoom_plugin_increase_time_limit' );

function incoom_carrot_bunnycdn_incoom_plugin_increase_action_scheduler_batch_size( $batch_size ) {
	return 50;
}
add_filter( 'action_scheduler_queue_runner_batch_size', 'incoom_carrot_bunnycdn_incoom_plugin_increase_action_scheduler_batch_size' );

function incoom_carrot_bunnycdn_incoom_plugin_increase_action_scheduler_concurrent_batches( $concurrent_batches ) {
	return 4;
}
add_filter( 'action_scheduler_queue_runner_concurrent_batches', 'incoom_carrot_bunnycdn_incoom_plugin_increase_action_scheduler_concurrent_batches' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
require plugin_dir_path( __FILE__ ) . 'functions/cache-helpers.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-carrot-bunnycdn-incoom-plugin-activator.php
 */
function activate_carrot_bunnycdn_incoom_plugin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-carrot-bunnycdn-incoom-plugin-activator.php';
	carrot_bunnycdn_incoom_plugin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-carrot-bunnycdn-incoom-plugin-deactivator.php
 */
function deactivate_carrot_bunnycdn_incoom_plugin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-carrot-bunnycdn-incoom-plugin-deactivator.php';
	carrot_bunnycdn_incoom_plugin_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_carrot_bunnycdn_incoom_plugin' );
register_deactivation_hook( __FILE__, 'deactivate_carrot_bunnycdn_incoom_plugin' );

require plugin_dir_path( __FILE__ ) . 'functions/global.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-carrot-bunnycdn-incoom-plugin.php';

/**
 * Transform full width hyphens and other variety hyphens in half size into simple hyphen,
 * and avoid consecutive hyphens and also at the beginning and end as well.
 */
function incoom_carrot_bunnycdn_incoom_plugin_format_hyphens( $str ) {
	$hyphen = '-';
	$hyphens = [
		'﹣', '－', '−', '⁻', '₋',
		'‐', '‑', '‒', '–', '—',
		'―', '﹘', 'ー','ｰ',
	];
	$str = str_replace( $hyphens, $hyphen, $str );
	// remove at the beginning and end.
	$beginning = mb_substr( $str, 0, 1 );
	if ( $beginning === $hyphen ) {
		$str = mb_substr( $str, 1 );
	}
	$end = mb_substr( $str, -1 );
	if ( $end === $hyphen ) {
		$str = mb_strcut( $str, 0, mb_strlen( $str ) - 1 );
	}
	$str = preg_replace( '/-{2,}/u', '-', $str );
	$str = trim( $str, implode( '', $hyphens ) );
	return $str;
}
/**
 * Filter {@see sanitize_file_name()} and return an unique file name.
 *
 * @param  string $filename
 * @return string
 */


 function incoom_carrot_bunnycdn_incoom_plugin_change_file_name( $file ) {
	$newName = incoom_carrot_bunnycdn_incoom_plugin_modify_uploaded_file_names($file['name']);
	$file['name'] = $newName;
	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'incoom_carrot_bunnycdn_incoom_plugin_change_file_name' );

function incoom_carrot_bunnycdn_incoom_plugin_modify_uploaded_file_names( $filename ) {
	
    $info = pathinfo( $filename );
    $ext  = empty( $info['extension'] ) ? '' : '.' . $info['extension'];
	if(empty($ext)){
		return $filename;
	}

	$name = basename( $filename, $ext );
	$name = remove_accents($name);
	
	// Related to English
	$name = str_replace( "'s", "", $name );
	$name = str_replace( "n\'t", "nt", $name );
	$name = preg_replace( "/\'m/i", "-am", $name );

	// We probably do not want those neither
	$name = str_replace( "'", "-", $name );
	$name = preg_replace( "/\//s", "-", $name );
	$name = str_replace( ['.','…'], "", $name );
	$name = str_replace(' ', '-', $name);
	$name = preg_replace('/[^A-Za-z0-9\-]/', "-", $name);
	$name = incoom_carrot_bunnycdn_incoom_plugin_format_hyphens($name);
	
	if($name === '-'){
		$name = 'carrot-wom-' . time() . '' . rand();
	}

	$object_versioning = get_option('incoom_carrot_bunnycdn_incoom_plugin_object_versioning');
	if(empty($object_versioning)){
		return $name . $ext;
	}
	
	return $name. ('-'.time().''.rand()) . $ext;
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_carrot_bunnycdn_incoom_plugin() {

	$plugin = new carrot_bunnycdn_incoom_plugin();
	$plugin->run();

}
run_carrot_bunnycdn_incoom_plugin();