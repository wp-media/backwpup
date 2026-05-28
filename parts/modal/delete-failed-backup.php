<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$backup_id = isset( $backup_id ) ? (int) $backup_id : 0;

$is_aborted = false;
if ( $backup_id > 0 ) {
	$container = wpm_apply_filters_typed( '?object', 'backwpup_container', null );
	if ( $container ) {
		$database   = $container->get( 'backwpup_database' );
		$backup_row = $database ? $database->get_backup_row_by_id( $backup_id ) : null;
		if ( $backup_row && 'aborted' === ( $backup_row->status ?? '' ) ) {
			$is_aborted = true;
		}
	}
}

BackWPupHelpers::component(
	"closable-heading",
	[
		'title' => $is_aborted ? __( 'Delete Aborted Backup Entry', 'backwpup' ) : __( 'Delete Failed Backup Entry', 'backwpup' ),
		'type'  => 'modal',
	]
);
?>

<?php
BackWPupHelpers::component(
	"alerts/info",
	[
		"type"    => "alert",
		"content" => $is_aborted
			? __( 'This removes the aborted entry from the backup history. Any partial backup file will also be deleted.', 'backwpup' )
			: __( 'This removes the failed entry from the backup history. No backup files will be deleted.', 'backwpup' ),
	]
);
?>

<footer class="flex flex-col gap-2">
	<?php
	BackWPupHelpers::component(
		"form/button",
		[
			"type"       => "primary",
			"label"      => __( 'Delete Entry', 'backwpup' ),
			"full_width" => true,
			"trigger"    => "delete-failed-backup",
			"data"       => [
				"backup-id" => $backup_id,
			],
		]
	);
	?>

	<?php
	BackWPupHelpers::component(
		"form/button",
		[
			"type"       => "secondary",
			"label"      => __( 'Cancel', 'backwpup' ),
			"full_width" => true,
			"trigger"    => "close-modal",
		]
	);
	?>
</footer>
