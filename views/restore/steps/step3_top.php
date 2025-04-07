<div class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width">
	<h3 class="hndle"><span><?php esc_html_e( 'Database Settings', 'backwpup' ); ?></span></h3>
	<div class="inside">
		<p><?php esc_html_e( 'The backup will be restored in the database of your choice. Proceed with caution as it will overwrite all information within the database. We provide you a way to test the database connection beforehand.', 'backwpup' ); ?></p>
		<p>
		<?php
		printf(
			// translators: %1$s and %2$s are the opening and closing <a> tags, respectively.
			esc_html__( 'Having trouble getting your database connection settings? %1$sGo to documentation%2$s to get more information.', 'backwpup' ),
			'<a href="https://backwpup.com/docs/restore-database-connection-settings/" target="_blank">',
			'</a>'
		);
		?>
		</p>
	</div>
</div>
