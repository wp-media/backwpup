<?php
use BackWPup\Utils\BackWPupHelpers;
use WPMedia\BackWPup\Adapters\OptionAdapter;

/**
 * @var int $job_id ID of the job we are retrieving the frequency settings for.
 */
BackWPupHelpers::component("closable-heading", [
  'title' => __("Job Format Settings", 'backwpup'),
  'type' => 'sidebar'
]);

$optionAdapter = new OptionAdapter();

if ( ! isset ( $job_id ) ) {
  return;
}

$archiveFormat = BackWPup_Option::get($job_id, 'archiveformat', $optionAdapter->defaults_job('archiveformat'));
$archiveNameNoHash = BackWPup_Option::get($job_id, 'archivenamenohash', $optionAdapter->defaults_job('archivenamenohash'));
$hash = BackWPup_Option::get_generated_hash( $job_id );
$archiveNamePreview = str_replace( '%hash%', $hash, BackWPup_Job::sanitize_file_name( BackWPup_Option::substitute_date_vars( $archiveNameNoHash ) ) );

BackWPupHelpers::component("containers/scrollable-start", ["gap_size" => "small"]);

BackWPupHelpers::component("form/text", [
	"label" => __("Archive name", 'backwpup'),
	'name' => 'archivename',
	"value" => $archiveNameNoHash,
	"required" => true,
  "maxlength" => 200,
  "trigger" => "format-job-name",
]);
?>
<div class="js-backwpup-format-job-name-no-hash" style="display: none;">
  <?php
  BackWPupHelpers::component( 'alerts/info', [
    'type'    => 'danger',
    'font'    => 'small',
    'content' => __( 'In order for backup history to work, %hash% must be included anywhere in the archive name.', 'backwpup' ),
  ]);
  ?>
</div>
<?php

BackWPupHelpers::children("sidebar/parts/archive-format-selector", false, [
  "label" => __("Archive format", 'backwpup'),
  "archiveformat" => $archiveFormat,
]);
?>
  <div class="">
    <p class="mt-2 pl-3 pr-3">
      <?php _e( 'Archive name preview:', 'backwpup' ); ?>
      <span class="break-all font-bold">
        <span class="js-backwpup-format-archive-name" data-hash="<?php esc_attr_e( $hash ); ?>"><?php esc_attr_e( $archiveNamePreview ); ?></span>_<?php echo BackWPup_Job::sanitize_file_name(implode( '-', BackWPup_Option::get($job_id, 'type') ) ); ?><span class="js-backwpup-format-archive-name-format"><?php esc_attr_e( $archiveFormat ) ?></span>
      </span>
    </p>
    <p class="mt-3 underline pl-3 pr-3">
      <a class="underline" href="https://backwpup.com/docs/what-placeholders-can-i-use-in-archive-names-and-what-do-they-mean/" target="_blank"><?php _e( 'What do these placeholders mean?', 'backwpup' ); ?></a>
    </p>
  </div>
<?php

BackWPupHelpers::component("containers/scrollable-end");

BackWPupHelpers::component("form/hidden", ["identifier" => 'job_id', "name" => "job_id", "value" => $job_id]);

BackWPupHelpers::component("form/button", [
  "type" => "primary",
  "label" => __("Save settings", 'backwpup'),
  "full_width" => true,
  "class" => "mt-4 save_job_format",
  "identifier" => 'save-job-format',
]);
?>