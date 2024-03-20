<?php

/**
 * Config API
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/admin/partials
 */
$default = carrot_bunnycdn_incoom_plugin_whichtype_settings();
$regional = get_option('incoom_carrot_bunnycdn_incoom_plugin_bucket_regional', 'nyc3');
$provider = isset($default['provider']) ? $default['provider'] : 'bunnycdn';

?>
<?php $status = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_success', 0);?>
<div id="incoom_carrot_bunnycdn_connection_status">
	<div>
		<?php if($status == 1):?>
			<p class="incoom_carrot_bunnycdn_error_accessing_class">
				<img class="incoom_carrot_bunnycdn_error_accessing_class_img" style="width: 35px;" src="<?php echo esc_url(carrot_bunnycdn_incoom_plugin_PLUGIN_URI.'admin/images/access-ok.png');?>">
				<span class="incoom_carrot_bunnycdn_error_accessing_class_span"><?php esc_html_e('Connection was successful', 'carrot-bunnycdn-incoom-plugin');?></span>
			</p>
		<?php else:?>
			<p class="incoom_carrot_bunnycdn_error_accessing_class">
				<img class="incoom_carrot_bunnycdn_error_accessing_class_img" style="width: 35px;" src="<?php echo esc_url(carrot_bunnycdn_incoom_plugin_PLUGIN_URI.'admin/images/access-error-logs.png');?>">
				<span class="incoom_carrot_bunnycdn_error_accessing_class_span"><?php esc_html_e('An error occurred while accessing, the credentials (access key or secret key) are NOT correct', 'carrot-bunnycdn-incoom-plugin');?></span>
			</p>
		<?php endif;?>
	</div>
</div>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap">
	<label>
		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Storage Provider', 'carrot-bunnycdn-incoom-plugin');?></span>
		<select class="incoom_carrot_bunnycdn_input_text" name="incoom_carrot_bunnycdn_connection_provider">
			<?php foreach(carrot_bunnycdn_incoom_plugin_whichtype as $key => $cloud ){?>
			<option value="<?php echo esc_attr($key);?>" <?php selected($key, $provider);?>><?php echo esc_html($cloud);?></option>
			<?php }?>
		</select>

		<span class="<?php if($provider != 'DO'){echo 'hidden';}?> conditional show_if_DO"><?php esc_html_e('Region', 'carrot-bunnycdn-incoom-plugin');?></span>
		<select class="<?php if($provider != 'DO'){echo 'hidden';}?> incoom_carrot_bunnycdn_input_text conditional show_if_DO" name="incoom_carrot_bunnycdn_incoom_plugin_bucket_regional">
			<?php foreach(carrot_bunnycdn_incoom_plugin_DO_REGIONS as $key => $cloud ){?>
			<option value="<?php echo esc_attr($key);?>" <?php selected($key, $regional);?>><?php echo esc_html($cloud);?></option>
			<?php }?>
		</select>
		<span class="incoom_carrot_bunnycdn_description"><?php esc_html_e('this is just a test, and not certified from the official bunny team yet', 'carrot-bunnycdn-incoom-plugin');?></span>
		
	</label>
</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_google <?php if($provider != 'google'){echo 'hidden';}?>">
	<label>
		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Credentials', 'carrot-bunnycdn-incoom-plugin');?></span>
		<textarea class="incoom_carrot_bunnycdn_input_text" name="incoom_carrot_bunnycdn_connection_credentials"><?php echo json_encode(get_option('incoom_carrot_bunnycdn_incoom_plugin_google_credentials', ''));?></textarea>
		<span class="incoom_carrot_bunnycdn_description"><?php esc_html_e('Authentication credentials to your application.', 'carrot-bunnycdn-incoom-plugin');?></span>
	</label>
</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_cloudflare-r2 show_if_DO show_if_aws show_if_wasabi show_if_bunnycdn <?php if($provider == 'google'){echo 'hidden';}?>">
	<label>
		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Access key', 'carrot-bunnycdn-incoom-plugin');?></span>
		<input class="incoom_carrot_bunnycdn_input_text" type="password" name="incoom_carrot_bunnycdn_connection_access_key_text" value="<?php echo esc_attr(isset($default['access_key']) ? $default['access_key'] : '');?>">
		<span class="incoom_carrot_bunnycdn_description"><?php esc_html_e('your bunnycdn account key found in accounts tab there', 'carrot-bunnycdn-incoom-plugin');?></span>
	</label>
