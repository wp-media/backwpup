<?php /** @var \stdClass $bind */ ?>
<div
	id="restore_step"
	class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width action"
	data-nonce="<?php echo esc_attr( $bind->nonce ); ?>">

	<div class="restore-progress-container">
		<div id="upload_progress"></div>
		<div class="progressbar">
			<div id="progressstep" class="bwpu-progress" style="width:0%;">0%</div>
		</div>
		<p id="onstep"></p>
	</div>

	<?php
	$view = new \Inpsyde\Restore\ViewLoader();
	$view->decrypt_key_input();
	?>

	<div id="drag-drop-area" class="drag-drop-area">
		<div class="drag-drop-inside">
			<input
				id="plupload-browse-button"
				class="button"
				type="button"
				value="<?php esc_attr_e( 'Select Archive', 'backwpup' ); ?>"/>
			<span class="separator"><?php esc_html_e( 'or', 'backwpup' ); ?></span>
			<span class="drop-text"><?php esc_html_e( 'Drop file here', 'backwpup' ); ?></span>
			<span>
				<i class="fa fa-info-circle"
					title="<?php esc_attr_e( 'Supported archive format zip,tar,tar.gz', 'backwpup' ); ?>">
				</i>
			</span>
		</div>
	</div>
</div>
