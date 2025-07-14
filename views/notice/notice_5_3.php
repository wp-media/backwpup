<p class="notice-titre">🎉 <?php esc_html_e( 'BackWPup 5.3 is here!', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="notice_5_3_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
	<?php
	esc_html_e(
		'You can now back up Files & Database together in a single backup and schedule it. We’ve also fixed new backups not respecting your selected archive format, along with other improvements.',
		'backwpup'
	)
	?>
</p>
