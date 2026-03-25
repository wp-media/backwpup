<?php
/**
 * Class BackWPup_Message_Box.
 *
 * $message_box = new BackWPup_Message_Box( 'restore_beta_survey' );
 * $message_box->set_box_html(
 *    'test'
 *  );
 * $message_box->init_hooks();
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BackWPup_Message_Box {

	/**
	 * ID of this message box.
	 *
	 * @var string
	 */
	private $box_id = '';

	/**
	 * HTML of this message box.
	 *
	 * @var string
	 */
	private $box_html = '';

	/**
	 * Campaign end date.
	 *
	 * @var string Date to a campaign should be displayed.
	 */
	private $campaign_to_date = '0000-00-00';

	/**
	 * BackWPup_Message_Box constructor.
	 *
	 * @param string $box_id Name for box to have more than one or future one.
	 */
	public function __construct( $box_id ) {
		if ( ! $box_id || ! is_string( $box_id ) ) {
			return;
		}

		$this->box_id = sanitize_title_with_dashes( $box_id );
	}

	/**
	 * Init hooks to displaying message box.
	 */
	public function init_hooks() {
		if ( ! current_user_can( 'backwpup' ) ) {
			return;
		}

		$boxes_display = get_user_meta( get_current_user_id(), 'backwpup_message_boxes_not_display', true );
		if ( ! $boxes_display ) {
			$boxes_display = [];
		}

		if ( ! empty( $boxes_display[ $this->box_id ] ) ) {
			return;
		}

		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( 'backwpupabout' === $page ) {
			return;
		}

		if ( '0000-00-00' !== $this->campaign_to_date ) {
			$this_day = wp_date( 'Y-m-d', time() );
			if ( $this_day > $this->campaign_to_date ) {
				return;
			}
		}

		add_action( 'admin_notices', [ $this, 'output_box_html' ] );
		add_action( 'admin_init', [ $this, 'save_not_display' ] );
	}

	/**
	 * Output the message box.
	 */
	public function output_box_html() {
		$server_name  = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$base_url     = '//' . $server_name . $request_uri;
		$nonce_action = 'backwpup_message_box_' . $this->box_id;
		$url          = add_query_arg(
			[ 'backwpup_msg_' . $this->box_id => 1 ],
			$base_url
		);
		$url          = wp_nonce_url( $url, $nonce_action );
		?>
		<div id="backwpup-message-<?php echo esc_attr( $this->box_id ); ?>" class="notice" style="padding:0;border:0;position:relative;">
			<?php echo wp_kses_post( $this->box_html ); ?>
			<a href="<?php echo esc_url( $url ); ?>" class="dismiss" style="text-decoration:none;position:absolute;top:5px;right:5px;" title="<?php echo esc_attr__( 'Dismiss', 'backwpup' ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
		</div>
		<?php
	}

	/**
	 * Add box html for output with this box.
	 *
	 * @param string $html HTML for the message box.
	 */
	public function set_box_html( $html ) {
		if ( ! $html || ! is_string( $html ) ) {
			return;
		}

		$this->box_html = $html;
	}

	/**
	 * Save user meta for boxes that should not be displayed.
	 */
	public function save_not_display() {
		$param_name     = 'backwpup_msg_' . $this->box_id;
		$should_dismiss = filter_input( INPUT_GET, $param_name, FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $should_dismiss ) ) {
			return;
		}

		$nonce_action = 'backwpup_message_box_' . $this->box_id;
		$nonce        = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return;
		}

		$boxes_display = get_user_meta( get_current_user_id(), 'backwpup_message_boxes_not_display', true );
		if ( ! $boxes_display ) {
			$boxes_display = [];
		}
		$boxes_display[ $this->box_id ] = true;
		update_user_meta( get_current_user_id(), 'backwpup_message_boxes_not_display', $boxes_display );
		remove_action( 'admin_notices', [ $this, 'output_box_html' ] );
	}

	/**
	 * Date to a campaign should be displayed.
	 *
	 * @since 3.3.2
	 *
	 * @param string $campaign_to_date Campaign end date.
	 */
	public function set_campaign_to_date( $campaign_to_date = '0000-00-00' ) {
		$this->campaign_to_date = $campaign_to_date;
	}
}
