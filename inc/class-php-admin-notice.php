<?php

/**
 *
 */
class BackWPup_Php_Admin_Notice {

	const NOTICE_ID = 'php52';
	const MAIN_ADMIN_PAGE_ID = 'toplevel_page_backwpup';

	/**
	 * A flag set once per request that is true when the notice should be shown on the page
	 *
	 * @var bool
	 */
	private static $should_show;

	/**
	 * Array of screen ids where we don't want to show any notice
	 *
	 * @var string[]
	 */
	private static $pages_to_skip = array(
		'backwpup-pro_page_backwpupabout',
		'backwpup-pro_page_backwpup-phone-home-consent',
	);

	/**
	 * For PHP 5.2 users, display an admin notice when not in any of the BackWPup admin pages.
	 * On BackWPup admin pages an "extended" version of the notice is shown, so no need of the notice.
	 *
	 * @wp-hook admin_notices
	 */
	public function admin_notice() {
		if ( $this->should_display() ) {
			echo $this->admin_notice_markup();
		}
	}

	/**
	 * For PHP 5.2 users, display a notice in all the the BackWPup admin pages.
	 */
	public function admin_page_message() {
		if ( get_current_screen()->id === self::MAIN_ADMIN_PAGE_ID && $this->should_display( true ) ) {
			echo $this->admin_page_markup();
		}
	}

	/**
	 * We don't display the notice if the PHP version is higher that PHP 5.3
	 * or if it was dismissed for good.
	 *
	 * @param bool $check_only_version
	 *
	 * @return bool
	 */
	private function should_display( $check_only_version = false ) {

		// If already checked, don't check again
		if ( is_bool( self::$should_show ) ) {
			return self::$should_show;
		}

		// By default, we don't show the notice to user with PHP 5.3+, but we make it filterable
		$allowed_version = apply_filters( 'backwpup_php52_notice_allowed_version', '5.3' );

		// If not PHP 5.2, don't do anything
		if ( version_compare( PHP_VERSION, $allowed_version, '>=' ) ) {
			self::$should_show = false;

			return self::$should_show;
		}

		$screen        = get_current_screen();
		$is_dashboard  = $screen->id === 'dashboard';
		$is_backwpup   = $screen->id === 'toplevel_page_backwpup' || strpos( $screen->id, 'backwpup' ) === 0;
		$pages_to_skip = in_array( $screen->id, self::$pages_to_skip, true );

		// On pages explicitly skipped, don't show anything
		if ( ! $is_dashboard && ( ! $is_backwpup || $pages_to_skip ) ) {

			self::$should_show = false;

			return self::$should_show;
		}

		// On main admin page only show the extended notice
		if ( $screen->id === self::MAIN_ADMIN_PAGE_ID && ! $check_only_version ) {
			return false;
		}

		// Notice on main admin page can't be dismissed for good
		if ( $check_only_version ) {
			self::$should_show = true;

			return self::$should_show;
		}

		// If notice is dismissed for good, don't show it
		$option = new BackWPup_Dismissible_Notice_Option( true );

		self::$should_show = ! $option->is_dismissed( self::NOTICE_ID );

		return self::$should_show;
	}

	/**
	 * The markup for the admin notice.
	 *
	 * @return string
	 */
	private function admin_notice_markup() {

		ob_start();
		$learn_more_url = $this->backwpup_admin_page_url();
		$dismiss_url    = BackWPup_Dismissible_Notice_Option::dismiss_action_url(
			self::NOTICE_ID,
			BackWPup_Dismissible_Notice_Option::FOR_GOOD_ACTION
		);
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php echo esc_html__( 'With the upcoming major release, BackWPup will be requiring PHP version 5.3 or higher.',
				                'backwpup' ) ?>
				<?php echo esc_html__( 'Currently, you are running PHP version 5.2.', 'backwpup' ) ?>
				<strong>
					<a href="<?php echo $learn_more_url ?>"><?php echo esc_html__( 'Please urgently read here!', 'backwpup' ) ?></a>
				</strong>
			</p>
			<p>
				<a style="font-size:smaller;" id="backwpup_dismiss_php52_notice" href="<?php echo esc_url( $dismiss_url ) ?>">
					<?php echo esc_html__( 'Don\'t show again.', 'backwpup' ) ?>
				</a>
			</p>
		</div>
		<script>
			(
				function( $ ) {
					$( '#backwpup_dismiss_php52_notice' ).on( 'click', function( e ) {
						e.preventDefault();
						var $link = $( this );
						$.post( $link.attr( 'href' ), { isAjax: 1 } );
						$link.closest( '.notice' ).hide();
					} );
				}
			)( jQuery );
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 *  The markup for the admin page message.
	 *
	 * @return string
	 */
	private function admin_page_markup() {
		ob_start();
		?>
		<div class="notice notice-error is-dismissible">
			<h3><?php echo esc_html__( 'Please urgently read here!', 'backwpup' ) ?></h3>
			<p>
				<?php echo esc_html__( 'BackWPup has determined, your installation is still running on the old PHP 5.2 version.', 'backwpup' ) ?>
			</p>
			<p>
				<?php echo esc_html__( 'In order to ensure a fast and secure development for BackWPup, we will most likely not support PHP version 5.2 in our next version.', 'backwpup' ) ?>
				<?php echo esc_html__( 'No need to worry, your host can update your PHP version relatively quickly and without any problems.', 'backwpup' ) ?>
				<?php echo esc_html__( 'Otherwise you can continue to stay on this last version and do not update the plugin in the future!', 'backwpup' ) ?>
			</p>
			<p>
				<strong><?php echo $this->contact_page_link() ?></strong><br>
				<?php echo esc_html__( 'If the response from PHP 5.2 users is surprisingly high, we will eventually keep support for PHP 5.2 for a while.', 'backwpup' ) ?>
			</p>
			<p>
				<?php echo esc_html__( 'Cheers!', 'backwpup' ) ?><br>
				<?php echo esc_html__( 'Your BackWPup Team!', 'backwpup' ) ?>
			</p>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Return the URL for the main admin page of BackWPup.
	 *
	 * @return string
	 */
	private function backwpup_admin_page_url() {

		return add_query_arg(
			array( 'page' => 'backwpup' ),
			is_multisite() ? network_admin_url( '/admin.php' ) : admin_url( '/admin.php' )
		);
	}

	/**
	 * Return a link to BackWPup contact page.
	 *
	 * @return string
	 */
	private function contact_page_link() {
		/* Translators: This is the anchor text for an HTML link pointing to BackWPup contact page */
		$contact_us = esc_html__( 'contact us', 'backwpup' );
		/* Translators: %s is replaced by an HTML link with text "contact us" pointing to BackWPup contact page */
		$contact_us_text = esc_html__( 'If you would like to have PHP 5.2 supported, please %s.', 'backwpup' );
		$contact_us_url  = esc_html__( 'https://backwpup.com/php52/', 'backwpup' );
		$contact_us_link = sprintf( '<a href="%s" target="_blank">%s</a>', $contact_us_url, $contact_us );

		return sprintf( $contact_us_text, $contact_us_link );
	}
}