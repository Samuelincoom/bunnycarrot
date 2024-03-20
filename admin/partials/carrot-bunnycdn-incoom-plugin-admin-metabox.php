<?php 
global $post;
$provider_object = carrot_bunnycdn_incoom_plugin_get_real_provider( $post->ID );
$provider_name = empty( $provider_object['provider'] ) ? '' : $provider_object['provider'];
$links = carrot_bunnycdn_incoom_plugin_row_actions_extra(array(), $post->ID);
?>
<div class="s3-details">
    <?php if ( empty($provider_object['key']) ) : ?>
    <div class="misc-pub-section">
        <em
            class="not-copied"><?php esc_html_e( 'This item has not been offloaded yet.', 'carrot-bunnycdn-incoom-plugin' ); ?></em>
    </div>
    <?php else : ?>
    <div class="misc-pub-section">
        <div class="s3-key"><?php esc_html_e( 'Storage Provider', 'carrot-bunnycdn-incoom-plugin' ); ?>:</div>
        <input type="text" id="carrot-provider" class="widefat" readonly="readonly"
            value="<?php echo esc_attr(carrot_bunnycdn_incoom_plugin_get_provider_service_name($provider_name)); ?>">
    </div>
    <div class="misc-pub-section">
        <div class="s3-key"><?php esc_html_e( 'Bucket', 'carrot-bunnycdn-incoom-plugin' ); ?>:</div>
        <input type="text" id="carrot-bucket" class="widefat" readonly="readonly"
            value="<?php echo esc_attr($provider_object['bucket']); ?>">
    </div>
    <?php if ( isset( $provider_object['region'] ) && $provider_object['region'] ) : ?>
    <div class="misc-pub-section">
        <div class="s3-key"><?php esc_html_e( 'Region', 'carrot-bunnycdn-incoom-plugin' ); ?>:</div>
        <input type="text" id="carrot-region" class="widefat" readonly="readonly"
            value="<?php echo esc_attr($provider_object['region']); ?>">
    </div>
    <?php endif; ?>
    <div class="misc-pub-section">
        <div class="s3-key"><?php esc_html_e( 'Path', 'carrot-bunnycdn-incoom-plugin' ); ?>:</div>
        <input type="text" id="carrot-key" class="widefat" readonly="readonly"
            value="<?php echo esc_attr($provider_object['key']); ?>">
    </div>

    <div class="misc-pub-section">
        <?php echo join(' | ', $links);?>
    </div>

    <?php endif; ?>
    <div class="clear"></div>
</div>