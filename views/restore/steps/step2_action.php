<?php /** @var \stdClass $bind */ ?>
<div
	id="restore_step"
	class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width action"
	data-nonce="<?php echo esc_attr( $bind->nonce ); ?>">

	<?php
	if ( $bind->upload_is_archive ) {
		?>
		<h3 class="hndle">
			<span>
				<?php esc_html_e( 'Choose your restore strategy.', 'backwpup' ); ?>
			</span>
		</h3>

		<button
			class="button button-primary button-primary-bwp restore-select-strategy step-loader"
			data-strategy="complete restore"
			data-next-step="3">
			<?php esc_html_e( 'Full Restore', 'backwpup' ); ?>
		</button>
		<button
			class="button button-primary button-primary-bwp restore-select-strategy step-loader"
			data-strategy="db only restore"
			data-next-step="3">
			<?php esc_html_e( 'Database Only', 'backwpup' ); ?>
		</button>

	<?php } elseif ( $bind->upload_is_sql ) { ?>
		<h4><?php esc_html_e( 'Restore Database.', 'backwpup' ); ?></h4>

		<button
			class="button button-primary button-primary-bwp step-loader"
			data-strategy="db only restore"
			data-next-step="3">
			<?php esc_html_e( 'Continue', 'backwup' ); ?>
		</button>

	<?php } else { ?>
		<h4 class='red'>
			<?php
			esc_html_e(
				'There seems to be a problem with the archive. It is neither an archive nor a SQL file. Try again and repeat the upload.',
				'backwpup'
			);
			?>
		</h4>
	<?php } ?>

</div>
