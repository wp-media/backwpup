<?php

/**
 *
 */
class BackWPup_BetaTester_Admin_Notice {

	const NOTICE_ID = 'beta_tester';
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
			<div class="metabox-holder postbox" id="backwpup_dismiss_beta_tester_notice">
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
			$option            = new BackWPup_Dismissible_Notice_Option( true );
			self::$should_show = ! $option->is_dismissed( self::NOTICE_ID );
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

		$join_us     = esc_html__( 'Join us as beta tester!', 'backwpup' );
		$join_us_url = esc_html__( 'https://backwpup.com/become-backwpup-beta-tester/', 'backwpup' );

		ob_start();
		?>
		<div>
			<p>
				<?php
				echo esc_html__(
					'To ensure that our releases are as bug-free as possible, we need you as a beta tester!',
					'backwpup'
				);
				?>
			</p>
			<p>
				<a
					style="background: #8fba2b; border-color: #7ba617 #719c0d #719c0d; -webkit-box-shadow: 0 1px 0 #719c0d; box-shadow: 0 1px 0 #719c0d; text-shadow: 0 -1px 1px #719c0d, 1px 0 1px #719c0d, 0 1px 1px #719c0d, -1px 0 1px #719c0d;"
					class="button button-primary"
					href="<?php echo esc_url( $join_us_url ) ?>"
					target="_blank">
					<?php echo $join_us ?>
				</a>

				<a
					class="button"
					id="backwpup_dismiss_beta_tester"
					href="<?php echo esc_url( $dismiss_url ) ?>">
					<?php echo esc_html__( 'Don\'t show again', 'backwpup' ) ?>
				</a>
			</p>
		</div>
		<script>
			(
				function( $ ) {
					$( '#backwpup_dismiss_beta_tester' ).on( 'click', function( e ) {
						e.preventDefault();
						$.post( $( this ).attr( 'href' ), { isAjax: 1 } );
						$( '#backwpup_dismiss_beta_tester_notice' ).hide();
					} );
				}
			)( jQuery );
		</script>
		<?php

		return ob_get_clean();
	}
}