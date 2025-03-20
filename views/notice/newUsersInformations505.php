<p class="notice-titre"><?php esc_html_e( '🎉 Welcome to BackWPup 5.0!', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="informations_505_notice_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
<?php
	printf(
		// Translators: %1$s = <a>, %2$s = </a>.
		esc_html__(
			'We\'ve completely redesigned the interface for a simpler and more intuitive backup experience. To help you navigate the new layout, please check out our %1$sblog post%2$s which guides you through the changes.',
			'backwpup'
		),
		'<a target="_blank" href="https://backwpup.com/backwpup-5-0/">',
		'</a>'
	);
	?>
</p>
