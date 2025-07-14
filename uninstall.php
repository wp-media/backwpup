<?php

//if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

/** @var wpdb $wpdb */
global $wpdb;

// only uninstall if no BackWPup Version active.
if ( ! class_exists( \BackWPup::class ) ) {
	require_once __DIR__ . '/src/Dependencies/BerlinDB/Database/Base.php';
	require_once __DIR__ . '/src/Dependencies/BerlinDB/Database/Column.php';
	require_once __DIR__ . '/src/Dependencies/BerlinDB/Database/Schema.php';
	require_once __DIR__ . '/src/Dependencies/BerlinDB/Database/Query.php';
	require_once __DIR__ . '/src/Dependencies/BerlinDB/Database/Row.php';
	require_once __DIR__ . '/src/Dependencies/BerlinDB/Database/Table.php';
	require_once __DIR__ . '/src/Dependencies/BerlinDB/Database/Queries/Meta.php';
	require_once __DIR__ . '/src/Dependencies/BerlinDB/Database/Queries/Date.php';
	require_once __DIR__ . '/src/Dependencies/BerlinDB/Database/Queries/Compare.php';
	require_once __DIR__ . '/src/Common/Database/TableInterface.php';
	require_once __DIR__ . '/src/Common/Database/Tables/AbstractTable.php';
	require_once __DIR__ . '/src/Backup/Database/Tables/Backup.php';

	$tables = [
		new \WPMedia\BackWPup\Backup\Database\Tables\Backup(),
	];
	backwpup_remove_tables( $tables );

    //delete plugin options
    if (is_multisite()) {
        $wpdb->query('DELETE FROM ' . $wpdb->sitemeta . " WHERE meta_key LIKE '%backwpup_%' ");
    } else {
        $wpdb->query('DELETE FROM ' . $wpdb->options . " WHERE option_name LIKE '%backwpup_%' ");
    }

	$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE '%backwpup_%' " );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

    //delete Backwpup user roles
    // Special handling for multisite when network-activated.
    if (is_multisite()) {
        $sites = get_sites([
            'fields' => 'ids',
        ]);
        $current_site = get_current_blog_id();

        foreach ($sites as $site) {
            switch_to_blog($site);
			backwpup_remove_roles();
			backwpup_remove_tables( $tables );
		}

        switch_to_blog($current_site);
    } else {
        backwpup_remove_roles();
    }
}

/**
 * Removes BackWPup roles and capabilities.
 */
function backwpup_remove_roles()
{
    remove_role('backwpup_admin');
    remove_role('backwpup_helper');
    remove_role('backwpup_check');

    //remove capabilities to administrator role
    $role = get_role('administrator');
    if (is_object($role) && method_exists($role, 'remove_cap')) {
        $role->remove_cap('backwpup');
        $role->remove_cap('backwpup_jobs');
        $role->remove_cap('backwpup_jobs_edit');
        $role->remove_cap('backwpup_jobs_start');
        $role->remove_cap('backwpup_backups');
        $role->remove_cap('backwpup_backups_download');
        $role->remove_cap('backwpup_backups_delete');
        $role->remove_cap('backwpup_logs');
        $role->remove_cap('backwpup_logs_delete');
        $role->remove_cap('backwpup_settings');
        $role->remove_cap('backwpup_restore');
    }
}

/**
 * Remove available database tables.
 *
 * @param array $tables Database tables objects.
 *
 * @return void
 */
function backwpup_remove_tables( $tables ) {
	foreach ( $tables as $table ) {
		if ( ! $table->exists() ) {
			continue;
		}
		$table->uninstall();
	}
}
