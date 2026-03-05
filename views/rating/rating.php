<?php
/**
 * Data from \WPMedia\BackWPup\Admin\RatingView::render
 *
 * @var array $data Notice configuration.
 */

$rating_title   = $data['title'] ?? '';
$rating_message = $data['message'] ?? '';
$dismiss_url    = $data['dismiss_url'] ?? '';
$remind_url     = $data['remind_url'] ?? '';
$leave_url      = $data['leave_url'] ?? '';
$notice_id      = $data['notice_id'] ?? 'backwpup_rate_notice';
?>
<div id="<?php echo esc_attr( $notice_id ); ?>"
	class="notice notice-warning notice-inpsyde notice-backwpup_rate backwpup-typography"
	data-notice-id="<?php echo esc_attr( $notice_id ); ?>"
>

	<div class="notice-inpsyde__content">

	<p class="text-primary-darker font-title font-bold text-2xl"><?php echo wp_kses_post( $rating_title ); ?></p>

	<a class="closeIt bwpup-ajax-close"
	data-bwpu-hide="<?php echo esc_attr( $notice_id ); ?>"
	href="<?php echo esc_url( $dismiss_url ); ?>"
	>
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'backwpup' ); ?></span>
	</a>

	<?php if ( '' !== $rating_message ) : ?>
	<div class="notice-message">
		<?php echo wp_kses_post( wpautop( esc_html( $rating_message ) ) ); ?>
	</div>
	<?php endif; ?>

	<p>
		<a class="button button-primary bwu-leave-review text-base"
		href="<?php echo esc_url( $leave_url ); ?>"
		target="_blank"
		rel="noopener noreferrer"
	>
		<?php esc_html_e( 'Leave a review', 'backwpup' ); ?>
		</a>
		<a class="button button-secondary text-base" href="<?php echo esc_url( $remind_url ); ?>">
		<?php esc_html_e( 'Remind me later', 'backwpup' ); ?>
		</a>
	</p>
	</div>
</div>
