<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$backup_id = isset( $backup_id ) ? (int) $backup_id : 0;
$job_id    = isset( $job_id ) ? (int) $job_id : 0;

$can_view_logs = current_user_can( 'backwpup_logs' );

if ( ! $can_view_logs ) {
	BackWPupHelpers::component(
		'closable-heading',
		[
			'title' => __( 'Backup Log', 'backwpup' ),
			'type'  => 'modal',
		]
	);
	?>
	<p class="text-sm text-grey-700">
		<?php esc_html_e( 'You are not allowed to view logs for this backup.', 'backwpup' ); ?>
	</p>
	<?php
	return;
}

$backup_row      = null;
$backup_status   = '';
$is_failed       = false;
$is_aborted      = false;
$destination     = '';
$error_code      = '';
$error_message   = '';
$display_message = __( 'Backup failed', 'backwpup' );
$next_step       = '';
$known_reasons   = [
	__( 'not enough storage', 'backwpup' ),
	__( 'incorrect login', 'backwpup' ),
];
$logfile         = '';
$log_filename    = '';
$log_lines       = [];
$log_truncated   = false;
$download_url    = '';
$view_url        = '';
$log_facade      = null;

$container = wpm_apply_filters_typed( '?object', 'backwpup_container', null );
if ( $backup_id > 0 && $container ) {
	$database   = $container->get( 'backwpup_database' );
	$backup_row = $database ? $database->get_backup_row_by_id( $backup_id ) : null;
}

if ( $backup_row ) {
	$backup_status = (string) ( $backup_row->status ?? '' );
	$is_failed     = 'failed' === $backup_status;
	$is_aborted    = 'aborted' === $backup_status;
	if ( $is_failed ) {
		$destination   = (string) ( $backup_row->destination ?? '' );
		$error_code    = (string) ( $backup_row->error_code ?? '' );
		$error_message = (string) ( $backup_row->error_message ?? '' );
	}
	$logfile = (string) ( $backup_row->logfile ?? '' );
}

if ( $is_aborted ) {
	$display_message = __( 'Aborted by user', 'backwpup' );
}

if ( $is_failed && 'FTP' === $destination && $job_id > 0 ) {
	$job = BackWPup_Option::get_job( $job_id );

	if ( is_array( $job ) && ! empty( $job['ftpssh'] ) ) {
		$destination = 'SFTP';
	}
}

if ( $is_failed && $container && '' !== $error_code ) {
	$display_details_resolver = $container->get( 'failure_display_details_resolver' );

	if ( $display_details_resolver ) {
		$display_details = $display_details_resolver->resolve( $error_code, $destination );

		if ( ! empty( $display_details['summary'] ) ) {
			$display_message = (string) $display_details['summary'];
		}

		if ( ! empty( $display_details['next_step'] ) ) {
			$next_step = (string) $display_details['next_step'];
		}
	}
}

if ( $is_failed && __( 'Backup failed', 'backwpup' ) === $display_message && '' !== $error_message ) {
	$display_message = sprintf(
		/* translators: %s: failure reason. */
		__( 'Backup failed – %s', 'backwpup' ),
		$error_message
	);
}

// Track log opened event when backup and job IDs are available.
if ( 0 !== $backup_id && 0 !== $job_id ) {
	do_action( 'backwpup_track_log_opened', ( $is_failed || $is_aborted ) ? $display_message : '', $backup_id, $job_id, ! $is_failed && ! $is_aborted );
}

$log_name = '';
if ( '' !== $logfile ) {
	$log_filename = basename( $logfile );
	$log_name     = str_replace( [ '.html', '.gz' ], '', $log_filename );
}

if (
	'' !== $log_name
	&&
	false !== strpos( $log_name, 'backwpup_log_' )
) {
	$view_url = admin_url(
		'admin-ajax.php?action=backwpup_view_log&log=' .
		rawurlencode( $log_name ) .
		'&_ajax_nonce=' .
		wp_create_nonce( 'view-log_' . $log_name ) .
		'&TB_iframe=true&width=640&height=440'
	);
}

$container = wpm_apply_filters_typed( '?object', 'backwpup_container', null );
if ( is_object( $container ) && method_exists( $container, 'has' ) && $container->has( 'log_facade' ) ) {
	$log_facade = $container->get( 'log_facade' );
}
if ( ! is_object( $log_facade ) || ! method_exists( $log_facade, 'read_excerpt' ) ) {
	$log_facade = new \WPMedia\BackWPup\Log\LogFacade();
}

$read_log_excerpt = static function ( string $logfile_path, int $max_lines, bool &$truncated ) use ( $log_facade ): array {
	return $log_facade->read_excerpt( $logfile_path, $max_lines, $truncated );
};

if ( '' !== $logfile && '' === $view_url ) {
	$log_lines = $read_log_excerpt( $logfile, 200, $log_truncated );
}

if ( '' !== $log_filename ) {
	$download_url = wp_nonce_url(
		network_admin_url( 'admin.php?page=backwpuplogs&action=download&file=' . rawurlencode( $log_filename ) ),
		'download_backwpup_logs',
		'download_backwpup_logs'
	);
}

BackWPupHelpers::component(
	'closable-heading',
	[
		'title' => $is_aborted ? __( 'Aborted Backup', 'backwpup' ) : ( $is_failed ? __( 'Failed Backup Details', 'backwpup' ) : __( 'Backup Log', 'backwpup' ) ),
		'type'  => 'modal',
	]
);
?>

<?php if ( $is_failed || $is_aborted ) : ?>
	<?php
	$alert_args = [
		'type'    => $is_aborted ? 'warning' : 'danger',
		'content' => esc_html( $display_message ),
		'font'    => 'xs',
	];

	if ( '' !== $next_step ) {
		$alert_args['content2'] = esc_html( $next_step );
	}

	BackWPupHelpers::component(
		'alerts/info',
		$alert_args
	);
	?>
<?php endif; ?>

<?php if ( '' !== $view_url ) : ?>
	<div
		class="border border-grey-200 rounded overflow-hidden w-full"
		style="height:60vh; min-height:320px; max-height:70vh;"
	>
		<iframe
			src="<?php echo esc_url( $view_url ); ?>"
			title="<?php esc_attr_e( 'Log details', 'backwpup' ); ?>"
			style="width:100%; height:100%;"
		></iframe>
	</div>
<?php elseif ( ! empty( $log_lines ) ) : ?>
	<div class="bg-grey-100 rounded p-4 max-h-64 overflow-auto">
		<pre class="text-xs text-grey-800 whitespace-pre-wrap"><?php echo esc_html( implode( "\n", $log_lines ) ); ?></pre>
	</div>
	<?php if ( $log_truncated ) : ?>
		<p class="text-xs text-grey-700">
			<?php esc_html_e( 'Showing the last 200 lines of the log.', 'backwpup' ); ?>
		</p>
	<?php endif; ?>
<?php else : ?>
	<p class="text-sm text-grey-700">
		<?php esc_html_e( 'Log content is not available for this backup.', 'backwpup' ); ?>
	</p>
<?php endif; ?>
