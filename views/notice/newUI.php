<p class="notice-titre"><?php esc_html_e( '🎉 Welcome to BackWPup 5.0!', 'backwpup' ); ?></p>
<span><a class="closeIt" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>">Dismiss</a></span>
<p><?php esc_html_e( 'We’ve completely redesigned the interface for a simpler and faster backup experience. All your favorite features are still here now organized more intuitively!', 'backwpup' ); ?></p>
<p>
<?php
	echo wp_kses(
	__( 'Need help finding menus or pages? If you need assistance navigating the new layout, check out our <a href="https://backwpup.com/backwpup-5-0/">blog post</a> to get started quickly with the new interface.', 'backwpup' ),
	[
		'a' => [
			'href'   => true,
			'target' => true,
		],
	]
	);
	?>
</p>