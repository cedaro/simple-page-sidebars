<?php
/*
Plugin Name: Simple Page Sidebars
Version: 1.0.1
Plugin URI: http://wordpress.org/extend/plugins/simple-page-sidebars/
Description: Assign custom, widget-enabled sidebars to any page with ease.
Author: Blazer Six, Inc.
Author URI: http://www.blazersix.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

------------------------------------------------------------------------
Copyright 2012  Blazer Six, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


class Simple_Page_Sidebars {
	function __construct() {
		add_action( 'plugins_loaded', array( &$this, 'load_plugin' ) );
	}
	
	/**
	 * Setup the plugin
	 *
	 * @since 0.2
	 */
	function load_plugin() {
		load_plugin_textdomain( 'simple-page-sidebars', false, 'simple-page-sidebars/languages' );
		
		require_once( plugin_dir_path( __FILE__ ) . '/includes/widget-area.php' );
		
		if ( is_admin() ) {
			require_once( plugin_dir_path( __FILE__ ) . 'admin/admin.php' );
		}
		
		// lower priority registers sidebars below those typically added in themes
		add_action( 'widgets_init', array( &$this, 'register_sidebars' ), 20 );
		
		if ( ! is_admin() ) {
			add_filter( 'sidebars_widgets', array( &$this, 'replace_sidebar' ) );
		}
	}
	
	/**
	 * Add custom widget areas and automatically register page sidebars
	 *
	 * @since 0.2
	 */
	function register_sidebars() {
		$widget_areas = array();
		
		// add widget areas using this filter
		$widget_areas = apply_filters( 'simple_page_sidebars_widget_areas', $widget_areas );
		$widget_areas = apply_filters( 'simpsid_widget_areas', $widget_areas ); // deprecated
		
		// verify id's exist, otherwise create them
		// helps ensure widgets don't get mixed up if widget areas are added or removed
		if ( ! empty( $widget_areas ) && is_array( $widget_areas ) ) {
			foreach ( $widget_areas as $key => $area ) {
				if ( is_numeric( $key ) ) {
					$widget_areas[ 'widget-area-' . sanitize_key( $area['name'] ) ] = $area;
					unset( $widget_areas[ $key ] );
				}
			}
		}
		
		// override the default widget properties
		$widget_area_defaults = array(
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<h4 class="title">',
			'after_title' => '</h4>'
		);
		$widget_area_defaults = apply_filters( 'simple_page_sidebars_widget_defaults', $widget_area_defaults );
		$widget_area_defaults = apply_filters( 'simpsid_widget_area_defaults', $widget_area_defaults ); // deprecated
		
		// if any custom sidebars have been assigned to pages, merge them with already defined widget areas
		$sidebars = simple_page_sidebars_get_names();
		if ( ! empty( $sidebars ) ) {
			foreach ( $sidebars as $sidebar ) {
				$page_sidebars[ 'page-sidebar-' . sanitize_key( $sidebar ) ] = array(
					'name' => $sidebar,
					'description' => NULL
				);
			}
			
			ksort( $page_sidebars );
			$widget_areas = array_merge_recursive( $widget_areas, $page_sidebars );
		}
		
		if ( ! empty( $widget_areas ) && is_array( $widget_areas ) ) {
			// register the widget areas
			foreach ( $widget_areas as $key => $area ) {
				register_sidebar(array(
					'id' => $key,
					'name' => $area['name'],
					'description' => $area['description'],
					'before_widget' => ( ! isset( $area['before_widget'] ) ) ? $widget_area_defaults['before_widget'] : $area['before_widget'],
					'after_widget' => ( ! isset( $area['after_widget'] ) ) ? $widget_area_defaults['after_widget'] : $area['after_widget'],
					'before_title' => ( ! isset( $area['before_title'] ) ) ? $widget_area_defaults['before_title'] : $area['before_title'],
					'after_title' => ( ! isset( $area['after_title'] ) ) ? $widget_area_defaults['after_title'] : $area['after_title']
				));
			}
		}
	}
	
	/**
	 * Replaces the default sidebar with a custom defined page sidebar
	 *
	 * @since 0.2
	 */
	function replace_sidebar( $sidebars_widgets ) {
		global $post;
		
		if ( is_page() || ( is_home() && $posts_page = get_option( 'page_for_posts' ) ) ) {
			$post_id = ( ! empty( $posts_page ) ) ? $posts_page : $post->ID;
			
			$custom_sidebar = get_post_meta( $post_id, '_sidebar_name', true );
			$default_sidebar_id = get_option( 'simple_page_sidebars_default_sidebar' );
			
			if ( $custom_sidebar && $default_sidebar_id ) {
				$custom_sidebar_id = 'page-sidebar-' . sanitize_key( $custom_sidebar );
				
				// only replace the default sidebar if the custom sidebar has widgets
				if ( ! empty( $sidebars_widgets[ $custom_sidebar_id ] ) ) {
					$sidebars_widgets[ $default_sidebar_id ] = $sidebars_widgets[ $custom_sidebar_id ];
				}
			}
		}
		
		return $sidebars_widgets;
	}
}
$simple_page_sidebars = new Simple_Page_Sidebars();

/**
 * Get an array of custom sidebar names
 *
 * @since 0.2
 * @return array Custom sidebar names
 */
function simple_page_sidebars_get_names() {
	global $wpdb;
	
	$sql = "SELECT meta_value
		FROM $wpdb->posts p, $wpdb->postmeta pm
		WHERE p.post_type='page' AND p.post_status!='auto-draft' AND p.ID=pm.post_id
			AND pm.meta_key='_sidebar_name'
		GROUP BY pm.meta_value
		ORDER BY pm.meta_value ASC";
	
	$sidebars = array();
	$sidebar_names = $wpdb->get_results($sql);
	if ( ! empty( $sidebar_names ) ) {
		foreach ( $sidebar_names as $meta ) {
			$sidebars[] = $meta->meta_value;
		}
	}
	
	return $sidebars;
}

/**
 * Sidebar display template tag
 *
 * Call this function in the template where custom sidebars should be displayed.
 * If a custom sidebar hasn't been defined, the sidebar name passed as the parameter
 * will be served as a fallback.
 *
 * This is no longer the recommended usage. No code changes to the theme are
 * are required for the plugin to work.
 *
 * @since 0.2
 * @param string $default_sidebar
 */
function simple_page_sidebar( $default_sidebar ) {
	global $post, $wp_registered_sidebars;
	
	$sidebar_name = get_post_meta( $post->ID, '_sidebar_name', true );
	
	// last chance to override which sidebar is displayed
	$sidebar_name = apply_filters( 'simple_page_sidebars_last_call', $sidebar_name );
	$sidebar_name = apply_filters( 'simpsid_sidebar_name', $sidebar_name ); // deprecated
	
	if ( is_page() && ! empty( $sidebar_name ) ) {
		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( count( $sidebars_widgets ) ) {
			foreach ( $wp_registered_sidebars as $id => $sidebar ) {
				if ( $sidebar['name'] == $sidebar_name ) {
					if ( count( $sidebars_widgets[$id] ) ) {
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
 * Deprecated
 */
if ( ! function_exists( 'simple_sidebar' ) ) :
function simple_sidebar( $default_sidebar ) {
	_deprecated_function( __FUNCTION__, '0.1.1', 'simple_page_sidebar()' );
	
	simple_page_sidebar( $default_sidebar );
}
endif;
?>