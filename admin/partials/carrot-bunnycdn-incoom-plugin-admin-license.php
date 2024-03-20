<div class="wrap" id="carrot-bunnycdn-incoom-plugin-wrap">
	<h1><?php esc_html_e( 'carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' );?></h1>

	<?php 
	$active = get_option('incoom_carrot_bunnycdn_incoom_plugin_license_active');
	if($active == '1'){
	?>
	<div id="message" class="updated notice">
		<p><?php esc_html_e( 'You have activated carrot-bunnycdn-incoom-plugin version which allows you to access all the customer benefits. Thank you for choosing carrot-bunnycdn-incoom-plugin.', 'carrot-bunnycdn-incoom-plugin' );?></p>
		<p><a href="<?php echo esc_url(admin_url('admin.php?page=carrot_bunnycdn_incoom_plugin'));?>"><?php esc_html_e('Back to settings.', 'carrot-bunnycdn-incoom-plugin');?></a></p>
	</div>	
	<table class="wc_status_table widefat" cellspacing="0" id="status">
		<thead>
			<tr>
				<th colspan="2">
					<h2>
						<a title="<?php esc_html_e( 'Read more about your license', 'carrot-bunnycdn-incoom-plugin' ); ?>" href="https://themeforest.net/licenses/standard" target="_blank"><i class="dashicons-before dashicons-admin-links"></i><?php esc_html_e( 'About License', 'carrot-bunnycdn-incoom-plugin' ); ?></a>
					</h2>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'License Key', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
				<td><?php echo esc_html(get_option('incoom_carrot_bunnycdn_incoom_plugin_license_key'));?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'License Email', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
				<td><?php echo esc_html(get_option('incoom_carrot_bunnycdn_incoom_plugin_license_email'));?></td>
			</tr>

			<?php $licenseType = get_option('incoom_carrot_bunnycdn_incoom_plugin_license_type', '');?>
			<?php if($licenseType):?>
				<tr>
					<td><?php esc_html_e( 'License Type', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
					<td>
						<a title="<?php esc_html_e( 'Read more about your license', 'carrot-bunnycdn-incoom-plugin' ); ?>" href="https://themeforest.net/licenses/standard" target="_blank"><i class="dashicons-before dashicons-admin-links"></i>
							<?php if($licenseType == '2'){echo esc_html__('Extended License', 'carrot-bunnycdn-incoom-plugin');}else{echo esc_html__('Regular License', 'carrot-bunnycdn-incoom-plugin');}?>
						</a>
					</td>
				</tr>
			<?php endif;?>

			<tr>
				<td><?php esc_html_e('Deactivate License', 'carrot-bunnycdn-incoom-plugin');?>:</td>
				<td>
					<form method="post">
						<input type="hidden" id="incoom_carrot_bunnycdn_settings_nonce" name="incoom_carrot_bunnycdn_settings_nonce" value="<?php echo esc_attr(wp_create_nonce('incoom_carrot_bunnycdn_settings_nonce'));?>">
						<input type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_deactivate_license" value="ok">
						<input type="submit" class="button-secondary" value="<?php esc_html_e('Deactivate license.', 'carrot-bunnycdn-incoom-plugin');?>" />
					</form>
				</td>
			</tr>

		</tbody>
	</table>
	<?php }?>

	<form method="post">
		<?php 
		$active = get_option('incoom_carrot_bunnycdn_incoom_plugin_license_active');
		if($active != '1'){
			?>

			<?php if(isset($_POST['incoom_carrot_bunnycdn_settings_nonce'])){?>
				<?php
				$message = get_option('incoom_carrot_bunnycdn_incoom_plugin_license_active_message', ''); 
				if(!empty($message)){
				?>
					<div class="update-nag"><p><?php echo esc_html($message);?></p></div>
				<?php }?>	
			<?php }?>	

			<input type="hidden" id="incoom_carrot_bunnycdn_settings_nonce" name="incoom_carrot_bunnycdn_settings_nonce" value="<?php echo esc_attr(wp_create_nonce('incoom_carrot_bunnycdn_settings_nonce'));?>">
			<p><?php esc_html_e( 'In order to receive all benefits of carrot-bunnycdn-incoom-plugin, you need to activate your plugin.', 'carrot-bunnycdn-incoom-plugin' );?></p>
			<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

				<label>

					<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Purchase Code', 'carrot-bunnycdn-incoom-plugin');?></span>

			        <span>

			            <input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_license_key" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_license_key'));?>">
			            <span><a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank"><?php esc_html_e('Where Is My Purchase Code?', 'carrot-bunnycdn-incoom-plugin');?></a>
			        </span>

				</label>

			</p>
			<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

				<label>

					<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Purchaser Email', 'carrot-bunnycdn-incoom-plugin');?></span>

			        <span>

			            <input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_license_email" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_license_email'));?>">
			        </span>

				</label>

			</p>
			<input type="submit" class="button-primary" value="<?php esc_html_e('Activate', 'carrot-bunnycdn-incoom-plugin');?>">	
		<?php }?>
	</form>	
</div>