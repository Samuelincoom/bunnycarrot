<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      2.0.32
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/admin/partials
 */
$action_scan = get_option('incoom_carrot_bunnycdn_incoom_plugin_action');
$media_count = carrot_bunnycdn_incoom_plugin_count_offloaded();
$offloaded = !empty($media_count['offloaded']) ? $media_count['offloaded'] : 0;

if($action_scan === 'remove_files_from_server'){
    $media_count = carrot_bunnycdn_incoom_plugin_count_local_removed();
}

if($action_scan === 'download_files_from_bucket'){
    $media_count = carrot_bunnycdn_incoom_plugin_count_download_files_from_cloud();
}

if($action_scan === 'remove_files_from_bucket'){
    $media_count = carrot_bunnycdn_incoom_plugin_count_remove_files_from_cloud();
}

$percentOffload = $media_count['percent'];
$count = $media_count['count'];

$removedLocal = carrot_bunnycdn_incoom_plugin_count_local_removed()['local_removed'];
?>	

<div class="notice-info notice">
	<p><a class="button-primary" target="_blank" href="<?php echo esc_url('//incoomsamuel@gmail.com/');?>"><?php esc_html_e('mail me lets talk!', 'carrot-bunnycdn-incoom-plugin');?></a></p>
</div>
<div class="wrap oneclick-wrap" id="carrot-bunnycdn-incoom-plugin-wrap">
    <div class="incoom_carrot_bunnycdn_loading"></div>
	<h1><?php esc_html_e( 'Sync Media - carrot-bunnycdn-incoom-plugin', 'carrot-bunnycdn-incoom-plugin' );?></h1>
    <?php $status = get_option('incoom_carrot_bunnycdn_incoom_plugin_connection_success', 0);?>
	<?php if($status == 1):?>
        <div id="post-body" class="metabox-holder columns-2">
            <div class="card">
                <?php if(empty($action_scan)):?>
                    <div id="smartwizard">
                        <ul class="nav">
                            <li class="nav-item">
                                <a class="nav-link" href="#step-1">
                                    <div class="num">1</div>
                                    <?php esc_html_e('Select sync option', 'carrot-bunnycdn-incoom-plugin');?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#step-2">
                                    <span class="num">2</span>
                                    <?php esc_html_e('Confirm', 'carrot-bunnycdn-incoom-plugin');?>
                                </a>
                            </li>
                        </ul>
                    
                        <div class="tab-content">
                            <div id="step-1" class="tab-pane" role="tabpanel" aria-labelledby="step-1">
                                <form id="form-1" class="row row-cols-1 ms-5 me-5 needs-validation" novalidate>
                                    <select id="media_action" name="media_action" class="form-control" required>
                                        <option value=""><?php esc_html_e('-- Select sync option --', 'carrot-bunnycdn-incoom-plugin');?></option>
                                        <option value="copy_files_to_bucket"><?php echo carrot_bunnycdn_incoom_plugin_get_sync_action_title('copy_files_to_bucket');?></option>
                                        
                                        <?php if($offloaded > 0):?>
                                            <option value="remove_files_from_server"><?php echo carrot_bunnycdn_incoom_plugin_get_sync_action_title('remove_files_from_server');?></option>
                                            <option value="remove_files_from_bucket"><?php echo carrot_bunnycdn_incoom_plugin_get_sync_action_title('remove_files_from_bucket');?></option>
                                        <?php endif;?>
                                        
                                        <option value="download_files_from_bucket"><?php echo carrot_bunnycdn_incoom_plugin_get_sync_action_title('download_files_from_bucket');?></option>
                                    </select>
                                    <div class="invalid-feedback">
                                        <?php esc_html_e('Please select an action.', 'carrot-bunnycdn-incoom-plugin');?>
                                    </div>
                                </form>
                            </div>
                            <div id="step-2" class="tab-pane" role="tabpanel" aria-labelledby="step-2">
                                <form id="form-2" class="row row-cols-1 ms-5 me-5 needs-validation" novalidate>
                                    <p class="incoom_carrot_bunnycdn_admin_parent_wrap">
                                        <input id="incoom_carrot_bunnycdn_incoom_plugin_send_email_task" class="incoom_carrot_bunnycdn_input_text" type="checkbox" name="incoom_carrot_bunnycdn_incoom_plugin_send_email_task" <?php checked( get_option('incoom_carrot_bunnycdn_incoom_plugin_send_email_task', 'on'), 'on', true ); ?>>
                                        <label for="incoom_carrot_bunnycdn_incoom_plugin_send_email_task"><?php esc_html_e('Send me an email when task is completed.', 'carrot-bunnycdn-incoom-plugin');?></label>
                                    </p>
                                </form>
                            </div>
                        </div>
                    
                        <!-- Include optional progressbar HTML -->
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php else:?>  
                    <h2 class="title"><?php echo carrot_bunnycdn_incoom_plugin_get_sync_action_title($action_scan);?></h2>
                    <div class="copy-process step-1">
                        <div class="iziToastloading spin_loading"></div>
                        <div class="progress-bar">
                            <span id="percent" style="line-height: 15px;height: 15px;"><?php echo esc_html($percentOffload);?>%</span>
                            <span class="bar" style="height: 15px;"><span style="width: <?php echo esc_attr($percentOffload);?>%;" class="progress"></span></span>
                        </div>
                        <div class="current-sync-process progress_count"><?php echo esc_html($count);?></div>
                    </div>
                    <button type="button" class="button-secondary" id="incoom_carrot_bunnycdn_settings_copy_files_to_bucket_kill"><?php esc_html_e('Kill process.', 'carrot-bunnycdn-incoom-plugin');?></button>
                <?php endif;?>
            </div>
        </div>
    <?php endif;?>
</div>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
