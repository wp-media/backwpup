<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var array $job Job information
 */


$job_id = $job['jobid'];
$is_cron_active = BackWPup_Option::get($job_id, 'activetype');
$is_active = !empty($is_cron_active);
$next_backup_label = __("No backup scheduled", 'backwpup'); // Default label

if ( $is_active ) {
	$cron_next = BackWPup_Cron::cron_next(BackWPup_Option::get($job_id, 'cron'));

	$next_backup_label = sprintf(
		__( '%1$s at %2$s', 'backwpup' ),
		wp_date( get_option( 'date_format' ), $cron_next ),
		wp_date( get_option( 'time_format' ), $cron_next )
	);
}

$is_file = in_array( $job['type'], [BackWPup_JobTypes::$type_job_both, BackWPup_JobTypes::$type_job_files], true );
if ( $is_file ) {
	$job_type   = 'files';
	$select     = "select-files";
	$icon = 'file-alt';
} else {
	$job_type   = 'database';
	$select     = "select-tables";
	$icon = 'database';
}


?>
<div class="flex-1 p-8 bg-white rounded-lg flex flex-col relative backwpup-job-card backwpup-job-<?= $job_type ?> h-[200px]" id="<?= 'backwpup-'.$job_id.'-options' ?>">

	<div class="mb-2 flex items-center gap-4">
		<?php BackWPupHelpers::component("icon", ["name" => $icon, "size" => "large"]); ?>

		<div class="mt-[5px] w-[152px] flex flex-auto">
			<?php
			$title = trim( BackWPup_Option::get($job_id, 'name') );

			BackWPupHelpers::component("heading", [
				"level" => 3,
				"title" => ucfirst( $title ),
				"class" => 'backwpup-job-title',
				"font"  => 'regular',
				"bold"  => 'font-bold',
				"flex"  => false,
				"truncate" => true,
			]);

			BackWPupHelpers::component("icon", [
				"name" => 'edit',
				"size" => "medium",
				"class" => "js-backwpup-load-and-open-sidebar cursor-pointer min-w-[21px] ml-3",
				"data"      => [
					'job-id' => $job_id,
					'block-type' => 'children',
					'block-name' => 'sidebar/edit-title',
					'content' => 'edit-title',
				],
			]);
			?>
		</div>
		<?php
		BackWPupHelpers::component("icon", [
			"name" => 'trash',
			"size" => "medium-2x",
			"class" => "js-backwpup-delete-job cursor-pointer",
			"data" => ["job-id" => $job_id]
		]);

		BackWPupHelpers::component("form/toggle", [
			"name" => "next_backup_$job_id",
			"trigger" => "toggle-job",
			"checked" => $is_active,
			"data"    => ['job-id' => $job_id],
		]);
		?>
	</div>

	<div class="mt-2 mb-4 flex-auto">
		<p class="text-base label-scheduled"><?= $next_backup_label; ?></p>
	</div>

	<p class="flex items-center gap-4">
		<?php
		BackWPupHelpers::component("form/button", [
			"type" => "link",
			"label" => __('Data', 'backwpup'),
			"class" => $is_file ? 'backwpup-btn-select-files' : 'backwpup-btn-select-tables',
			"trigger" => "load-and-open-sidebar",
			"display" => $select,
			"disabled" => !$is_active,
			"data" => [ 'job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'sidebar/'.$select,  ],
		]);
		?>
		<span class="h-5 w-0 border-r border-primary-darker"></span>
		<?php
		BackWPupHelpers::component("form/button", [
			"type" => "link",
			"label" => __("Frequency", 'backwpup'),
			"trigger" => "load-and-open-sidebar",
			"display" => 'frequency',
			"disabled" => !$is_active,
			"data"    => [ 'job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'sidebar/frequency',  ],
		]);
		?>
		<span class="h-5 w-0 border-r border-primary-darker"></span>
		<?php
		BackWPupHelpers::component("form/button", [
			"type" => "link",
			"label" => __("Storage", 'backwpup'),
			"trigger" => "load-and-open-sidebar",
			"display" => "storages",
			"disabled" => !$is_active,
			"data"    => ['job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'sidebar/storages',  ]
		]);
		?>
	</p>
</div>