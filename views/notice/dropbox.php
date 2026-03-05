<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dropbox notice view.
 *
 * @var \Inpsyde\BackWPup\Notice\NoticeMessage $bind
 */
?>
<p><?php esc_html_e( 'You have one or more BackWPup jobs that need to reauthenticate with Dropbox.', 'backwpup' ); ?></p>
<p><?php esc_html_e( 'The Dropbox API is discontinuing long-lived access tokens. To conform to these new changes, we must implement the use of refresh tokens, which can only be fetched when you reauthenticate.', 'backwpup' ); ?></p>
<p><?php esc_html_e( 'Please visit each job below and reauthenticate your Dropbox connection.', 'backwpup' ); ?></p>

<ul>
	<?php foreach ( $bind->jobs as $job_id => $name ) : ?>
		<?php
		$job_url = wp_nonce_url(
			add_query_arg(
				[
					'page'  => 'backwpupeditjob',
					'tab'   => 'dest-dropbox',
					'jobid' => absint( $job_id ),
				],
				network_admin_url( 'admin.php' )
			),
			'edit-job'
		);
		?>
		<li>
			<a href="<?php echo esc_url( $job_url ); ?>">
				<?php echo esc_html( $name ); ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
