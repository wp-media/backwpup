<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $job_id The job ID.
 */

if ( ! isset( $job_id ) ) {
	return;
}
$name = BackWPup_Option::get( $job_id, 'name' );

BackWPupHelpers::component(
	'closable-heading',
	[
		'title' => __( 'Backup now: ', 'backwpup' ) . $name,
		'type'  => 'modal',
	]
	);
?>  
  
<?php
$info_content = [
	'component' => 'alerts/info',
	'args'      => [
		'type'    => 'info',
		'content' => __( 'Your backup will be created using the data and the storage location you selected for your scheduled backup.', 'backwpup' ),
	],
];
$info_content = wpm_apply_filters_typed( 'array', 'backwpup_backup_now_modal_info_content', $info_content, $job_id );
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
			'trigger'    => 'start-backup-job',
			'class'      => 'backwpup-start-backup-job',
			'data'       => [ 'job_id' => $job_id ],
		],
	];
	$button = wpm_apply_filters_typed( 'array', 'backwpup_backup_now_modal_button', $button, $job_id );
	if ( isset( $button['component'], $button['args'] ) && $button ) {
		BackWPupHelpers::component( $button['component'], $button['args'] );
	}
	?>
	</footer>