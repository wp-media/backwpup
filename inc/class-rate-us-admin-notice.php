<?php

/**
 *
 */
class BackWPup_Rate_Us_Admin_Notice {

	const NOTICE_ID = 'rate_us';
	const MAIN_ADMIN_PAGE_IDS = 'toplevel_page_backwpup';

	private static $main_admin_page_ids = array(
		'toplevel_page_backwpup',
		'toplevel_page_backwpup-network',
	);

	/**
	 * A flag set once per request that is true when the notice should be shown on the page
	 *
	 * @var bool
	 */
	private static $should_show;

	/**
	 * Display a notice in BackWPup admin dashboard.
	 */
	public function dashboard_message() {

		static $done;
		$screen_id = get_current_screen()->id;
		if ( ! $done && in_array( $screen_id, self::$main_admin_page_ids, true ) && $this->should_display() ) {
			$done = true;
			?>
			<div class="metabox-holder postbox" id="backwpup_dismiss_rate_us_notice">
				<div class="inside">
					<?php echo $this->widget_markup() ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Should we display the notice ?
	 *
	 * @return bool
	 */
	private function should_display() {

		if ( ! is_bool( self::$should_show ) ) {
			if ( class_exists( 'BackWPup_Pro', false ) ) {
				self::$should_show = false;
			} else {
				$option            = new BackWPup_Dismissible_Notice_Option( true );
				self::$should_show = ! $option->is_dismissed( self::NOTICE_ID );
			}
		}

		return self::$should_show;
	}

	/**
	 *  The markup for the admin page message.
	 *
	 * @return string
	 */
	private function widget_markup() {

		$dismiss_url = BackWPup_Dismissible_Notice_Option::dismiss_action_url(
			self::NOTICE_ID,
			BackWPup_Dismissible_Notice_Option::FOR_USER_FOR_GOOD_ACTION
		);

		$rate_us     = esc_html__( 'Make Us Happy and Give Your Rating', 'backwpup' );
		$rate_us_url = esc_html__( 'https://wordpress.org/support/plugin/backwpup/reviews/', 'backwpup' );

		ob_start();
		?>
		<div>
			<p>
				<?php
				echo esc_html__(
					'Are you happy with BackWPup? If you are satisfied with our free plugin and support, then please make us even happier and just take 30 seconds to leave a positive rating. :) We would really appreciate that and it will motivate our team to develop even more cool features for BackWPup!',
					'backwpup'
				);
				?>
			</p>
			<p>
				<a
					style="background: #9FC65D; border-color: #7ba617 #719c0d #719c0d; -webkit-box-shadow: 0 1px 0 #719c0d; box-shadow: 0 1px 0 #719c0d; text-shadow: 0 -1px 1px #719c0d, 1px 0 1px #719c0d, 0 1px 1px #719c0d, -1px 0 1px #719c0d;"
					class="button button-primary"
					href="<?php echo esc_url( $rate_us_url ) ?>"
					target="_blank">
					<?php echo $rate_us ?>
				</a>

				<a
					class="button"
					id="backwpup_dismiss_rate_us"
					href="<?php echo esc_url( $dismiss_url ) ?>">
					<?php echo esc_html__( 'Don\'t show again', 'backwpup' ) ?>
				</a>
			</p>
		</div>
		<script>
			(
				function( $ ) {
					$( '#backwpup_dismiss_rate_us' ).on( 'click', function( e ) {
						e.preventDefault();
						$.post( $( this ).attr( 'href' ), { isAjax: 1 } );
						$( '#backwpup_dismiss_rate_us_notice' ).hide();
					} );
				}
			)( jQuery );
		</script>
		<?php

		return ob_get_clean();
	}
}