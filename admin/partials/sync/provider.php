<?php
$provider_from = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_provider_from');
$settings_from = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_settings_from', []);
$provider_to = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_provider_to');
$settings_to = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_settings_to', []);

$credentials_from = isset($settings_from['credentials_key']) ? $settings_from['credentials_key'] : '';
$access_key_from = isset($settings_from['access_key']) ? $settings_from['access_key'] : '';
$secret_access_from = isset($settings_from['secret_access_key']) ? $settings_from['secret_access_key'] : '';

$credentials_to = isset($settings_to['credentials_key']) ? $settings_to['credentials_key'] : '';
$access_key_to = isset($settings_to['access_key']) ? $settings_to['access_key'] : '';
$secret_access_to = isset($settings_to['secret_access_key']) ? $settings_to['secret_access_key'] : '';

$client_from = null;
$client_to = null;

if(!empty($provider_from) && !empty($settings_from)){
	$client_from = carrot_bunnycdn_incoom_plugin_whichtype($provider_from, $settings_from);
}

if(!empty($provider_to) && !empty($settings_to)){
	$client_to = carrot_bunnycdn_incoom_plugin_whichtype($provider_to, $settings_to);
}

$Bucket_Selected_from = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_bucket_from', '');
$Bucket_Selected_to = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_bucket_to', '');

