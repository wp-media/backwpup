<?php
//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

global $wpdb;
/* @var wpdb $wpdb */

//only uninstall if no BackWPup Version active
if ( ! class_exists( 'BackWPup' ) ) {
	//remove roles
	remove_role( 'backwpup_admin' );
	remove_role( 'backwpup_helper' );
	remove_role( 'backwpup_check' );

	//remove capabilities to administrator role
	$role = get_role( 'administrator' );
	if ( is_object( $role )  && method_exists( $role, 'remove_cap' ) ) {
		$role->remove_cap( 'backwpup' );
		$role->remove_cap( 'backwpup_jobs' );
		$role->remove_cap( 'backwpup_jobs_edit' );
		$role->remove_cap( 'backwpup_jobs_start' );
		$role->remove_cap( 'backwpup_backups' );
		$role->remove_cap( 'backwpup_backups_download' );
		$role->remove_cap( 'backwpup_backups_delete' );
		$role->remove_cap( 'backwpup_logs' );
		$role->remove_cap( 'backwpup_logs_delete' );
		$role->remove_cap( 'backwpup_settings' );
	}

	//delete plugin options
	if ( is_multisite() )
		$wpdb->query( "DELETE FROM " . $wpdb->sitemeta . " WHERE meta_key LIKE '%backwpup_%' " );
	else
		$wpdb->query( "DELETE FROM " . $wpdb->options . " WHERE option_name LIKE '%backwpup_%' " );
}
