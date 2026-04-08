<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$backup_id = isset( $backup_id ) ? (int) $backup_id : 0;
$job_id    = isset( $job_id ) ? (int) $job_id : 0;

$backup_row     = null;
$error_message  = '';
$known_reasons  = [
	__( 'not enough storage', 'backwpup' ),
	__( 'incorrect login', 'backwpup' ),
];
$logfile        = '';
$log_filename   = '';
$log_lines      = [];
$log_truncated  = false;
$download_url   = '';
$view_url       = '';

$container = wpm_apply_filters_typed( '?object', 'backwpup_container', null );
if ( $backup_id > 0 && $container ) {
	$database = $container->get( 'backwpup_database' );
	$backup_row = $database ? $database->get_backup_row_by_id( $backup_id ) : null;
}

if ( $backup_row ) {
	$error_message = (string) ( $backup_row->error_message ?? '' );
	$logfile       = (string) ( $backup_row->logfile ?? '' );
}

if ( '' !== $error_message && in_array( $error_message, $known_reasons, true ) ) {
	/* translators: %s: failure reason. */
	$error_message = sprintf( __( 'Backup failed – %s', 'backwpup' ), $error_message );
} else {
	$error_message = __( 'Backup failed', 'backwpup' );
}

// Track log opened event for failed backup if backup_id and job_id are available.
// Prevent tracking if either backup_id or job_id is missing to avoid sending incomplete data to Mixpanel or on page load.
if ( 0 !== $backup_id && 0 !== $job_id ) {
	do_action( 'backwpup_track_log_opened', $error_message, $backup_id, $job_id, false );
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
	&&
	current_user_can( 'backwpup_logs' )
) {
	$view_url = admin_url(
		'admin-ajax.php?action=backwpup_view_log&log=' .
		rawurlencode( $log_name ) .
		'&_ajax_nonce=' .
		wp_create_nonce( 'view-log_' . $log_name ) .
		'&TB_iframe=true&width=640&height=440'
	);
}

$read_log_excerpt = static function ( string $logfile_path, int $max_lines, bool &$truncated ): array {
	$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
	$log_folder = BackWPup_File::get_absolute_path( $log_folder );
	$log_folder = untrailingslashit( $log_folder );

	if ( empty( $logfile_path ) || empty( $log_folder ) ) {
		return [];
	}

	$filename = basename( $logfile_path );
	$base     = $log_folder . '/' . $filename;
	$path     = null;

	if ( is_readable( $base ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
		$path = $base;
	} elseif ( substr( $base, -5 ) !== '.html' && is_readable( $base . '.html' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
		$path = $base . '.html';
	} elseif ( substr( $base, -8 ) !== '.html.gz' && is_readable( $base . '.html.gz' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
		$path = 'compress.zlib://' . $base . '.html.gz';
	}

	if ( null === $path ) {
		return [];
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$raw = file_get_contents( $path );
	if ( false === $raw ) {
		return [];
	}

	$raw = wp_strip_all_tags( $raw );
	$raw = html_entity_decode( $raw );
	$raw = str_replace( "\r\n", "\n", $raw );

	$lines = [];
	foreach ( explode( "\n", $raw ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$lines[] = $line;
		}
	}

	if ( count( $lines ) > $max_lines ) {
		$lines     = array_slice( $lines, -$max_lines );
		$truncated = true;
	}

	return $lines;
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
	"closable-heading",
	[
		'title' => __( 'Failed Backup Details', 'backwpup' ),
		'type'  => 'modal',
	]
);
?>

<?php
BackWPupHelpers::component(
	"alerts/info",
	[
		"type"    => "danger",
		"content" => esc_html( $error_message ),
    "font" => "xs"
	]
);
?>

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
