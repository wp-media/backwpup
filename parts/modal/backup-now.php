<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component(
	'closable-heading',
	[
		'title' => __( 'Backup Now', 'backwpup' ),
		'type'  => 'modal',
	]
	);
?>

<?php
$info_content = [
	'component' => 'alerts/info',
	'args'      => [
		'type'    => 'info',
		'content' => __( 'Backup all your files and database in one click. <br />Your backup will be stored on your website’s server and you will be able to download it on your computer.', 'backwpup' ),
	],
];
$info_content = wpm_apply_filters_typed( 'array', 'backwpup_backup_now_modal_info_content', $info_content, 0 );
if ( isset( $info_content['component'], $info_content['args'] ) && $info_content ) {
	BackWPupHelpers::component( $info_content['component'], $info_content['args'] );
}
?>

<footer class="flex flex-col gap-2">
	<?php
	$button = [
		'component' => 'form/button',
		'args'      => [
			'type'       => 'primary',
			'label'      => __( 'Start', 'backwpup' ),
			'full_width' => true,
			'trigger'    => 'start-backup-now',
			'class'      => 'backwpup-button-backup',
		],
	];
	$button = wpm_apply_filters_typed( 'array', 'backwpup_backup_now_modal_button', $button, 0 );
	if ( isset( $button['component'], $button['args'] ) && $button ) {
		BackWPupHelpers::component( $button['component'], $button['args'] );
	}
	?>
</footer>