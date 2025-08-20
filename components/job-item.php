<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var array $job Job information
 */


$job_id            = $job['jobid'];
$is_cron_active    = BackWPup_Option::get( $job_id, 'activetype' );
$is_active         = ! empty( $is_cron_active );
$next_backup_label = __( 'No backup scheduled', 'backwpup' ); // Default label.
$tooltip_text      = __( 'Enable', 'backwpup' );
if ( $is_active ) {
	$cron_next = BackWPup_Cron::cron_next( BackWPup_Option::get( $job_id, 'cron' ) );

	$next_backup_label = sprintf(
		__( '%1$s at %2$s', 'backwpup' ),
		wp_date( get_option( 'date_format' ), $cron_next ),
		wp_date( get_option( 'time_format' ), $cron_next )
	);
	$tooltip_text      = __( 'Disable', 'backwpup' );
}


$job_type  = $job['type'];
$class     = 'backwpup-btn-mixed';
$select    = 'settings-data-type';
$data_type = 'mixed';
$icon      = 'mixed';
if ( BackWPup_JobTypes::$type_job_database === $job_type ) {
	$data_type = 'database';
	$icon      = 'database';
} elseif ( BackWPup_JobTypes::$type_job_files === $job_type ) {
	$data_type = 'files';
	$icon      = 'file-alt';
}
?>
<div class="flex-1 p-8 bg-white rounded-lg flex flex-col relative backwpup-job-card
backwpup-job-<?php echo esc_attr( $data_type ); ?> h-[200px]" id="<?php echo esc_attr( 'backwpup-' . $job_id . '-options' ); ?>">

    <div class="mb-2 flex items-center gap-4">
		<?php
		BackWPupHelpers::component(
			'icon',
			[
				'name' => $icon,
				'size' => 'large',
			]
		);
		?>

        <div class="mt-[5px] w-[152px] flex flex-auto">
			<?php
			$job_title = trim( BackWPup_Option::get( $job_id, 'name' ) );

			BackWPupHelpers::component(
				'heading',
				[
					'level'    => 3,
					'title'    => ucfirst( $job_title ),
					'class'    => 'backwpup-job-title',
					'font'     => 'regular',
					'bold'     => 'font-bold',
					'flex'     => false,
					'truncate' => true,
				]
			);

			BackWPupHelpers::component(
				'tooltip',
				[
					'icon_name' => 'edit',
					'icon_size' => 'medium',
					'class'     => 'js-backwpup-load-and-open-sidebar cursor-pointer min-w-[21px] ml-3',
					'data'      => [
						'job-id'     => $job_id,
						'block-type' => 'children',
						'block-name' => 'sidebar/edit-title',
						'content'    => 'edit-title',
					],
					'content'   => __( 'Edit title', 'backwpup' ),
					'position'  => 'top',
				]
			);
			?>
        </div>
		<?php
		BackWPupHelpers::component(
			'tooltip',
			[
				'icon_name' => 'trash',
				'icon_size' => 'medium-2x',
				'class'     => 'js-backwpup-delete-job cursor-pointer',
				'data'      => [ 'job-id' => $job_id ],
				'content'   => __( 'Delete', 'backwpup' ),
				'position'  => 'top',
			]
		);

		BackWPupHelpers::component(
			'tooltip',
			[
				'content'                     => $tooltip_text,
				'tooltip_position'            => 'top',
				'tooltip_size'                => 'medium',
				'position'                    => 'top',
				'tooltip_surrounding_element' => 'div',
				'parent_classes'              => 'flex gap-2',
				'tooltip_component'           => [
					'component' => 'form/toggle',
					'args'      => [
						'name'       => "next_backup_$job_id",
						'trigger'    => 'toggle-job',
						'checked'    => $is_active,
						'remove_div' => true,
						'data'       => [ 'job-id' => $job_id ],
					],
				],
			]
		);
		?>
    </div>

    <div class="mt-2 mb-4 flex flex-row items-center gap-2">
		<span class="text-base label-scheduled">
			<?php echo $next_backup_label; ?>
		</span>
		<?php
		BackWPupHelpers::component(
			'form/button',
			[
				'type'             => 'icon-hover',
				'font_size'        => 'py-[14px] text-base gap-4',
				'label'            => '',
				'trigger'          => 'load-and-open-modal',
				'class'            => 'backwpup-btn-backup-job disabled:opacity-40 always-enabled',
				'display'          => 'backup-job',
				'data'             => [
					'job-id'     => $job_id,
					'block-type' => 'children',
					'block-name' => 'modal/backup-job',
				],
				'tooltip'          => __( 'Backup now', 'backwpup' ),
				'tooltip_position' => 'top',
				'tooltip_size'     => 'medium',
				'tooltip_icon'     => 'download',
			]
		);
		?>
    </div>

    <p class="flex items-center gap-4">
		<?php
		BackWPupHelpers::component(
			'form/button',
			[
				'type'     => 'link',
				'label'    => __( 'Data', 'backwpup' ),
				'class'    => $class,
				'trigger'  => 'load-and-open-sidebar',
				'display'  => $select,
				'disabled' => ! $is_active,
				'data'     => [
					'job-id'     => $job_id,
					'block-type' => 'children',
					'block-name' => 'sidebar/' . $select,
					'job-type'   => $data_type,
				],
			]
		);
		?>
        <span class="h-5 w-0 border-r border-primary-darker"></span>
		<?php
		BackWPupHelpers::component(
			'form/button',
			[
				'type'     => 'link',
				'label'    => __( 'Frequency', 'backwpup' ),
				'trigger'  => 'load-and-open-sidebar',
				'display'  => 'frequency',
				'disabled' => ! $is_active,
				'data'     => [
					'job-id'     => $job_id,
					'block-type' => 'children',
					'block-name' => 'sidebar/frequency',
				],
			]
		);
		?>
        <span class="h-5 w-0 border-r border-primary-darker"></span>
		<?php
		BackWPupHelpers::component(
			'form/button',
			[
				'type'     => 'link',
				'label'    => __( 'Storage', 'backwpup' ),
				'trigger'  => 'load-and-open-sidebar',
				'display'  => 'storages',
				'disabled' => ! $is_active,
				'data'     => [
					'job-id'     => $job_id,
					'block-type' => 'children',
					'block-name' => 'sidebar/storages',
				],
			]
		);
		?>
    </p>
</div>