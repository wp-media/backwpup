<p class="notice-titre"><?php esc_html_e( 'BackWPup - Update Alert', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); ?>" data-bwpu-hide="disabled_legacy_jobs"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
	<?php
	printf(
	// Translators: %1$s = <a>, %2$s = </a>.
		esc_html__(
			'We have identified a critical issue with the recent BackWPup 5.1 update: all backup jobs %1$screated prior to version 5%2$s are now disabled and not running. Unfortunately, reverting to a previous version will not restore these jobs. 
			%3$sWe are actively working on solutions to assist you in restoring your backups, and we anticipate these will be available with the upcoming 5.1.3 update in just a few days.
			%3$sTo check if your backup jobs are affected, please click %4$shere%5$s.
			%3$sIf you are impacted and cannot wait for the update, you can restore a database backup from before version 5.1 to recover your previous jobs.
			%3$sWe sincerely apologize for this inconvenience and appreciate your patience and understanding.',
			'backwpup'
		),
		'<strong>',
		'</strong>',
		'<br>',
		'<a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=backwpupjobs' ) ) . '">',
		'</a>'
	);
	?>
</p>
