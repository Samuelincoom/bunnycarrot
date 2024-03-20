<?php 
$allow_methods = get_option('incoom_carrot_bunnycdn_incoom_plugin_cors_allow_methods', array('GET', 'HEAD'));
?>
<input type="hidden" name="incoom_carrot_bunnycdn_incoom_plugin_cors_tab" value="1">
<p class="incoom_carrot_bunnycdn_admin_parent_wrap">
    <span><?php esc_html_e('Cross Origin Resource Sharing (CORS) is a mechanism for allowing interactions between resources from different origins, something that is normally prohibited in order to prevent malicious behavior.', 'carrot-bunnycdn-incoom-plugin');?></span>
</p>


<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

    <label>

        <span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Origins', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_cors_origin" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_cors_origin', '*'));?>">
            <span><?php esc_html_e('EX: assets.example.com,cdn.example.com or *', 'carrot-bunnycdn-incoom-plugin');?></span>
        </span>
        <span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('The list of Origins eligible to receive CORS response headers, separated by commas. Note: "*" is permitted in the list of origins, and means "any Origin".', 'carrot-bunnycdn-incoom-plugin');?></span>

    </label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

    <label>

        <span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('HTTP methods', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <select class="incoom_carrot_bunnycdn_input_text" name="incoom_carrot_bunnycdn_incoom_plugin_cors_allow_methods[]" multiple="">
                <?php 
                foreach (carrot_bunnycdn_incoom_plugin_CORS_AllOWED_METHODS as $method) {
                    ?>
                    <option value="<?php echo esc_attr($method);?>" <?php if(in_array($method, $allow_methods)){echo 'selected="selected"';}?>><?php echo esc_html($method);?></option>
                    <?php
                }
                ?>
            </select>
        </span>

    </label>

</p>

<p class="incoom_carrot_bunnycdn_admin_parent_wrap">

    <label>

        <span class="incoom_carrot_bunnycdn_title"><?php esc_html_e('Max Age Seconds', 'carrot-bunnycdn-incoom-plugin');?></span>

        <span>

            <input class="incoom_carrot_bunnycdn_input_text" type="text" name="incoom_carrot_bunnycdn_incoom_plugin_cors_maxageseconds" value="<?php echo esc_attr(get_option('incoom_carrot_bunnycdn_incoom_plugin_cors_maxageseconds', '3600'));?>">
            
        </span>
        <span class="incoom_carrot_bunnycdn_description_checkbox"><?php esc_html_e('For preflighted requests, allow the browser to make requests for 3600 seconds (1 hour) before it must repeat the preflight request.', 'carrot-bunnycdn-incoom-plugin');?></span>

    </label>

</p>