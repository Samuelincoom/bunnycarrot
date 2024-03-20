<?php 
$has_bk_option = false;
$sync_status = get_option('incoom_carrot_bunnycdn_incoom_plugin_synced_status', 0);
if($sync_status > 0){
	$has_bk_option = true;
}
if(!$has_bk_option):
?>
	<?php 
	$bucket_from = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_bucket_from');
	$bucket_to = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_bucket_to');
	$type = get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_type');
	$text_offload = esc_html__('Sync data now', 'carrot-bunnycdn-incoom-plugin');
	?>
	<div class="incoom_carrot_bunnycdn_loading"></div>
	<div class="sync-tab">
		<p class="sync-target incoom_carrot_bunnycdn_admin_parent_wrap">

			<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Choose a target', 'carrot-bunnycdn-incoom-plugin');?></span>

			<label>

				<input type="radio" name="incoom_carrot_bunnycdn_incoom_plugin_sync_target" <?php checked(get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_type'), 'cloud');?> value="cloud">

				<span class="incoom_carrot_bunnycdn_margin_right"><?php esc_html_e('Cloud to Cloud', 'carrot-bunnycdn-incoom-plugin');?></span>

			</label>

			<label>
				<input type="radio" name="incoom_carrot_bunnycdn_incoom_plugin_sync_target" <?php checked(get_option('incoom_carrot_bunnycdn_incoom_plugin_sync_type'), 'bucket');?> value="bucket">

				<span class="incoom_carrot_bunnycdn_margin_right"><?php esc_html_e('Bucket to Bucket', 'carrot-bunnycdn-incoom-plugin');?></span>
			</label>

		</p>
		<div class="sync-content">
			<?php if(!empty($type)):?>
				<?php 
				incoom_carrot_bunnycdn_incoom_plugin_load_template(
					'admin/partials/sync/provider.php', 
					array('type' => $type), 
					true
				);
				?>
			<?php endif;?>
		</div>
		<div id="incoom_carrot_bunnycdn_sync_data" class="sync-action <?php if(!empty($bucket_from) && !empty($bucket_to)){echo '';}else{echo 'hidden';}?>">
			<input type="button" class="button-primary" value="<?php echo esc_html($text_offload);?>">
		</div>
	</div>
<?php else:?>
	<div class="sync-tab">
		<p class="sync-target incoom_carrot_bunnycdn_admin_parent_wrap">
			<?php esc_html_e('carrot-bunnycdn-incoom-plugin Synchronized running.', 'carrot-bunnycdn-incoom-plugin');?>
		</p>
	</div>
<?php endif;?>