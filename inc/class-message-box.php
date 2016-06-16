<?php

/**
 * Class BackWPup_Message_Box
 *
 * $message_box = new BackWPup_Message_Box( 'restore_beta_survey' );
 * $message_box->set_box_html(
 *    'test'
 *  );
 * $message_box->init_hooks();
 */
class BackWPup_Message_Box {

	/**
	 * ID of this message box
	 * @var string
	 */
	private $box_id = '';

	/**
	 * HTML of this message box
	 * @var string
	 */
	private $box_html = '';

	/**
	 * @var string Date to a campaign should be displayed
	 */
	private $campaign_to_date = '0000-00-00';

	/**
	 * BackWPup_Message_Box constructor.
	 *
	 * @param string $box_id Name for box to have more than one or future one
	 */
	public function __construct( $box_id ) {

		if ( ! $box_id || ! is_string( $box_id ) ) {
			return null;
		}

		$this->box_id = sanitize_title_with_dashes( $box_id );
	}

	/**
	 * Init hooks to displaying message box
	 */
	public function init_hooks() {

		if ( ! current_user_can( 'backwpup' ) ) {
			return;
		}

		$boxes_display = get_user_meta( get_current_user_id(), 'backwpup_message_boxes_not_display', true );
		if ( ! $boxes_display ) {
			$boxes_display = array();
		}

		if ( ! empty( $boxes_display[ $this->box_id ] ) ) {
			return;
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'backwpupabout' ) {
			return;
		}


		if ( $this->campaign_to_date !== '0000-00-00' ) {
			$this_day = date( 'Y-m-d' );
			if ( $this_day > $this->campaign_to_date ) {
				return;
			}
		}

		add_action( 'admin_notices', array( $this, 'output_box_html' ) );
		add_action( 'admin_init', array( $this, 'save_not_display' ) );
	}

	/**
	 * Output the message box
	 */
	public function output_box_html() {

		$url = add_query_arg( array( 'backwpup_msg_' . $this->box_id => 1 ), '//' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] );

		?>
		<div id="backwpup-message-<?php echo $this->box_id; ?>" class="notice" style="padding:0;border:0;position:relative;">
			<?php echo $this->box_html; ?>
			<a href="<?php echo $url; ?>" class="dismiss" style="text-decoration:none;position:absolute;top:5px;right:5px;" title="<?php echo __( 'Dismiss', 'backwpup' ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
		</div>
		<?php

	}

	/**
	 * Add box html for output with this box
	 *
	 * @param $html
	 */
	public function set_box_html( $html ) {

		if ( ! $html || ! is_string( $html ) ) {
			return;
		}

		$this->box_html = $html;
	}

	/**
	 * Save user meta for boxes that should not be displayed
	 */
	public function save_not_display() {

		if ( ! empty( $_GET[ 'backwpup_msg_' . $this->box_id ] ) ) {
			$boxes_display = get_user_meta( get_current_user_id(), 'backwpup_message_boxes_not_display', true );
			if ( ! $boxes_display ) {
				$boxes_display = array();
			}
			$boxes_display[ $this->box_id ] = true;
			update_user_meta( get_current_user_id(), 'backwpup_message_boxes_not_display', $boxes_display );
			remove_action( 'admin_notices', array( $this, 'output_box_html' ) );
		}
	}

	/**
	 * Date to a campaign should be displayed
	 *
	 * @since 3.3.2
	 *
	 * @param string $campaign_to_date
	 */
	public function set_campaign_to_date( $campaign_to_date = '0000-00-00' ) {

		$this->campaign_to_date = $campaign_to_date;
	}
}
