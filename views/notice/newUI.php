<p class="notice-titre"><?php esc_html_e( '🎉 Welcome to BackWPup 5.0!', 'backwpup' ); ?></p>
<span><a class="closeIt" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>">Dismiss</a></span>
<p><?php esc_html_e( 'We\'ve completely redesigned the interface for a simpler and easier backup experience. Rest assured, all your old backups remain fully operational. Custom jobs are now replaced by two default ones: Files Backup & Database Backup.', 'backwpup' ); ?></p>
<p>
<?php
	echo wp_kses(
	__( 'You can still view your old jobs in read-only mode and manage them from the settings page. For help navigating the new layout, our <a href="https://backwpup.com/backwpup-5-0/">blog post</a> is here to guide you swiftly through the changes.', 'backwpup' ),
	[
		'a' => [
			'href'   => true,
			'target' => true,
		],
	]
	);
	?>
</p>