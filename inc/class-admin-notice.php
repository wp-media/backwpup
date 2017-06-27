<?php

/**
 *
 */
class BackWPup_Admin_Notice {

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
	private $should_show;
	
	private $has_displayed;
	
	private $id;
	
	private $button_text;
	
	private $button_url;
	
	private $priority;
	
	public function __construct( $id, $button_text, $button_url, $priority = 20 ) {
		$this->has_displayed = false;
		$this->id = $id;
		$this->button_text = $button_text;
		$this->button_url = $button_url;
		$this->priority = $priority;
	}
	
	public function initiate() {
		add_action( 'backwpup_admin_messages', array( $this, 'dashboard_message' ), 20 );
		BackWPup_Dismissible_Notice_Option::setup_actions(
			false,
			$this->id,
			'backwpup'
		);
	}

	/**
	 * Display a notice in BackWPup admin dashboard.
	 */
	public function dashboard_message() {

		$screen_id = get_current_screen()->id;
		if ( ! $this->has_displayed && in_array( $screen_id, self::$main_admin_page_ids, true )
			&& $this->should_display() ) {
			$done = true;
			?>
			<div class="metabox-holder postbox" id="backwpup_dismiss_<?php echo esc_attr( $this->id ) ?>_notice">
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

		if ( ! is_bool( $this->should_show ) ) {
			if ( class_exists( 'BackWPup_Pro', false ) ) {
				$this->should_show = false;
			} else {
				$option            = new BackWPup_Dismissible_Notice_Option( true );
				$this->should_show = ! $option->is_dismissed( $this->id );
			}
		}

		return $this->should_show;
	}

	/**
	 *  The markup for the admin page message.
	 *
	 * @return string
	 */
	private function widget_markup() {

		$dismiss_url = BackWPup_Dismissible_Notice_Option::dismiss_action_url(
			$this->id,
			BackWPup_Dismissible_Notice_Option::FOR_USER_FOR_GOOD_ACTION
		);

		ob_start();
		?><div><?php
		require dirname( dirname( __FILE__ ) ) . '/assets/templates/admin-notices/' .
			sanitize_file_name( str_replace( '_', '-', $this->id ) . '.php' );
		?>
			<p>
				<a
					style="background: #9FC65D; border-color: #7ba617 #719c0d #719c0d; -webkit-box-shadow: 0 1px 0 #719c0d; box-shadow: 0 1px 0 #719c0d; text-shadow: 0 -1px 1px #719c0d, 1px 0 1px #719c0d, 0 1px 1px #719c0d, -1px 0 1px #719c0d;"
					class="button button-primary"
					href="<?php echo esc_url( $this->button_url ) ?>"
					target="_blank">
					<?php echo $this->button_text ?>
				</a>

				<a
					class="button"
					id="backwpup_dismiss_<?php echo esc_attr( $this->id ) ?>"
					href="<?php echo esc_url( $dismiss_url ) ?>">
					<?php echo esc_html__( 'Don\'t show again', 'backwpup' ) ?>
				</a>
			</p>
		</div>
		<script>
			(
				function( $ ) {
					$( '#backwpup_dismiss_<?php echo esc_js( $this->id ) ?>' ).on( 'click', function( e ) {
						e.preventDefault();
						$.post( $( this ).attr( 'href' ), { isAjax: 1 } );
						$( '#backwpup_dismiss_<?php echo esc_js( $this->id ) ?>_notice' ).hide();
					} );
				}
			)( jQuery );
		</script>
		<?php

		return ob_get_clean();
	}
}