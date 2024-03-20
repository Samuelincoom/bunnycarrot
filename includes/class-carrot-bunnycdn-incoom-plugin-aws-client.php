<?php
if (!defined('ABSPATH')) {exit;}
/**
 * AWS S3 Client
 *
 * @since      1.0.0
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */

class carrot_bunnycdn_incoom_plugin_Aws_Client extends carrot_bunnycdn_incoom_plugin_Storage {
    
    public static function docs_link_credentials(){
        return 'https://aws.amazon.com/blogs/security/wheres-my-secret-access-key/';
    }

    public static function docs_link_create_bucket(){
        return 'https://docs.aws.amazon.com/en_us/quickstarts/latest/s3backup/step-1-create-bucket.html';
    }
}