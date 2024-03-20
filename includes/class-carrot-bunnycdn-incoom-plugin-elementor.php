<?php

use Elementor\Core\Files\CSS\Post;
use Elementor\Element_Base;
use Elementor\Plugin;

/**
 * Support Elementor
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      2.0.32
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

class carrot_bunnycdn_incoom_plugin_Elementor {
    
    /**
	 * Keep track update_metadata recursion level
	 *
	 * @var int
	 */
	private static $recursion_level = 0;
	private static $filter_local_to_cloud;
	private static $filter_cloud_to_local;

    public static function init(){
		global $carrot_filter_cloud_to_local, $carrot_filter_local_to_cloud;
		
		static::$filter_local_to_cloud = $carrot_filter_local_to_cloud;
		static::$filter_cloud_to_local = $carrot_filter_cloud_to_local;

        if ( defined( 'ELEMENTOR_VERSION' ) ) {
            add_filter( 'elementor/editor/localize_settings', array( __CLASS__, 'localize_settings' ) );
		    add_action( 'elementor/frontend/before_render', array( __CLASS__, 'frontend_before_render' ) );
            add_action( 'wp_print_styles', array( __CLASS__, 'wp_print_styles' ) );
            add_filter( 'update_post_metadata', array( __CLASS__, 'update_post_metadata' ), 10, 5 );

            if ( isset( $_REQUEST['action'] ) && 'elementor_ajax' === $_REQUEST['action'] ) {
                add_filter( 'widget_update_callback', array( __CLASS__, 'widget_update_callback' ), 10, 2 );
            }

            add_action( 'carrot_bunnycdn_incoom_plugin_action_scheduler_completed', array( __CLASS__, 'clear_elementor_css_cache' ) );
        }
    }

	/**
	 * Some widgets, specifically any standard WordPress widgets, make ajax requests back to the server
	 * before the edited section gets rendered in the Elementor editor. When they do, Elementor picks up
	 * properties directly from the saved post meta.
	 *
	 * @param array $instance
	 * @param array $new_instance
	 *
	 * @handles widget_update_callback
	 *
	 * @return mixed
	 */
	public static function widget_update_callback( $instance, $new_instance ) {
        $filter_local_to_S3 = static::$filter_local_to_cloud;
		return json_decode(
			$filter_local_to_S3->filter_post( json_encode( $instance, JSON_UNESCAPED_SLASHES ) ),
			true
		);
	}

	/**
	 * Clears the Elementor cache
	 *
	 * @handles Multiple action
	 */
	public static function clear_elementor_css_cache() {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			Plugin::instance()->files_manager->clear_cache();
		}
	}

	/**
	 * Rewrite media library URLs from local to remote when settings are read from
	 * database.
	 *
	 * @param object $config
	 *
	 * @return object
	 *
	 * @handles elementor/editor/localize_settings
	 */
	public static function localize_settings( $config ) {
		if ( ! is_array( $config ) || ! isset( $config['initial_document'] ) ) {
			return $config;
		}

		if ( ! is_array( $config['initial_document'] ) || ! isset( $config['initial_document']['elements'] ) ) {
			return $config;
		}

        $filter_local_to_S3 = static::$filter_local_to_cloud;

		$filtered = json_decode(
			$filter_local_to_S3->filter_post( json_encode( $config['initial_document']['elements'], JSON_UNESCAPED_SLASHES ) ),
			true
		);

		// Avoid replacing content if the filtering failed
		if ( false !== $filtered ) {
			$config['initial_document']['elements'] = $filtered;
		}

		return $config;
	}

	/**
	 * Replace local URLs in settings that Elementor renders in HTML for some attributes, i.e json structs for
	 * the section background slideshow
	 *
	 * @param Element_Base $element
	 *
	 * @handles elementor/frontend/before_render
	 */
	public static function frontend_before_render( $element ) {
        $filter_local_to_S3 = static::$filter_local_to_cloud;
		$element->set_settings(
			json_decode(
				$filter_local_to_S3->filter_post( json_encode( $element->get_settings(), JSON_UNESCAPED_SLASHES ) ),
				true
			)
		);
	}

    /**
	 * Rewrite URLs in Elementor frontend inline CSS before it's rendered/printed by WordPress
	 *
	 * @implements wp_print_styles
	 */
	public static function wp_print_styles() {
		$wp_styles = wp_styles();
		if ( empty( $wp_styles->registered['elementor-frontend']->extra['after'] ) ) {
			return;
		}

        $filter_local_to_S3 = static::$filter_local_to_cloud;

		foreach ( $wp_styles->registered['elementor-frontend']->extra['after'] as &$extra_css ) {
			$filtered_css = $filter_local_to_S3->filter_post( $extra_css );
			if ( ! empty( $filtered_css ) ) {
				$extra_css = $filtered_css;
			}
		}
	}

	/**
	 * Rewrites local URLs in generated CSS files
	 *
	 * @param int   $object_id
	 * @param array $meta_value
	 */
	private static function rewrite_css( $object_id, $meta_value ) {
		if ( 'file' === $meta_value['status'] ) {
			$elementor_css = new Post( $object_id );
			$file          = Post::get_base_uploads_dir() . Post::DEFAULT_FILES_DIR . $elementor_css->get_file_name();

			if ( file_exists( $file ) ) {
				$old_content = file_get_contents( $file );
				if ( ! empty( $old_content ) ) {
                    $filter_local_to_S3 = static::$filter_local_to_cloud;
					file_put_contents(
						$file,
						$filter_local_to_S3->filter_post( $old_content )
					);
				}
			}
		}
	}

    /**
	 * Handle Elementor's call to update_metadata() for _elementor_data when saving
	 * a post or page. Rewrites remote URLs to local.
	 *
	 * @param bool   $check
	 * @param int    $object_id
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 * @param mixed  $prev_value
	 *
	 * @handles update_post_metadata
	 *
	 * @return bool
	 */
	public static function update_post_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( '_elementor_css' === $meta_key ) {
			static::rewrite_css( $object_id, $meta_value );

			return $check;
		}

		if ( '_elementor_data' !== $meta_key ) {
			return $check;
		}

		// We're calling update_metadata recursively and need to make sure
		// we never nest deeper than one level.
		if ( 0 === static::$recursion_level ) {
			static::$recursion_level++;

            $filter_S3_to_local = static::$filter_cloud_to_local;
			// We get here from an update_metadata() call that has already done some string sanitizing
			// including wp_unslash(), but the original json from Elementor still needs slashes
			// removed for our filters to work.
			// Note: wp_unslash can't be used because it also unescapes any embedded HTML.
			$json       = str_replace( '\/', '/', $meta_value );
			$json       = $filter_S3_to_local->filter_post( $json );
			$meta_value = wp_slash( str_replace( '/', '\/', $json ) );
			update_metadata( 'post', $object_id, '_elementor_data', $meta_value, $prev_value );

			// Reset recursion level and let update_metadata we already handled saving the meta
			static::$recursion_level = 0;

			return true;
		}

		return $check;
	}
}