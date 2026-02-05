<?php
/**
 * Data from \WPMedia\BackWPup\Admin\Chatbot::render_button
 *
 * @var array $data Button configuration with optional 'label' key.
 */

$label = $data['label'] ?? __( 'Contact Support', 'backwpup' );
?>
<button
	type="button"
	class="button button-secondary"
	id="backwpup-open-chatbot"
	aria-haspopup="dialog"
	aria-controls="backwpup-chatbot-modal"
><?php echo esc_html( $label ); ?></button>
