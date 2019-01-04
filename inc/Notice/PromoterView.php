<?php # -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Notice;

/**
 * Class PromoterView
 */
class PromoterView {

	/**
	 * @param \Inpsyde\BackWPup\Notice\PromoterMessage $message
	 *
	 * @return false|string
	 */
	public function notice( PromoterMessage $message, $dismiss_action_url ) {
		?>

		<div
			class="notice notice-inpsyde"
			id="<?php echo esc_attr( Promoter::ID ) ?>_notice"
			data-notice-id="<?php echo esc_attr( Promoter::ID ) ?>"
		>
			<p class="notice-inpsyde__content">
				<?php echo wp_kses_post( $message->content() ) ?>
			</p>
			<p class="notice-inpsyde-actions">
				<a
					class="button button--inpsyde"
					href="<?php echo esc_url( $message->cta_url() ) ?>"
					target="_blank"
				>
					<?php echo esc_html( $message->button_label() ) ?>
				</a>

				<a
					class="button"
					id="<?php echo esc_attr( Promoter::ID ) ?>_dismiss"
					href="<?php echo esc_url( $dismiss_action_url ) ?>"
				>
					<?php esc_html_e( 'Don\'t show again', 'backwpup' ) ?>
				</a>
			</p>
		</div>
		<?php
	}
}
