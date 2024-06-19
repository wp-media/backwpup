<div class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width">
	<h3 class="hndle"><span><?php esc_html_e( 'Database Settings', 'backwpup' ); ?></span></h3>
	<div class="inside">
		<p><?php esc_html_e( 'Before the actually restore can take place you need to tell us some information about your database. Probably, these information are the same as your WordPress database. The restore will overwrite all information within this database. So make sure you choose the correct database.', 'backwpup' ); ?></p>
		<p>
		<?php
		echo wp_kses(
			__(
				'You can use the <strong>Test Connection</strong> button below to test, you guessed it, your connection to the database.
				If everything is fine a green message box will appear. Otherwise, a red message box will tell you more about the problem.
				You can only continue when a working connection to the database could be established.',
				'backwpup'
			),
			[ 'strong' => [] ]
		);
		?>
		</p>
		<p>
		<?php
		echo wp_kses(
			__( 'Having trouble getting your database connection settings? <a href="https://backwpup.com/docs/restore-database-connection-settings/" target="_blank">Go to documentation</a> to get more information.', 'backwpup' ),
			[
				'a' => [
					'href'   => true,
					'target' => true,
				],
			]
		);
		?>
		</p>
	</div>
</div>
