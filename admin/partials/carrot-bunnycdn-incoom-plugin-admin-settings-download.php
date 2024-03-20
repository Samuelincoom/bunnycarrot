
<input type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_download_tab" value="1">
<style>#incoom_carrot_bunnycdn_settings_submit{display:none;}</style>
<div id="change_links_download_header">
    <h3><?php esc_html_e('Change all links download from server to cloud.', 'carrot-bunnycdn-incoom-plugin');?></h3>
    <input type="button" id="incoom_carrot_bunnycdn_change_links_download" class="button-primary" value="<?php esc_html_e('Change all links now', 'carrot-bunnycdn-incoom-plugin');?>">
</div>
<div id="change_links_download_content" class="hidden">
    <h3><?php esc_html_e('Please sit tight while we change your links. Do not refresh the page.', 'carrot-bunnycdn-incoom-plugin');?></h3>
    <p><img src="<?php echo esc_url( carrot_bunnycdn_incoom_plugin_PLUGIN_URI . 'admin/images/importing.svg' ); ?>" alt="<?php esc_attr_e( 'Importing animation', 'carrot-bunnycdn-incoom-plugin' ); ?>"></p>
</div>
<div id="change_links_download_footer" class="hidden">
    <h3><?php esc_html_e('Links change completed!', 'carrot-bunnycdn-incoom-plugin');?></h3>
    <p><?php esc_html_e( 'Congrats, your links was changed successfully.' , 'carrot-bunnycdn-incoom-plugin' ); ?></p>
</div>