$regional_from = get_option('incoom_carrot_bunnycdn_incoom_plugin_bucket_regional_from', 'nyc3');
$regional_to = get_option('incoom_carrot_bunnycdn_incoom_plugin_bucket_regional_to', 'nyc3');
?>
<div class="sync-content-provider">
	<div class="sync-content-provider-col sync-content-provider-from">
		<p class="incoom_carrot_bunnycdn_admin_parent_wrap">
			<label>
				<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('From', 'carrot-bunnycdn-incoom-plugin');?></span>
				<select data-target="from" class="incoom_carrot_bunnycdn_input_text sync-provider" name="incoom_carrot_bunnycdn_connection_provider_from">
					<option value="0"><?php esc_html_e('Select a cloud', 'carrot-bunnycdn-incoom-plugin');?></option>
					<?php foreach(carrot_bunnycdn_incoom_plugin_whichtype_SYNC as $key => $cloud ){?>
					<option value="<?php echo esc_attr($key);?>" <?php selected($key, $provider_from);?>><?php echo esc_html($cloud);?></option>
					<?php }?>
				</select>
				<span class="incoom_carrot_bunnycdn_title <?php if($provider_from != 'DO'){echo 'hidden';}?> conditional_from show_if_DO"><?php esc_html_e('Region', 'carrot-bunnycdn-incoom-plugin');?></span>
				<select class="<?php if($provider_from != 'DO'){echo 'hidden';}?> incoom_carrot_bunnycdn_input_text conditional_from show_if_DO sync-region" name="incoom_carrot_bunnycdn_incoom_plugin_bucket_regional_from">
					<?php foreach(carrot_bunnycdn_incoom_plugin_DO_REGIONS as $key => $cloud ){?>
					<option value="<?php echo esc_attr($key);?>" <?php selected($key, $regional_from);?>><?php echo esc_html($cloud);?></option>
					<?php }?>
				</select>
			</label>
		</p>
		<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional_from show_if_google <?php if($provider_from != 'google'){echo 'hidden';}?>">
			<label>
				<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Credentials', 'carrot-bunnycdn-incoom-plugin');?></span>
				<textarea class="conditional_change incoom_carrot_bunnycdn_input_text" name="incoom_carrot_bunnycdn_connection_credentials_from"><?php if(!empty($credentials_from)){echo json_encode($credentials_from);}?></textarea>
			</label>
		</p>

		<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional_from  show_if_DO show_if_aws show_if_wasabi <?php if($provider_from == 'google'){echo 'hidden';}?>">
			<label>
				<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Access key', 'carrot-bunnycdn-incoom-plugin');?></span>
				<input class="conditional_change incoom_carrot_bunnycdn_input_text" type="password" name="incoom_carrot_bunnycdn_connection_access_key_text_from" value="<?php echo $access_key_from;?>">
			</label>
		</p>

		<p class="incoom_carrot_bunnycdn_admin_parent_wrap conditional_from  show_if_DO show_if_aws show_if_wasabi <?php if($provider_from == 'google'){echo 'hidden';}?>">

			<label>

				<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Secret access key', 'carrot-bunnycdn-incoom-plugin');?></span>

				<input class="conditional_change incoom_carrot_bunnycdn_input_text" type="password" name="incoom_carrot_bunnycdn_connection_secret_access_key_text_from" value="<?php echo $secret_access_from;?>">

			</label>

		</p>
		<div class="sync-content-bucket">
			<?php if($client_from != null):?>
				<p class="incoom_carrot_bunnycdn_admin_parent_wrap">
					<label>
						<span class="incoom_carrot_bunnycdn_title">
							<?php if($type == 'bucket'){?>
								<?php esc_html_e('From bucket', 'carrot-bunnycdn-incoom-plugin');?>
							<?php }else{?>
								<?php esc_html_e('Select bucket', 'carrot-bunnycdn-incoom-plugin');?>
							<?php }?>
						</span>
						<select data-target="from" class="incoom_carrot_bunnycdn_input_text" name="incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_from" tabindex="-1" aria-hidden="true"><?php echo $client_from->Show_Buckets($Bucket_Selected_from);?></select>
					</label>
				</p>
				<?php if($type == 'bucket'){?>
				<p class="incoom_carrot_bunnycdn_admin_parent_wrap">
					<label>
						<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('To bucket', 'carrot-bunnycdn-incoom-plugin');?></span>
						<select data-target="to" class="incoom_carrot_bunnycdn_input_text" name="incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_to" tabindex="-1" aria-hidden="true"><?php echo $client_from->Show_Buckets($Bucket_Selected_to);?></select>
					</label>
				</p>
				<?php }?>
			<?php endif;?>
		</div>
	</div>
	<?php if($type == 'cloud'):?>
		<div class="sync-content-provider-col sync-content-provider-to">
			<p class="incoom_carrot_bunnycdn_admin_parent_wrap">
				<label>
					<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('To', 'carrot-bunnycdn-incoom-plugin');?></span>
					<select data-target="to" class="incoom_carrot_bunnycdn_input_text sync-provider" name="incoom_carrot_bunnycdn_connection_provider_to">
						<option value="0"><?php esc_html_e('Select a cloud', 'carrot-bunnycdn-incoom-plugin');?></option>
						<?php foreach(carrot_bunnycdn_incoom_plugin_whichtype_SYNC as $key => $cloud ){?>
						<option value="<?php echo esc_attr($key);?>" <?php selected($key, $provider_to);?>><?php echo esc_html($cloud);?></option>
						<?php }?>
					</select>

					<span class="incoom_carrot_bunnycdn_title <?php if($provider_to != 'DO'){echo 'hidden';}?> conditional_to show_if_DO"><?php esc_html_e('Region', 'carrot-bunnycdn-incoom-plugin');?></span>
					<select class="<?php if($provider_to != 'DO'){echo 'hidden';}?> incoom_carrot_bunnycdn_input_text conditional_to show_if_DO sync-region" name="incoom_carrot_bunnycdn_incoom_plugin_bucket_regional_to">
						<?php foreach(carrot_bunnycdn_incoom_plugin_DO_REGIONS as $key => $cloud ){?>
						<option value="<?php echo esc_attr($key);?>" <?php selected($key, $regional_to);?>><?php echo esc_html($cloud);?></option>
						<?php }?>
					</select>
				</label>
			</p>

			<p class="incoom_carrot_bunnycdn_admin_parent_wrap <?php if($provider_to != 'google'){echo 'hidden';}?> conditional_to show_if_google">
				<label>
					<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Credentials', 'carrot-bunnycdn-incoom-plugin');?></span>
					<textarea class="conditional_change incoom_carrot_bunnycdn_input_text" name="incoom_carrot_bunnycdn_connection_credentials_to"><?php if(!empty($credentials_to)){echo json_encode($credentials_to);}?></textarea>
				</label>
			</p>

			<p class="incoom_carrot_bunnycdn_admin_parent_wrap <?php if($provider_to == 'google'){echo 'hidden';}?> conditional_to  show_if_DO show_if_aws show_if_wasabi">
				<label>
					<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Access key', 'carrot-bunnycdn-incoom-plugin');?></span>
					<input class="conditional_change incoom_carrot_bunnycdn_input_text" type="password" name="incoom_carrot_bunnycdn_connection_access_key_text_to" value="<?php echo $access_key_to;?>">
				</label>
			</p>

			<p class="incoom_carrot_bunnycdn_admin_parent_wrap <?php if($provider_to == 'google'){echo 'hidden';}?> conditional_to  show_if_DO show_if_aws show_if_wasabi">

				<label>

					<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Secret access key', 'carrot-bunnycdn-incoom-plugin');?></span>

					<input class="conditional_change incoom_carrot_bunnycdn_input_text" type="password" name="incoom_carrot_bunnycdn_connection_secret_access_key_text_to" value="<?php echo $secret_access_to;?>">

				</label>

			</p>
			<div class="sync-content-bucket">
				<?php if($client_to != null):?>
					<p class="incoom_carrot_bunnycdn_admin_parent_wrap">
						<label>
							<span class="incoom_carrot_bunnycdn_title">
								<?php esc_html_e('Select bucket', 'carrot-bunnycdn-incoom-plugin');?>
							</span>
							<select data-target="to" class="incoom_carrot_bunnycdn_input_text" name="incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_to" tabindex="-1" aria-hidden="true"><?php echo $client_to->Show_Buckets($Bucket_Selected_to);?></select>
						</label>
					</p>
				<?php endif;?>
			</div>
		</div>
	<?php endif;?>
</div>