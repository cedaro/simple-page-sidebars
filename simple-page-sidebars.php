<?php
/**
 * Simple Page Sidebars
 *
 * @package SimplePageSidebars
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Simple Page Sidebars
 * Plugin URI:  https://wordpress.org/plugins/simple-page-sidebars/
 * Description: Assign custom, widget-enabled sidebars to any page with ease.
 * Version:     1.2.1
 * Author:      Cedaro
 * Author URI:  https://www.cedaro.com/?utm_source=wordpress-plugin&utm_medium=link&utm_content=simple-page-sidebars-author-uri&utm_campaign=plugins
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-page-sidebars
 */

/**
 * Set a constant path to the plugin's root directory.
 */
if ( ! defined( 'SIMPLE_PAGE_SIDEBARS_DIR' ) )
	define( 'SIMPLE_PAGE_SIDEBARS_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Set a constant URL to the plugin's root directory.
 */
if ( ! defined( 'SIMPLE_PAGE_SIDEBARS_URL' ) )
	define( 'SIMPLE_PAGE_SIDEBARS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 *
 * @since 0.2.0
 */
class Simple_Page_Sidebars {
	/**
	 * Setup the plugin.
	 *
	 * @since 0.2.0
	 */
	public static function load() {
		self::load_textdomain();

		require_once( plugin_dir_path( __FILE__ ) . 'includes/widget-area.php' );

		// Load the admin functionality.
		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'upgrade' ) );

			require_once( plugin_dir_path( __FILE__ ) . 'admin/admin.php' );
			Simple_Page_Sidebars_Admin::load();
		}

		// Lower priority registers sidebars below those typically added by themes.
		add_action( 'widgets_init', array( __CLASS__, 'register_sidebars' ), 20 );
		add_action( 'widgets_init', array( __CLASS__, 'register_widgets' ) );

		if ( ! is_admin() ) {
			add_filter( 'sidebars_widgets', array( __CLASS__, 'replace_sidebar' ) );
		}
	}

	/**
	 * Plugin localization support.
	 *
	 * @since 1.1.4
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'simple-page-sidebars' );
	}

	/**
	 * Register the Area widget.
	 *
	 * @since 1.1.0
	 */
	public static function register_widgets() {
		register_widget( 'Simple_Page_Sidebars_Widget_Area' );
	}

	/**
	 * Add custom widget areas and automatically register page sidebars.
	 *
	 * @todo Try to insert a link into the description of custom sidebars so
	 *       they can be edited. It'd be useful for when the Sidebar column is
	 *       disabled, since there isn't any other way to access the Edit
	 *       Sidebar screen.
	 *
	 * @since 0.2.0
	 */
	public static function register_sidebars() {
		$widget_areas = array();

		// Add widget areas using this filter.
		$widget_areas = apply_filters( 'simple_page_sidebars_widget_areas', $widget_areas );

		// Verify id's exist, otherwise create them.
		// Help ensure widgets don't get mixed up if widget areas are added or removed.
		if ( ! empty( $widget_areas ) && is_array( $widget_areas ) ) {
			foreach ( $widget_areas as $key => $area ) {
				if ( is_numeric( $key ) ) {
					$widget_areas[ 'widget-area-' . sanitize_key( $area['name'] ) ] = $area;
					unset( $widget_areas[ $key ] );
				}
			}
		}

		// Override the default widget properties.
		$widget_area_defaults = array(
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="title">',
			'after_title'   => '</h4>'
		);

		$widget_area_defaults = apply_filters( 'simple_page_sidebars_widget_defaults', $widget_area_defaults );

		// If any custom sidebars have been assigned to pages, merge them with already defined widget areas.
		$sidebars = simple_page_sidebars_get_names();
		if ( ! empty( $sidebars ) ) {
			foreach ( $sidebars as $sidebar ) {
				$page_sidebars[ 'page-sidebar-' . sanitize_key( $sidebar ) ] = array(
					'name'        => $sidebar,
					'description' => ''
				);
			}

			ksort( $page_sidebars );
			$widget_areas = array_merge_recursive( $widget_areas, $page_sidebars );
		}

		if ( ! empty( $widget_areas ) && is_array( $widget_areas ) ) {
			// Register the widget areas.
			foreach ( $widget_areas as $key => $area ) {
				register_sidebar(array(
					'id'            => $key,
					'name'          => $area['name'],
					'description'   => $area['description'],
					'before_widget' => ( isset( $area['before_widget'] ) ) ? $area['before_widget'] : $widget_area_defaults['before_widget'],
					'after_widget'  => ( isset( $area['after_widget'] ) )  ? $area['after_widget']  : $widget_area_defaults['after_widget'],
					'before_title'  => ( isset( $area['before_title'] ) )  ? $area['before_title']  : $widget_area_defaults['before_title'],
					'after_title'   => ( isset( $area['after_title'] ) )   ? $area['after_title']   : $widget_area_defaults['after_title']
				));
			}
		}
	}

	/**
	 * Replaces the default sidebar with a custom defined page sidebar.
	 *
	 * @since 0.2.0
	 * @param array $sidebars_widgets
	 */
	public static function replace_sidebar( $sidebars_widgets ) {
		global $post;

		$supports = ( isset( $post->post_type ) && post_type_supports( $post->post_type, 'simple-page-sidebars' ) ) ? true : false;

		if ( is_page() || $supports || ( is_home() && $posts_page = get_option( 'page_for_posts' ) ) ) {
			$post_id = ( ! empty( $posts_page ) ) ? $posts_page : $post->ID;

			$custom_sidebar = get_post_meta( $post_id, '_sidebar_name', true );
			$default_sidebar_id = get_option( 'simple_page_sidebars_default_sidebar' );

			if ( $custom_sidebar && $default_sidebar_id ) {
				$custom_sidebar_id = 'page-sidebar-' . sanitize_key( $custom_sidebar );

				// Only replace the default sidebar if the custom sidebar has widgets.
				if ( ! empty( $sidebars_widgets[ $custom_sidebar_id ] ) ) {
					$sidebars_widgets[ $default_sidebar_id ] = $sidebars_widgets[ $custom_sidebar_id ];
				}
			}
		}

		return $sidebars_widgets;
	}

	/**
	 * Save version information for future upgrades.
	 *
	 * @since 1.1.0
	 */
	public static function upgrade() {
		$saved_version = get_option( 'simple_page_sidebars_version' );

		// If the plugin version setting isn't set or if it's less than or equal to 1.1.0, update the saved version.
		if ( ! $saved_version || version_compare( $saved_version, '1.1.0', '<=' ) ) {
			$plugin_data = get_plugin_data( __FILE__ );

			// Update saved version number.
			update_option( 'simple_page_sidebars_version', $plugin_data['Version'] );
		}
	}
}
add_action( 'plugins_loaded', array( 'Simple_Page_Sidebars', 'load' ) );

