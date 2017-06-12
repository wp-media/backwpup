<?php

/**
 *
 */
class BackWPup_Become_Inpsyder_Widget {

	const NOTICE_ID = 'become_inpsyder';

	private static $main_admin_page_ids = array(
		'toplevel_page_backwpup',
		'toplevel_page_backwpup-network',
	);

	/**
	 * A flag set once per request that is true when the widget should not be shown on the page
	 *
	 * @var bool
	 */
	private static $should_show;

	public function setup_widget() {

		if ( defined( 'INPSYDE_DASHBOARD_WIDGET' ) && ! INPSYDE_DASHBOARD_WIDGET ) {
			return;
		}

		if ( $this->should_display() ) {
			wp_add_dashboard_widget(
				'backwpup_become_inpsyder',
				esc_html__( 'Make BackWPup better!', 'backwpup' ),
				array( $this, 'print_widget_markup' )
			);
		}
	}

	public function print_plugin_widget_markup() {

		static $done;
		$screen_id = get_current_screen()->id;
		if ( ! $done && in_array( $screen_id, self::$main_admin_page_ids, true ) && $this->should_display() ) {
			$done = true;
			?>
			<div class="metabox-holder postbox" id="backwpup_become_inpsyder">
				<h3 class="hndle"><span><?php echo esc_html__( 'Make BackWPup better!', 'backwpup' ) ?></span></h3>
				<div class="inside">
					<?php echo $this->widget_markup( 'left' ) ?>
				</div>
			</div>
			<?php
		}
	}

	public function print_widget_markup() {

		if ( defined( 'INPSYDE_DASHBOARD_WIDGET' ) && ! INPSYDE_DASHBOARD_WIDGET ) {
			return;
		}

		static $done;
		if ( ! $done && $this->should_display() ) {
			$done = true;
			echo $this->widget_markup();
		}
	}

	/**
	 * We don't display widget if it was dismissed for good.
	 *
	 * @return bool
	 */
	private function should_display() {

		// If already checked, don't check again
		if ( is_bool( self::$should_show ) ) {
			return self::$should_show;
		}
		
		if ( class_exists( 'BackWPup_Pro', false ) ) {
			self::$should_show = false;
		} else {
			$option = new BackWPup_Dismissible_Notice_Option( false );

			// If notice is dismissed for good, don't show it
			self::$should_show = ! $option->is_dismissed( self::NOTICE_ID );
		}

		return self::$should_show;
	}

	/**
	 * The markup for the admin notice.
	 *
	 * @param string $btn_float
	 *
	 * @return string
	 */
	private function widget_markup( $btn_float = 'right' ) {

		$dismiss_url = BackWPup_Dismissible_Notice_Option::dismiss_action_url(
			self::NOTICE_ID,
			BackWPup_Dismissible_Notice_Option::FOR_USER_FOR_GOOD_ACTION
		);

		$plugin_file = dirname( dirname( __FILE__ ) ) . '/backwpup.php';
		$logo_url    = plugins_url( '/assets/images/inpsyde.png', $plugin_file );

		$job_url = __(
			'https://inpsyde.com/en/jobs/?utm_source=BackWPup&utm_medium=Link&utm_campaign=BecomeAnInpsyder',
			'backwpup'
		);

		ob_start();
		?>
		<div>
			<p align="justify">
				<?php
				esc_html_e(
					'We want to make BackWPup even stronger and its support much faster.',
					'backwpup'
				);
				?>
				<br>
				<strong>
					<?php
					esc_html_e(
						'This is why we are looking for a talented developer who can work remotely and support us in BackWPup',
						'backwpup'
					);
					?>
				</strong>
				<?php
				esc_html_e(
					'and other exciting WordPress projects at our VIP partner agency.',
					'backwpup'
				);
				?>
			</p>
			<p<?php echo $btn_float === 'right' ? ' align="right' : '' ?>">
			<a
				style="background: #9FC65D; border-color: #7ba617 #719c0d #719c0d; -webkit-box-shadow: 0 1px 0 #719c0d; box-shadow: 0 1px 0 #719c0d; text-shadow: 0 -1px 1px #719c0d, 1px 0 1px #719c0d, 0 1px 1px #719c0d, -1px 0 1px #719c0d;"
				class="button button-large button-primary"
				href="<?php echo esc_url( $job_url ) ?>"
				target="_blank">
				<?php echo esc_html__( 'Apply now!', 'backwpup' ) ?>
			</a>
			</p>
			<hr>
			<p>

				<a class="button button-small" id="backwpup_dismiss_become_new_inpsyder" href="<?php echo esc_url( $dismiss_url ) ?>">
					<?php echo esc_html__( 'Don\'t show again', 'backwpup' ) ?>
				</a>

				<a style="float: right;" href="<?php echo $job_url ?>">
					<img src="<?php echo $logo_url ?>" alt="<?php echo esc_attr__( 'Work for Inpsyde', 'backwpup' ) ?>">
				</a>
			</p>
		</div>
		<script>
			(
				function( $ ) {
					$( '#backwpup_dismiss_become_new_inpsyder' ).on( 'click', function( e ) {
						e.preventDefault();
						$.post( $( this ).attr( 'href' ), { isAjax: 1 } );
						$( '#backwpup_become_inpsyder' ).hide();
						$( '#backwpup_become_inpsyder-hide' ).click();
					} );
				}
			)( jQuery );
		</script>
		<?php

		return ob_get_clean();
	}
}