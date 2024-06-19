<?php /** @var \stdClass $bind */ ?>
<div
	id="restore_step"
	class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width action"
	data-nonce="<?php echo esc_attr( $bind->nonce ); ?>">

	<h3 class="hndle">
		<span>
			<?php esc_html_e( 'Restore Progress.', 'backwpup' ); ?>
		</span>
	</h3>

	<div class="restore-progress-container">
		<div id="restore_progress"></div>
	</div>

	<button
		id="start-restore"
		class="button button-primary button-primary-bwp">
		<?php esc_html_e( 'Start', 'backwpup' ); ?>
	</button>

</div>
