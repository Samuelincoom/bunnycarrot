<?php

/**
 * Config S3
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/admin/partials
 */
?>
<?php $status = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_success', 0);?>
<?php if($status == 1):?>
	<?php require_once( carrot_bunnycdn_incoom_plugin_PLUGIN_DIR . 'admin/partials/carrot-bunnycdn-incoom-plugin-admin-settings-aws.php' );?>
<?php else:?>
	<p class="incoom_carrot_bunnycdn_error_accessing_class">
		<img class="incoom_carrot_bunnycdn_error_accessing_class_img" style="width: 35px;" src="<?php echo esc_url(carrot_bunnycdn_incoom_plugin_PLUGIN_URI.'admin/images/access-error-logs.png');?>">
		<span class="incoom_carrot_bunnycdn_error_accessing_class_span"><?php esc_html_e('An error occurred while accessing, the credentials (access key or secret key) are NOT correct', 'carrot-bunnycdn-incoom-plugin');?></span>
	</p>
<?php endif;?>