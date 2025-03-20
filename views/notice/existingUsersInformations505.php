<p class="notice-titre"><?php esc_html_e( 'You\'re currently using BackWPup version 5.0, featuring a completely redesigned interface.', 'backwpup' ); ?></p>
<a href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" class="closeIt"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
<?php
	printf(
		// Translators: %1$s = <a>, %2$s = </a>, %3$s = <a>.
		esc_html__(
			'Rest assured, all your old backups remain fully operational. You can still view your previous jobs in read-only mode and manage them from %1$shere%2$s. We\'re actively working to restore the ability to create, edit, and schedule backup jobs as needed. In the meantime, if you prefer the previous setup, you can roll back to an earlier version. Visit %3$sthis link%2$s to access previous versions.',
			'backwpup'
		),
		'<a target="_blank" href="/wp-admin/admin.php?page=backwpupjobs">',
		'</a>',
		'<a target="_blank" href="https://wordpress.org/plugins/backwpup/advanced/">'
	);
	?>
</p>
