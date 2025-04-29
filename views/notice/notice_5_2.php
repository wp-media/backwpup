<p class="notice-titre">🎉 <?php esc_html_e( 'BackWPup 5.2 is here!', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="notice_5_2_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
	<?php
	printf(
	// Translators: %1$s = opening tag, %2$s = closing tag.
		esc_html__(
			'We’ve listened to your feedback and are happy to announce that this new version brings back the option to pick the exact day for monthly backups, 
			as well as a “Backup Now” button for each scheduled job—among other improvements. Check out our %1$sblog post%2$s to learn more.',
			'backwpup'
		),
		'<a target="_blank" rel="noopener noreferrer" href="' . esc_url( 'https://backwpup.com/backwpup-5-2/' ) . '">',
		'</a>',
	);
	?>
</p>
