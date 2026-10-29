<?php
/**
 * Localized Strings
 */

return array(
	'uploadingArchive'            => esc_html__( 'Uploading Archive...', 'backwpup' ),
	'restoringDirectories'        => esc_html__( 'Restoring: Directories ...', 'backwpup' ),
	'restoringDatabase'           => esc_html__( 'Restoring: Database ...', 'backwpup' ),
	'extractingArchive'           => esc_html__( 'Extracting Archive please be patient ...', 'backwpup' ),
	'downloadingArchive'          => esc_html__( 'Downloading Archive...', 'backwpup' ),
	'successDatabaseRestored'     => esc_html__( 'Database restored successfully.', 'backwpup' ),
	'failedRestore'               => esc_html__(
		'Ops! There was an error during database restore. Please see the log for more information.',
		'backwpup'
	),
	'restoringPrefix'             => esc_html__( 'Restoring: ', 'backwpup' ),
	'noEventSourceInWindowObject' => esc_html__( 'No EventSource found in the brower. Cannot continue.', 'backwpup' ),
	'successFilesRestored'        => esc_html__( 'Directories restored successfully.', 'backwpup' ),
	'connectionFailed'            => esc_html__(
		'The restore step could not be started, your session may have expired. Please log in again in another tab and press Start to continue.',
		'backwpup'
	),
);
