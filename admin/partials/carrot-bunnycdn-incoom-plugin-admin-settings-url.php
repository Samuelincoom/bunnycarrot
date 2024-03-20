<?php $provider = carrot_bunnycdn_incoom_plugin_whichtype();?>
<input type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_url_tab" value="1">
	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Rewrite Media URLs', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>

	            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_rewrite_urls_checkbox', 'on'), 'on', true ); ?>>
	        </span>

	        <span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('For Media Library files that have been copied to your bucket, rewrite the URLs so that they are served from the bucket or CDN instead of your server. Rewrites local URLs to be served from your Amazon S3 bucket, CloudFront or another CDN, or a custom domain.', 'carrot-bunnycdn-incoom-plugin');?></span>

		</label>

	</p>

	<?php if($provider):?>
		<p class="sync-target incoom_carrot_bunnycdn_admin_parent_wrap">

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Delivery', 'carrot-bunnycdn-incoom-plugin');?></span>

			<label>

				<input class="incoom_carrot_bunnycdn_input_text" type="radio" <?php checked(get_option('incoom_carrot_bunnycdn_incoom_plugin_cdn', 'default'), 'default');?> name="incoom_carrot_bunnycdn_incoom_plugin_cdn" value="default">
				<span class="incoom_carrot_bunnycdn_margin_right"><?php echo esc_html($provider::name());?></span>

			</label>

			<label>
				<input class="incoom_carrot_bunnycdn_input_text" type="radio" name="incoom_carrot_bunnycdn_incoom_plugin_cdn" value="cloudflare" <?php checked(get_option('incoom_carrot_bunnycdn_incoom_plugin_cdn', 'default'), 'cloudflare');?>>
				<span class="incoom_carrot_bunnycdn_margin_right"><?php esc_html_e('CloudFlare', 'carrot-bunnycdn-incoom-plugin');?></span>
			</label>
			<span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('If use other CDN, the bucket name must exactly match your CDN domain name.', 'carrot-bunnycdn-incoom-plugin');?></span>
		</p>
	<?php endif;?>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Custom Domain (CNAME)', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>

	            <input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_cname" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_cname'));?>">
	            <span><?php esc_html_e('Ex: assets.example.com', 'carrot-bunnycdn-incoom-plugin');?></span>
	        </span>

			<span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('We strongly recommend you configure a CDN to point at your bucket and configure a subdomain of localhost to point at your CDN. If you don\'t enter a subdomain of your site\'s domain in the field above it will negatively impact your site\'s SEO. By default rewritten URLs use the raw bucket URL format, e.g. https://s3.amazonaws.com.... If you have enabled CloudFront, another CDN, or are using a CNAME, you can set that domain with this setting. ', 'carrot-bunnycdn-incoom-plugin');?></span>

		</label>

	</p>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('CDN exclude file types', 'carrot-bunnycdn-incoom-plugin');?></span>

			<span>

				<input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_cdn_exclude_filetypes" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_cdn_exclude_filetypes'));?>">
				<span><?php esc_html_e('The blank means that will include all types.', 'carrot-bunnycdn-incoom-plugin');?></span>
			</span>

			<span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('If you want to exclude only .mkv and .mp4 files. Separated by commas. EX: mp4,mkv', 'carrot-bunnycdn-incoom-plugin');?></span>

		</label>

	</p>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Force HTTPS', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>

	            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_force_https_checkbox" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_force_https_checkbox', ''), 'on', true ); ?>>
	        </span>

	        <span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('By default we use HTTPS when the request is HTTPS and regular HTTP when the request is HTTP, but you may want to force the use of HTTPS always, regardless of the request.', 'carrot-bunnycdn-incoom-plugin');?></span>

		</label>

	</p>