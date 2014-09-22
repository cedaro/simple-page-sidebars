<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key='_sidebar_name'" );
delete_option( 'simple_page_sidebars_default_sidebar' );
delete_option( 'simple_page_sidebars_version' );
