<div class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width">
	<h3 class="hndle"><span><?php esc_html_e( 'Restore', 'backwpup' ); ?></span></h3>
	<div class="inside">
		<?php
		echo wp_kses(
			__(
			'You reached the last step. Now you\'re ready to restore the data. Simply press the <strong>Start</strong> button and the restore starts.',
			'backwpup'
		),
			[ 'strong' => [] ]
			);
		?>
	</div>
</div>
