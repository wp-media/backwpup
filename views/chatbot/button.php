<?php
/**
 * Data from \WPMedia\BackWPup\Admin\Chatbot::render_button
 *
 * @var array $data Button configuration with optional 'label' key.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$label = $data['label'] ?? __( 'Get Help', 'backwpup' );
?>
<button
	type="button"
	class="button button-secondary fixed bottom-4 right-5 z-50 !bg-gray-100"
	id="backwpup-open-chatbot"
	aria-haspopup="dialog"
	aria-controls="backwpup-chatbot-modal"
><?php echo esc_html( $label ); ?></button>
