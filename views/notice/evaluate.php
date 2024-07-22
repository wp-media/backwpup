<?php
/**
 * The evaluate notice template
 *
 * @var \Inpsyde\BackWPup\Notice\NoticeMessage $bind
 */

?>

<p class="notice-titre">BackWpUp</p>
<span><a class="closeIt" href="<?php echo esc_url( $bind->dismissurl ); ?>">Dismiss</a></span>
<div id="backwpup_notice_evaluate_step1">
	<p><?php esc_html_e( 'How is your experience with BackWPup?', 'backwpup' ); ?></p>
	<p class="notice-actions">
	<a id="backwpup_notice_evaluate_working" href="#">
		<?php esc_html_e( 'Everything is working perfectly!', 'backwpup' ); ?>
	</a>
	<br />
	<a id="backwpup_notice_evaluate_issues" href="#">
	<?php esc_html_e( "I've encountered some issues.", 'backwpup' ); ?>
	</a>
	</p>
</div>
<div id="backwpup_notice_evaluate_step_review">
	<p><?php esc_html_e( 'Fantastic! If you’re enjoying our plugin, could you take a moment to leave us a review? Your positive feedback motivates our team and helps us continue providing great service.', 'backwpup' ); ?></p>
	<p class="notice-actions">
	<a class="doubleLink" id="backwpup_notice_evaluate_review" href="https://wordpress.org/support/plugin/backwpup/reviews/?rate=5#new-post" hrefbis="<?php echo esc_url( $bind->dismissurl ); ?>">
		<?php esc_html_e( 'Sure, happy to!', 'backwpup' ); ?>
	</a><br />
	<a id="backwpup_notice_evaluate_later" href="<?php echo esc_url( $bind->tempdissmissurl ); ?>">
		<?php esc_html_e( 'Nope, maybe later', 'backwpup' ); ?>
	</a>
	</p>
</div>
<div id="backwpup_notice_evaluate_step_issue">
	<p><?php esc_html_e( 'We’re sorry to hear that. How can we assist you in resolving these issues?', 'backwpup' ); ?></p>
	<p class="notice-actions">
	<a class="doubleLink" id="backwpup_notice_evaluate_support" href="https://wordpress.org/support/plugin/backwpup/#new-topic-0" hrefbis="<?php echo esc_url( $bind->tempdissmissurl ); ?>">
		<?php esc_html_e( 'I need your help!', 'backwpup' ); ?>
	</a><br />
	<a class="doubleLink" id="backwpup_notice_evaluate_feedback" href="https://backwpup.com/contact/" hrefbis="<?php echo esc_url( $bind->tempdissmissurl ); ?>">
		<?php esc_html_e( 'I have feedback', 'backwpup' ); ?>
	</a><br />
	<a id="backwpup_notice_evaluate_feedback" href="<?php echo esc_url( $bind->tempdissmissurl ); ?>">
		<?php esc_html_e( 'Not now, thanks', 'backwpup' ); ?>
	</a>
	</p>
</div>
<div id="backwpup_notice_evaluate_step_thanks">
	<p><?php esc_html_e( 'Thanks for your rating! We appreciate your feedback and support.', 'backwpup' ); ?></p>
</div>