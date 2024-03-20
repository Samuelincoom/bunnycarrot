<?php $aws_s3_client = carrot_bunnycdn_incoom_plugin_whichtype();?>
<input type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_general_tab" value="1">
<input type="hidden" name="incoom_carrot_bunnycdn_update_cache_control" value="0">
	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

				<?php if($aws_s3_client::identifier() == 'bunnycdn'):?>
					<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Choose pull zone', 'carrot-bunnycdn-incoom-plugin');?></span>
				<?php else:?>
					<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Set a bucket name', 'carrot-bunnycdn-incoom-plugin');?></span>
				<?php endif;?>

				<span id="as3s_Buckets_List_select">
					<select class="incoom_carrot_bunnycdn_input_text" name="incoom_carrot_bunnycdn_incoom_plugin_connection_bucket_selected_select" tabindex="-1" aria-hidden="true">
					<?php echo $aws_s3_client->Show_Buckets();?></select> 

					<?php if(!in_array($aws_s3_client::identifier(), ['bunnycdn', 'cloudflare-r2'])):?>
						<?php esc_html_e('Or', 'carrot-bunnycdn-incoom-plugin');?> 
						<input type="button" class="button-secondary" value="<?php esc_html_e('Create bucket', 'carrot-bunnycdn-incoom-plugin');?>" id="incoom_carrot_bunnycdn_create_bucket">
					<?php endif;?>
					
				</span>

				<?php if($aws_s3_client::identifier() == 'bunnycdn'):?>
					<span class="incoom_carrot_bunnycdn_description" style="margin-top: 10px;"><?php echo sprintf(esc_html__('', 'carrot-bunnycdn-incoom-plugin'), $aws_s3_client::name());?>, <a href="<?php echo esc_url(carrot_bunnycdn_incoom_plugin_whichtype()::docs_link_create_bucket());?>" target="_blank"><?php ?></a>
				<?php else:?>
					<span class="incoom_carrot_bunnycdn_description" style="margin-top: 10px;"><?php echo sprintf(esc_html__('', 'carrot-bunnycdn-incoom-plugin'), $aws_s3_client::name());?>, <a href="<?php echo esc_url(carrot_bunnycdn_incoom_plugin_whichtype()::docs_link_create_bucket());?>" target="_blank"><?php ?></a>
				<?php endif;?>

	        </span>

		</label>

	</p>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Path', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>

	            <input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_bucket_folder_main" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_bucket_folder_main', ''));?>">
	            <span class="incoom_carrot_bunnycdn_margin_right"><?php esc_html_e('EX: my-folder/my-sub-folder', 'carrot-bunnycdn-incoom-plugin');?></span>
	        </span>
	        <span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('', 'carrot-bunnycdn-incoom-plugin');?></span>
		</label>

	</p>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			
	        <span>

	            <input type="hidden" class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_copy_file_s3_checkbox'), 'on', true ); ?>>

	           
	        </span>

	        <span class="incoom_carrot_bunnycdn_description_checkbox">
	        	<?php echo sprintf(esc_html__('', 'carrot-bunnycdn-incoom-plugin'), $aws_s3_client::name(), $aws_s3_client::name());?></span>

		</label>

	</p>
	
	<?php if( class_exists('WooCommerce') || class_exists('Easy_Digital_Downloads') ):?>
		<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Expiration time', 'carrot-bunnycdn-incoom-plugin');?></span>

			<span>
				<input class="incoom_carrot_bunnycdn_input_text" type="number" min="5" max="2160" name="incoom_carrot_bunnycdn_incoom_plugin_time_valid_number" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_time_valid_number', 5));?>">
			</span>

			<span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('The maximum expiration time for presigned url(WooCommerce vs Easy Digital Downloads). Default: 5 minutes', 'carrot-bunnycdn-incoom-plugin');?></span>

		</p>
	<?php endif;?>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap hidden">

		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Permissions', 'carrot-bunnycdn-incoom-plugin');?></span>

		<label>

			<input class="incoom_carrot_bunnycdn_input_text" type="radio" name="incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', 'public'), 'private', true ); ?> value="private">

            <span class="incoom_carrot_bunnycdn_margin_right"><?php esc_html_e('Private', 'carrot-bunnycdn-incoom-plugin');?></span>

		</label>

		<label>
			<input class="incoom_carrot_bunnycdn_input_text" type="radio" name="incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_private_public_radio_button', 'public'), 'public', true ); ?> value="public">

            <span class="incoom_carrot_bunnycdn_margin_right"><?php esc_html_e('Public', 'carrot-bunnycdn-incoom-plugin');?></span>
		</label>

		<span class="incoom_carrot_bunnycdn_description"><?php echo sprintf(esc_html__('By setting the files as public, anyone who knows the %s URL will have complete access to it.', 'carrot-bunnycdn-incoom-plugin'), $aws_s3_client::name());?></span>

	</p>



	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			
	        <span>
	            <input class="incoom_carrot_bunnycdn_input_text" type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_cache_control" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_cache_control', 'public, max-age=31536000'));?>">
	        </span>

	       
		</label>

	</p>



	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Allow File Upload Types', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>
	            <input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_accepted_filetypes" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_accepted_filetypes', ''));?>">
	           
	        </span>

	       
		</label>

	</p>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Compress Objects Automatically', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>

	            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_gzip" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_gzip'), 'on', true ); ?>>
	        </span>
	        <?php esc_html_e('Enable GZIP', 'carrot-bunnycdn-incoom-plugin');?>

		</label>

	</p>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Remove from server', 'carrot-bunnycdn-incoom-plugin');?></span>

	        <span>

	            <input class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_remove_from_server_checkbox" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_remove_from_server_checkbox'), 'on', true ); ?>>

	            <?php esc_html_e('Remove from the server', 'carrot-bunnycdn-incoom-plugin');?>
	        </span>

	        
		</label>

	</p>

	<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

		<label>

			<span type="hidden" class="incoom_carrot_bunnycdn_title">  <?php esc_html_e('Object Versioning', 'carrot-bunnycdn-incoom-plugin');?></span>

			<span>
				<input class="incoom_carrot_bunnycdn_input_text" type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_object_versioning" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_object_versioning'), 'on', true ); ?>>
				

				</span>

			<span class="incoom_carrot_bunnycdn_description_checkbox">
				
			</span>

		</label>

	</p>