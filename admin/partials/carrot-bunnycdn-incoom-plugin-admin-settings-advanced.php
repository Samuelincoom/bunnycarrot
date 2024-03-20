
<input type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_advanced_tab" value="1">

<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Removing the emoji.', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_emoji" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_emoji'), 'on', true ); ?>>
        </span>

	</label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Minify HTML', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_minify_html" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_minify_html'), 'on', true ); ?>>
        </span>

	</label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

	<label>

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('WebP versions', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_webp" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_webp'), 'on', true ); ?>>
            <span><?php esc_html_e('Create also WebP version of the images.', 'carrot-bunnycdn-incoom-plugin');?></span>
        </span>
        <span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('', 'carrot-bunnycdn-incoom-plugin');?></span>
       
	</label>

</p>