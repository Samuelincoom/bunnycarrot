<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/admin/partials
 */
$tab = 'connectS3';
if(isset($_GET['tab'])){
	$tab = $_GET['tab'];
}
$remove_file_server = get_option('incoom_carrot_bunnycdn_incoom_plugin_remove_from_server_checkbox');
$action_scan = get_option('incoom_carrot_bunnycdn_incoom_plugin_action');
$step_scan = get_option('incoom_carrot_bunnycdn_incoom_plugin_step_scan_attachments', 0);


$default = carrot_bunnycdn_incoom_plugin_whichtype_settings();
$provider = isset($default['provider']) ? $default['provider'] : 'aws';

?>	
<div class="notice-info notice">
	<p><a class="button-primary" target="_blank" href="<?php echo esc_url('//incoomsamuel@gmail.com/');?>"><?php esc_html_e('mail me lets talk!', 'carrot-bunnycdn-incoom-plugin');?></a></p>
</div>

<div class="wrap" id="carrot-bunnycdn-incoom-plugin-wrap">
	<h1><?php esc_html_e( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' );?></h1>

	<div class="incoom_carrot_bunnycdn_loading"><?php esc_html_e('Loading', 'carrot-bunnycdn-incoom-plugin');?>&#8230;</div>

	<div class="col-left">
		<h2 class="nav-tab-wrapper">
		    <a class="nav-tab <?php if($tab == 'connectS3'){echo 'nav-tab-active';}?>" href="<?php echo esc_url(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin&tab=connectS3'));?>"><?php esc_html_e('BunnyCDN credentials', 'carrot-bunnycdn-incoom-plugin');?></a>
		    
			<?php $status = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_success', 0);?>
		    <?php if($status == 1):?>

		    	<?php $bucket_selected = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select', '');?>
			    <a class="<?php if(empty($bucket_selected)){echo 'red';}?> nav-tab <?php if($tab == 'generalsettings'){echo 'nav-tab-active';}?>" href="<?php echo esc_url(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin&tab=generalsettings'));?>">
			    	<?php esc_html_e('Bucket Settings', 'carrot-bunnycdn-incoom-plugin');?>
			    	<?php 
			    	if(empty($bucket_selected)){
			    		esc_html_e('(Bucket does not exist)', 'carrot-bunnycdn-incoom-plugin');
			    	}
			    	?>	
			    </a>

				<?php if ( incoom_carrot_bunnycdn_incoom_plugin_is_plugin_setup() ) {?>
					<a class="nav-tab <?php if($tab == 'assets'){echo 'nav-tab-active';}?>" href="<?php echo esc_url(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin&tab=assets'));?>"><?php esc_html_e('Assets', 'carrot-bunnycdn-incoom-plugin');?></a>          
					<a class="nav-tab <?php if($tab == 'RewriteUrl'){echo 'nav-tab-active';}?>" href="<?php echo esc_url(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin&tab=RewriteUrl'));?>"><?php esc_html_e('Change links to bunny', 'carrot-bunnycdn-incoom-plugin');?></a>          
					
					<?php if($provider !== 'bunnycdn'):?>
					<a class="nav-tab <?php if($tab == 'cors'){echo 'nav-tab-active';}?>" href="<?php echo esc_url(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin&tab=cors'));?>"><?php esc_html_e('CORS', 'carrot-bunnycdn-incoom-plugin');?></a>         
					<?php endif;?>
					
					<?php if( class_exists('WooCommerce') || class_exists('Easy_Digital_Downloads') ):?>
						<a class="nav-tab <?php if($tab == 'download'){echo 'nav-tab-active';}?>" href="<?php echo esc_url(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin&tab=download'));?>"><?php esc_html_e('Download', 'carrot-bunnycdn-incoom-plugin');?></a>
					<?php endif;?>
					
					<a class="nav-tab <?php if($tab == 'advanced'){echo 'nav-tab-active';}?>" href="<?php echo esc_url(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin&tab=advanced'));?>"><?php esc_html_e('Advanced', 'carrot-bunnycdn-incoom-plugin');?></a>
					
				<?php }?>

			<?php endif;?>     

			
			
		</h2>
		<form method="post">
			<input type="hidden" id="incoom_carrot_bunnycdn_settings_nonce" name="incoom_carrot_bunnycdn_settings_nonce" value="<?php echo esc_attr(wp_create_nonce('incoom_carrot_bunnycdn_settings_nonce'));?>">
			
			<?php 
			if($tab == 'connectS3'){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/carrot-bunnycdn-incoom-plugin-admin-settings-connect.php';
			}

			if($tab == 'generalsettings' && $status == 1){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/carrot-bunnycdn-incoom-plugin-admin-settings-general.php';
			}

			if($tab == 'RewriteUrl' && $status == 1){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/carrot-bunnycdn-incoom-plugin-admin-settings-url.php';
			}

			if($tab == 'assets' && $status == 1){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/carrot-bunnycdn-incoom-plugin-admin-settings-assets.php';
			}

			if($tab == 'cors' && $status == 1){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/carrot-bunnycdn-incoom-plugin-admin-settings-cors.php';
			}

			if($tab == 'advanced' && $status == 1){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/carrot-bunnycdn-incoom-plugin-admin-settings-advanced.php';
			}

			if($tab == 'import'){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/carrot-bunnycdn-incoom-plugin-admin-settings-import.php';
			}

			if($tab == 'download' && $status == 1){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/carrot-bunnycdn-incoom-plugin-admin-settings-download.php';
			}

			if($tab == 'sync' && $status == 1){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'partials/carrot-bunnycdn-incoom-plugin-admin-settings-sync.php';
			}else{
			?>
			<input data-tab="<?php echo esc_attr($tab);?>" type="submit" id="incoom_carrot_bunnycdn_settings_submit" class="button-primary" value="<?php esc_html_e('Save Changes', 'carrot-bunnycdn-incoom-plugin');?>">
			<?php }?>

		</form>
	</div>
	<div class="col-right">
		<?php 
		if(!incoom_carrot_bunnycdn_incoom_plugin_cache_folder_check()):
		?>
		<div class="card error">
			<h2 class="title">
				<?php esc_html_e('System error!', 'carrot-bunnycdn-incoom-plugin');?>
			</h2>
			<p><?php printf( _x( 'Please make sure %s is a writable directory.', 'writable directory', 'carrot-bunnycdn-incoom-plugin' ), carrot_bunnycdn_incoom_plugin_CACHE_PATH );?></p>
		</div>
		<?php endif;?>
	</div>
</div>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