/**
 * Get an array of custom sidebar names.
 *
 * @since 0.2.0
 * @return array Custom sidebar names.
 */
function simple_page_sidebars_get_names() {
	global $wpdb;

	$sidebar_names = $wpdb->get_results( "SELECT DISTINCT meta_value
		FROM $wpdb->posts p, $wpdb->postmeta pm
		WHERE p.post_status!='auto-draft' AND p.ID=pm.post_id AND pm.meta_key='_sidebar_name'
		ORDER BY pm.meta_value ASC" );

	$sidebars = array();
	if ( ! empty( $sidebar_names ) ) {
		foreach ( $sidebar_names as $meta ) {
			$sidebars[] = $meta->meta_value;
		}
	}

	return $sidebars;
}

/**
 * Sidebar display template tag.
 *
 * This is no longer the recommended usage. No code changes to the theme are
 * are required for the plugin to work.
 *
 * Call this function in the template where custom sidebars should be
 * displayed. If a custom sidebar hasn't been defined, the sidebar name passed
 * as the parameter will be served as a fallback.
 *
 * @since 0.2.0
 * @param string $default_sidebar
 */
function simple_page_sidebar( $default_sidebar ) {
	global $post, $wp_registered_sidebars;

	$sidebar_name = get_post_meta( $post->ID, '_sidebar_name', true );

	// Last chance to override which sidebar is displayed.
	$sidebar_name = apply_filters( 'simple_page_sidebars_last_call', $sidebar_name );

	if ( is_page() && ! empty( $sidebar_name ) ) {
		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( count( $sidebars_widgets ) ) {
			foreach ( $wp_registered_sidebars as $id => $sidebar ) {
				if ( $sidebar['name'] == $sidebar_name ) {
					if ( count( $sidebars_widgets[ $id ] ) ) {
						dynamic_sidebar( $sidebar_name );
					} elseif ( ! empty( $default_sidebar ) ) {
						dynamic_sidebar( $default_sidebar );
					}
				}
			}
		}
	} elseif ( ! empty( $default_sidebar ) ) {
		dynamic_sidebar( $default_sidebar );
	}
}

/**
 * Deprecated.
 */
if ( ! function_exists( 'simple_sidebar' ) ) :
function simple_sidebar( $default_sidebar ) {
	_deprecated_function( __FUNCTION__, '0.1.1', 'simple_page_sidebar()' );

	simple_page_sidebar( $default_sidebar );
}
endif;
