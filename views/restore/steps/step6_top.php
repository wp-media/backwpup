<?php
if ( ! $bind->errors ) { ?>
	<div class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width">
		<h3 class="hndle">
			<span><?php esc_html_e( 'Success', 'backwpup' ); ?>
			</span>
		</h3>
		<div class="inside">
			<p>
				<?php
				esc_html_e(
					'Your restore was successful and everything should be back to normal.',
					'backwpup'
				);
				?>
			</p>
		</div>
	</div>
<?php } ?>

<?php if ( $bind->errors ) { ?>
	<div class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width">
		<h3 class="hndle">
			<span>
				<?php esc_html_e( 'Restore Report', 'backwpup' ); ?>
			</span>
		</h3>
		<div class="inside">
			<?php
			esc_html_e(
				'Seems there was some error during the restore that need manual action.',
				'backwpup'
			);
			?>
		</div>
	</div>
	<?php
}
