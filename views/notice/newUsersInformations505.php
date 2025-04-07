<p class="notice-titre"><?php esc_html_e( '🎉 Welcome to BackWPup 5.1!', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="informations_505_notice_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
<?php
	printf(
		// Translators: %1$s = <a>, %2$s = </a>.
		esc_html__(
			'We\'ve brought back unlimited backup jobs, enhanced the "Backup Now" feature, and added customizable backup titles, among other improvements. Check out our %1$sblog post%2$s to learn more about these updates.',
			'backwpup'
		),
		'<a target="_blank" href="https://backwpup.com/backwpup-5-1/">',
		'</a>'
	);
	?>
</p>
