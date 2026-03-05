<?php
/**
 * Before Restore Upload Content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'backwpup_restore_before_upload_content' );

// Restore Upload Content.
do_action( 'backwpup_restore_upload_content' );

// After Restore Upload Content.
do_action( 'backwpup_restore_after_upload_content' );
