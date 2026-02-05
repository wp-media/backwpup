<?php
/**
 * Data from \WPMedia\BackWPup\Admin\Chatbot::render_modal
 *
 * @var array $data Modal configuration with 'title', 'close', and optional 'snapshot'
 */

$modal_title = $data['title'] ?? '';
$close       = $data['close'] ?? '';
?>
<div class="backwpup-chatbot-modal"
	id="backwpup-chatbot-modal"
	role="dialog"
	aria-modal="true"
	aria-labelledby="backwpup-chatbot-title"
	tabindex="-1">

	<div class="backwpup-chatbot-modal__panel">
	<div class="backwpup-chatbot-modal__bar">
		<strong id="backwpup-chatbot-title"><?php echo esc_html( $modal_title ); ?></strong>
		<button type="button" class="button" id="backwpup-chatbot-close" data-backwpup-chatbot-close>
		<?php echo esc_html( $close ); ?>
		</button>
	</div>

	<iframe
		class="backwpup-chatbot-modal__iframe"
		id="backwpup-chatbot-iframe"
		loading="lazy"
		referrerpolicy="no-referrer"
	></iframe>
	</div>
</div>
