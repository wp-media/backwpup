<?php /** @var \stdClass $bind */ ?>
<?php foreach ( $bind->notifies as $level => $notices ) { ?>
	<div class="notice notice-<?php echo esc_attr( $level ); ?>">
		<?php foreach ( $notices as $notice ) { ?>
			<p><?php echo esc_html( $notice ); ?></p>
		<?php } ?>
	</div>
	<?php
}
