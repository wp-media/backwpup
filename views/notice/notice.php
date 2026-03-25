<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notice view.
 *
 * @var \Inpsyde\BackWPup\Notice\NoticeMessage $bind
 */
?>
<div
	class="notice<?php echo $bind->type ? ' ' . esc_attr( $bind->type ) : ''; ?> notice-inpsyde"
	id="<?php echo esc_attr( $bind->id ); ?>_notice"
	data-notice-id="<?php echo esc_attr( $bind->id ); ?>"
>
	<div class="notice-inpsyde__content">
		<?php backwpup_template( $bind, $bind->template ); ?>
	</div>
	<?php if ( isset( $bind->button_url ) || isset( $bind->dismiss_action_url ) ) { ?>
		<p class="notice-inpsyde-actions">
			<?php if ( isset( $bind->button_url ) ) { ?>
				<a
					class="button button--inpsyde"
					href="<?php echo esc_url( $bind->button_url ); ?>"
					target="_blank"
				>
					<?php echo esc_html( $bind->button_label ); ?>
				</a>

			<?php } ?>
			<?php if ( isset( $bind->dismiss_action_url ) ) { ?>
				<a
					class="button dismiss-button"
					id="<?php echo esc_attr( $bind->id ); ?>_dismiss"
					href="<?php echo esc_url( $bind->dismiss_action_url ); ?>"
				>
					<?php echo esc_html_e( 'Don\'t show again', 'backwpup' ); ?>
				</a>
			<?php } ?>
		</p>
	<?php } ?>
</div>
