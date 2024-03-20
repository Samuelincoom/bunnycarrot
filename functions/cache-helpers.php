<?php
function incoom_carrot_bunnycdn_incoom_plugin_get_cache_item($key){
    return wp_cache_get($key);
}

function incoom_carrot_bunnycdn_incoom_plugin_set_cache_item($key, $data){
    wp_cache_set($key, $data, '', carrot_bunnycdn_incoom_plugin_CACHE_TIMEOUT_ATTACHED_FILE);
    return $data;
}

function incoom_carrot_bunnycdn_incoom_plugin_delete_cache_item($key){
    wp_cache_delete($key);
    return null;
}