</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_bunnycdn <?php if($provider !== 'bunnycdn'){echo 'hidden';}?>">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('The first password at Bunnycdn storage bucket', 'carrot-bunnycdn-incoom-plugin');?></span>

		<input class="incoom_carrot_bunnycdn_input_text" type="password" name="incoom_carrot_bunnycdn_connection_bunny_storage_key" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_connection_bunny_storage_key', ''));?>">

		

	</label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_bunnycdn <?php if($provider !== 'bunnycdn'){echo 'hidden';}?>">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('bunny Bucket name with slashes at front and back', 'carrot-bunnycdn-incoom-plugin');?></span>

		<input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_connection_bunny_storage_path" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_connection_bunny_storage_path', ''));?>">
		
	</label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_cloudflare-r2 show_if_DO show_if_aws show_if_wasabi <?php if($provider == 'google' || $provider == 'bunnycdn'){echo 'hidden';}?>">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Secret access key', 'carrot-bunnycdn-incoom-plugin');?></span>

		<input class="incoom_carrot_bunnycdn_input_text" type="password" name="incoom_carrot_bunnycdn_connection_secret_access_key_text" value="<?php echo esc_attr(isset($default['secret_access_key']) ? $default['secret_access_key'] : '');?>">

		<span class="incoom_carrot_bunnycdn_description"><?php esc_html_e('The auto-upload to bunnystream doesnt work yet. i used the framework for AWS, cloudflare, and many other methods but failed.. so for now, you should select CDN. that one works perfect. as i improve this when i get time, hopefully, we will have the stream section of bunnycdn also being able to upload automatically when i understand the API more', 'carrot-bunnycdn-incoom-plugin');?></span>

	</label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_google <?php if($provider != 'google'){echo 'hidden';}?>">

	<label>

    <span class="incoom_carrot_bunnycdn_description" style="margin-top: 10px;"><?php esc_html_e('If you don\'t know where to search for your Google Cloud Storage credentials,', 'carrot-bunnycdn-incoom-plugin');?> <a href="https://cloud.google.com/storage/docs/reference/libraries#setting_up_authentication" target="_blank"><?php esc_html_e('you can find them here', 'carrot-bunnycdn-incoom-plugin');?></a>

    </span>

	</label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_aws <?php if($provider != 'aws'){echo 'hidden';}?>">

	<label>

    

	</label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_wasabi <?php if($provider != 'wasabi'){echo 'hidden';}?>">

	<label>

    <span class="incoom_carrot_bunnycdn_description" style="margin-top: 10px;"><?php esc_html_e('If you don\'t know where to search for your Wasabi credentials,', 'carrot-bunnycdn-incoom-plugin');?> <a href="https://wasabi-support.zendesk.com/hc/en-us/articles/360019677192-Creating-a-Root-Access-Key-and-Secret-Key" target="_blank"><?php esc_html_e('you can find them here', 'carrot-bunnycdn-incoom-plugin');?></a>

    </span>

	</label>

</p>


<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_cloudflare-r2 <?php if($provider != 'cloudflare-r2'){echo 'hidden';}?>">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Account ID', 'carrot-bunnycdn-incoom-plugin');?></span>

		<input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_connection_r2_account_id" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_connection_r2_account_id', ''));?>">
		<span class="incoom_carrot_bunnycdn_description"><?php esc_html_e('Set the Account ID', 'carrot-bunnycdn-incoom-plugin');?></span>

	</label>

</p>
<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional show_if_cloudflare-r2 <?php if($provider != 'cloudflare-r2'){echo 'hidden';}?>">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Public Bucket URL', 'carrot-bunnycdn-incoom-plugin');?></span>

		<input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_connection_r2_bucket_url" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_connection_r2_bucket_url', ''));?>">
		<span class="incoom_carrot_bunnycdn_description"><?php esc_html_e('Set the Public Bucket URL', 'carrot-bunnycdn-incoom-plugin');?></span>

	</label>

</p>