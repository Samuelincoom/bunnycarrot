<?php
/**
 * Admin View: Page - Status
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : 'status';
$show_offloaded = ! empty( $_REQUEST['show_offloaded'] ) ? sanitize_title( $_REQUEST['show_offloaded'] ) : 0;
$tabs = [
	'status' => esc_html__( 'System status', 'carrot-bunnycdn-incoom-plugin' )
];
$tabs = apply_filters( 'carrot_admin_status_tabs', $tabs );
?>
<div class="wrap woocommerce">
	<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php
		foreach ( $tabs as $name => $label ) {
			echo '<a href="' . admin_url( 'admin.php?page=carrot_bunnycdn_incoom_plugin_scheduled_actions&tab=' . $name ) . '" class="nav-tab ';
			if ( $current_tab == $name ) {
				echo 'nav-tab-active';
			}
			echo '">' . $label . '</a>';
		}
		?>
	</nav>
	<h1 class="screen-reader-text"><?php echo esc_html( $tabs[ $current_tab ] ); ?></h1>
	<?php 
		switch($current_tab){
			case "status":
				?>
				<table class="wc_status_table widefat" cellspacing="0" id="status">
					<thead>
						<tr>
							<th colspan="3" data-export-label="WordPress Environment"><h2><?php esc_html_e( 'WordPress environment', 'carrot-bunnycdn-incoom-plugin' ); ?></h2></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td data-export-label="WP Version"><?php esc_html_e( 'PHP version', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
							<td><?php echo esc_html(phpversion());?></td>
							<td class="help"><?php echo ( esc_html__( 'The version of PHP', 'carrot-bunnycdn-incoom-plugin' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
						</tr>
						<tr>
							<td data-export-label="WP Version"><?php esc_html_e( 'WordPress version', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
							<td><?php echo esc_html(get_bloginfo( 'version' ));?></td>
							<td class="help"><?php echo ( esc_html__( 'The version of WordPress installed on your site.', 'carrot-bunnycdn-incoom-plugin' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
						</tr>
						<tr>
							<td data-export-label="WP Version"><?php esc_html_e( 'WordPress multisite', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
							<td>
								<?php if ( is_multisite() ) : ?>
									<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
								<?php else : ?>
									<mark class="red">&ndash;</mark>
								<?php endif; ?>
							</td>
							<td class="help"><?php echo ( esc_html__( 'Whether or not you have WordPress Multisite enabled.', 'carrot-bunnycdn-incoom-plugin' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
						</tr>
						<tr>
							<td data-export-label="WP Debug Mode"><?php esc_html_e( 'WordPress debug mode', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
							<td>
								<?php if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) : ?>
									<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
								<?php else : ?>
									<mark class="red">&ndash;</mark>
								<?php endif; ?>
							</td>
							<td class="help"><?php echo ( esc_html__( 'Displays whether or not WordPress is in Debug Mode.', 'carrot-bunnycdn-incoom-plugin' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
						</tr>
						<tr>
							<td data-export-label="WP Cron"><?php esc_html_e( 'WordPress cron', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
							<td>
								<?php if ( !( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) : ?>
									<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
								<?php else : ?>
									<mark class="red">&ndash;</mark>
								<?php endif; ?>
							</td>
							<td class="help"><?php echo ( esc_html__( 'Displays whether or not WP Cron Jobs are enabled.', 'carrot-bunnycdn-incoom-plugin' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
						</tr>
						<tr>
							<td data-export-label="WP Version"><?php esc_html_e( 'carrot-bunnycdn-incoom-plugin version', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
							<td><?php echo esc_html(carrot_bunnycdn_incoom_plugin_VERSION);?></td>
						</tr>
						
						<?php if ( !empty($show_offloaded) ) : ?>
							<tr>
								<td data-export-label="Offloaded"><?php esc_html_e( 'Offloaded files', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
								<td>
									<?php 
									$media_count = carrot_bunnycdn_incoom_plugin_count_offloaded();
									echo esc_html($media_count['count']);
									?>
								</td>
							</tr>

							<?php
							$blog_id = get_current_blog_id();
							$source_type_classes = carrot_bunnycdn_incoom_plugin_get_source_type_classes();
							foreach($source_type_classes as $source_type => $class){
								$count = $class::verify_missing_source_ids(0, true);
								?>
								<tr>
									<td data-export-label="Offloaded"><?php echo esc_html($source_type); ?>:</td>
									<td><?php echo esc_html($count); ?></td>
								</tr>
								<?php
							}
							?>
						<?php endif; ?>

						<tr>
							<td data-export-label="WP Cron"><?php esc_html_e( 'Reset scheduled actions', 'carrot-bunnycdn-incoom-plugin' ); ?>:</td>
							<td>
								<form method="post">
									<input type="submit" class="button-primary" value="<?php esc_html_e('Reset', 'carrot-bunnycdn-incoom-plugin');?>">
									<input type="hidden" id="incoom_carrot_bunnycdn_reset_nonce" name="incoom_carrot_bunnycdn_reset_nonce" value="<?php echo esc_attr(wp_create_nonce('incoom_carrot_bunnycdn_reset_nonce'));?>">
								</form>
							</td>
						</tr>

					</tbody>
				</table>
				<?php
				break;
			default:
				if ( array_key_exists( $current_tab, $tabs ) && has_action( 'carrot_admin_status_content_' . $current_tab ) ) {
					do_action( 'carrot_admin_status_content_' . $current_tab );
				}
				break;
		}
	?>
</div>
