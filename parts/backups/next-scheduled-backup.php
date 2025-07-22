<?php
use BackWPup\Utils\BackWPupHelpers;

BackWPupHelpers::component("heading", [
	"level" => 1,
	"title" => __("Next Scheduled Backups", 'backwpup'),
	"class" => "max-md:justify-center"
]);

$jobs = BackWPup_Job::get_jobs();
?>

<div id="backwpup-loading-overlay-template" class="hidden">
	<div class="backwpup-loading-overlay">
		<?php
			BackWPupHelpers::component("icon", [
				"name" => 'loading',
				"size" => 'xl',
			]);
		?>
	</div>
</div>

<div class="mt-2 dynamic-cards-grid max-md:flex-col gap-4" id="backwup-next-scheduled-backups">
	<?php
	if ( ! empty( $jobs ) && is_array( $jobs ) ) {
		foreach ( $jobs as $job ) {
			if ( ! isset( $job['jobid'] ) || (isset($job['legacy']) && $job['legacy'] === true))  {
				continue;
			}

			// Skip temp jobs.
			if ( isset($job['tempjob']) && true === $job['tempjob'] ) {
				continue;
			}

            if ( isset($job['backup_now']) && true === $job['backup_now'] ) {
                continue;
            }

			BackWPupHelpers::component( 'job-item', [ 'job' => $job ] );
		}
	}

	BackWPupHelpers::component("next-scheduled-backup");
	?>
</div>

<div id="backwpup_dynamic_response_content" class="hidden">
	<?php BackWPupHelpers::component("next-scheduled-backup"); ?>
</div>