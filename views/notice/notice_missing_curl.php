<p class="notice-titre">⚠️ <?php esc_html_e( 'cURL functions are missing', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="notice_missing_curl_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
	<?php
	printf(
	// Translators: %1$s = opening tag, %2$s = closing tag.
		esc_html__(
			'PHP cURL functions are missing in your installation. Not all features of BackWPup will work without them. Please %1$scontact support%2$s for assistance.',
			'backwpup'
		),
		'<a target="_blank" rel="noopener noreferrer" href="' . esc_url( 'https://backwpup.com/contact/' ) . '">',
		'</a>',
	);
	?>
</p>
