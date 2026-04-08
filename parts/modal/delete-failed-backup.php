<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$backup_id = isset( $backup_id ) ? (int) $backup_id : 0;

BackWPupHelpers::component(
	"closable-heading",
	[
		'title' => __( 'Delete Failed Backup Entry', 'backwpup' ),
		'type'  => 'modal',
	]
);
?>

<?php
BackWPupHelpers::component(
	"alerts/info",
	[
		"type"    => "alert",
		"content" => __( 'This removes the failed entry from the backup history. No backup files will be deleted.', 'backwpup' ),
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
