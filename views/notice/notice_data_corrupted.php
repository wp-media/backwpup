<p class="notice-titre">⚠️ <?php esc_html_e( 'Your data is corrupted', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="notice_data_corrupted_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
	<?php
	printf(
	// Translators: %1$s = opening tag, %2$s = closing tag.
		esc_html__(
			'Plugin update was successful, but we ran into an unexpected issue with one of your backup data. For assistance or to ensure everything is working as expected, %1$sreach out to support%2$s.',
			'backwpup'
		),
		'<a target="_blank" rel="noopener noreferrer" href="' . esc_url( 'https://backwpup.com/contact/' ) . '">',
		'</a>',
	);
	?>
</p>
