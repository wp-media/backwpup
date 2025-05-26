<p class="notice-titre">🎉 <?php esc_html_e( 'BackWPup 5.2.2 is here!', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="notice_5_2_2_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
	<?php
	esc_html_e(
		'You can now deselect the default Website Server as a storage destination. We’ve fixed Google Drive & Dropbox storage issues, the sidebar not clickable error, and reduced plugin size, among other improvements for a stable BackWPup experience.',
		'backwpup'
	)
	?>
</p>
