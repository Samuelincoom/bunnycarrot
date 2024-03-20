<?php $provider = carrot_bunnycdn_incoom_plugin_whichtype();?>
<input type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_url_tab" value="1">
	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Rewrite Media URLs', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>

	            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox', 'on'), 'on', true ); ?>>
	        </span>

	        <span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('', 'carrot-bunnycdn-incoom-plugin');?></span>

		</label>

	</p>

	<?php if($provider):?>
		<p class="sync-target incoom_carrot_bunnycdn_admin_parent_wrap">

			
			<label>

				<input class="incoom_carrot_bunnycdn_input_text" type="hidden" <?php checked(get_option('incoom_carrot_bunnycdn_incoom_plugin_cdn', 'default'), 'default');?> name="incoom_carrot_bunnycdn_incoom_plugin_cdn" value="default">
				<span class="incoom_carrot_bunnycdn_margin_right"><?php echo esc_html($provider::name());?></span>

			</label>

			
				</p>
	<?php endif;?>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Custom Domain (CNAME)', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>

	            <input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_cname" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_cname'));?>">
	            <span><?php esc_html_e('Ex: assets.example.com', 'carrot-bunnycdn-incoom-plugin');?></span>
	        </span>

			
		</label>

	</p>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('files to exclude from BunnyCDN', 'carrot-bunnycdn-incoom-plugin');?></span>

			<span>

				<input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_cdn_exclude_filetypes" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_cdn_exclude_filetypes'));?>">
				<span><?php esc_html_e('The blank means that will include all types.', 'carrot-bunnycdn-incoom-plugin');?></span>
			</span>

			
		</label>

	</p>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Force HTTPS', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>

	            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_force_https_checkbox" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_force_https_checkbox', ''), 'on', true ); ?>>
	        </span>

	       
		</label>

	</p>