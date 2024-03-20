<?php
if (!defined('ABSPATH')) {exit;}
/**
 * Generator the WordPress loop.
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.0
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 * @author     incoom <incoomsamuel@gmail.com>
 */

/**
 * Simplifies the WordPress loop.
 *
 * @param WP_Query|WP_Post[] $iterable
 *
 * @return Generator
 */
class carrot_bunnycdn_incoom_plugin_Lazy_Query_Loop {

	public static function generator($iterable = null){
		if ( null === $iterable ) {
			$iterable = $GLOBALS['wp_query'];
		}
	
		$posts = $iterable;
		if ( is_object( $iterable ) && property_exists( $iterable, 'posts' ) ) {
			$posts = $iterable->posts;
		}
	
		if ( ! is_array( $posts ) ) {
			throw new \InvalidArgumentException( sprintf( esc_html__('Expected an array, received %s instead', 'carrot-bunnycdn-incoom-plugin'), gettype( $posts ) ) );
		}
	
		global $post;
	
		// Save the global post object so we can restore it later
		$save_post = $post;
	
		try {
	
			foreach ( $posts as $post ) {
				setup_postdata( $post );
				yield $post;
			}
	
		} finally {
			wp_reset_postdata();
	
			// Restore the global post object
			$post = $save_post;
		}
	}
}