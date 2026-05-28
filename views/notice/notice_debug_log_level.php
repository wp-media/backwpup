<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$configured_count = wpm_apply_filters_typed( 'integer', 'backwpup_debug_log_count', 5 );
?>
<p class="notice-titre">⚠️ <?php esc_html_e( 'Debug Logging is Active', 'backwpup' ); ?></p>
<p>
	<?php
	printf(
		// Translators: %1$d = number of backup runs.
		esc_html__(
		'Debug logging is currently enabled for BackWPup. This mode is intended for troubleshooting purposes only and should not be left on during normal operation.
Be aware that log files may contain sensitive information, such as file paths, database details, and server configuration data. Make sure log files are stored securely and deleted once they are no longer needed.
Debug logging will automatically disable itself after %1$d backup runs. If your issue is resolved, it is recommended to turn it off manually right away.',
		'backwpup'
		),
		esc_html( $configured_count )
	);
	?>
</p>
