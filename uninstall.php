<?php
//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

global $wpdb;
/* @var wpdb $wpdb */

//only uninstall if no BackWPup Version active
if ( ! class_exists( 'BackWPup' ) ) {

	//delete plugin options
	if ( is_multisite() )
		$wpdb->query( "DELETE FROM " . $wpdb->sitemeta . " WHERE meta_key LIKE '%backwpup_%' " );
	else
		$wpdb->query( "DELETE FROM " . $wpdb->options . " WHERE option_name LIKE '%backwpup_%' " );

	//delete Backwpup user roles 
	$backWPUpRoles = array(
		"backwpup_admin",
		"backwpup_check",
		"backwpup_helper"
	);
	
	foreach ( $backWPUpRoles as $backWPUpRole ) {
		if ( get_role( $backWPUpRole ) ) {
			remove_role( $backWPUpRole );
		}
	}

}
