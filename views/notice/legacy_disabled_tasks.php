<p class="notice-titre"><?php esc_html_e( 'BackWPup - Restore Your Legacy Jobs', 'backwpup' ); ?></p>
<a class="closeIt" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="informations_505_notice_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
	<?php
	printf(
	// Translators: %1$s = <br>, %2$s = <a>, %3$s = </a>, %4$s = <a>, %5$s = </a>.
		esc_html__(
			'Following an issue identified in the BackWPup 5.1 update, backup jobs created before version 5 are currently not running. To assist you in reactivating these jobs, we are providing two options:%1$s%1$s

			%6$sOption 1%7$s: %2$sClick this link%3$s to see your disabled backup jobs. Select the jobs you wish to reactivate and then use the Bulk Actions menu to choose either "Activate with CRON" or "Activate with Link."%1$s%1$s

			%6$sOption 2%7$s: For advanced users, we offer a command to update backups using WP CLI. For more information, please refer to our %4$sdocumentation%5$s.',
			'backwpup'
		),
		'<br>',
		'<a target="_blank" href="' . esc_url( network_admin_url( 'admin.php?page=backwpupjobs' ) ) . '">',
		'</a>',
		'<a target="_blank" href="' . esc_url( 'https://backwpup.com/docs/article/reactivating-backup-jobs-after-update/' ) . '">',
		'</a>',
		'<strong>',
		'</strong>'
	);
	?>
</p>
