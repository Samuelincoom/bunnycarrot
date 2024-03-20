
<style>
    #incoom_carrot_bunnycdn_settings_submit{
        display: none;
    }
</style>
<p class="incoom_carrot_bunnycdn_admin_parent_wrap wom_import">
	<label>
		<span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Import settings', 'carrot-bunnycdn-incoom-plugin');?></span>
		<textarea class="incoom_carrot_bunnycdn_input_text" id="incoom_carrot_bunnycdn_import_content" name="incoom_carrot_bunnycdn_import_json"></textarea>
        <input disabled type="button" id="incoom_carrot_bunnycdn_import" class="button-primary" value="<?php esc_html_e('Import', 'carrot-bunnycdn-incoom-plugin');?>">
		<span class="incoom_carrot_bunnycdn_description"><?php esc_html_e('Enter exported json file content.', 'carrot-bunnycdn-incoom-plugin');?></span>
	</label>
</p>

<?php $status = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_success', 0);?>
<?php if($status == 1):?>
<p class="incoom_carrot_bunnycdn_admin_parent_wrap">
	<label>
        <span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Export settings', 'carrot-bunnycdn-incoom-plugin');?></span>
        <a id="downloadAnchorElem" style="display:none"></a>
		<input type="button" id="incoom_carrot_bunnycdn_export" class="button-primary" value="<?php esc_html_e('Export json file', 'carrot-bunnycdn-incoom-plugin');?>">
	</label>
</p>
<?php endif;?>