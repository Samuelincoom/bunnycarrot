<?php $checkbox = get_option('incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox', '');?>
<input type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_url_tab_assets" value="1">
<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Change link of static files', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox" <?php checked( $checkbox, 'on', true ); ?>>
        </span>

        
        <span class="incoom_carrot_bunnycdn_description_checkbox show_if_assets_rewrite_urls <?php if($checkbox != 'on'){echo 'hidden';}?>"><input type="button" class="button-secondary" value="<?php esc_html_e('Scan assets', 'carrot-bunnycdn-incoom-plugin');?>" id="incoom_carrot_bunnycdn_scan_assets"></span>

	</label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Custom path', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_pull_assets_path" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_pull_assets_path', 'pull-assets/'));?>">
            <span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('EX: pull-assets/', 'carrot-bunnycdn-incoom-plugin');?></span>
        </span>

	</label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

   <label>

        <span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('upload JS files to bunny CDN', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_offload_js" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_offload_js', 'on'), 'on', true ); ?>>
        </span>

    </label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

	
	
	 <label>

        <span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('upload CSS files to bunnyCDN', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_offload_css" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_offload_css', 'on'), 'on', true ); ?>>
        </span>

    </label>
	
	
	

</p>