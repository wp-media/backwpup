<div class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width">
	<h3 class="hndle"><span><?php esc_html_e( 'Select Strategy', 'backwpup' ); ?></span></h3>
	<div class="inside">
		<p><?php esc_html_e( 'This page provides two options you can choose from. Your decision affects the behavior of the restore process and decides which files are going to be restored.', 'backwpup' ); ?></p>
		<hr>
		<h3 class="mdl-card__title-text"><?php esc_html_e( 'Full Restore', 'backwpup' ); ?></h3>
		<p>
			<?php
			wp_kses(
				__(
					'This option restores everything. Your database as well as all files within the backup are restored. The backup usually holds files from your <code>wp-content/</code> directory.',
					'backwpup'
				),
				[ 'code' => [] ]
			);
			?>
		</p>

		<h3 class="mdl-card__title-text"><?php esc_html_e( 'Database Only', 'backwpup' ); ?></h3>
		<p><?php esc_html_e( 'The second option is a database only restore. Choosing this option will restore only the database dump. No files will be restored.', 'backwpup' ); ?></p>

		<hr>
		<p>
		<?php
		echo wp_kses(
			__( 'Note: <strong>Full Restore</strong> can only be done if everything is included in the zip file. If your zip file only includes a database export, then only a <strong>Database only</strong> restore can be performed.', 'backwpup' ),
			[ 'strong' => [] ]
		);
		?>
		</p>
	</div>
</div>
