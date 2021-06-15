<?php
//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

global $wpdb;
/* @var wpdb $wpdb */

//only uninstall if no BackWPup Version active
if (!class_exists('BackWPup')) {
    //do nothing if `keep plugin data` enabled
    if (!empty(get_site_option('backwpup_cfg_keepplugindata'))) {
        return;
    }

    //delete plugin options
    if (is_multisite()) {
        $wpdb->query("DELETE FROM " . $wpdb->sitemeta . " WHERE meta_key LIKE '%backwpup_%' ");
    } else {
        $wpdb->query("DELETE FROM " . $wpdb->options . " WHERE option_name LIKE '%backwpup_%' ");
    }

	//delete Backwpup user roles
	// Special handling for multisite when network-activated.
	if ( is_multisite() ) {
		$sites = get_sites( array(
			'fields' => 'ids',
		) );
		$current_site = get_current_blog_id();

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			backwpup_remove_roles();
		}

		switch_to_blog( $current_site );
	} else {
		backwpup_remove_roles();
	}

}

/**
 * Removes BackWPup roles and capabilities.
 */
function backwpup_remove_roles() {
	remove_role( 'backwpup_admin' );
 	remove_role( 'backwpup_helper' );
	remove_role( 'backwpup_check' );

	//remove capabilities to administrator role
	$role = get_role( 'administrator' );
	if ( is_object( $role ) && method_exists( $role, 'remove_cap' ) ) {
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
		$role->remove_cap( 'backwpup_restore' );
	}
}
