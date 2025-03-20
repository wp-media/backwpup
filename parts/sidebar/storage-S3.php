<?php
use BackWPup\Utils\BackWPupHelpers;
$job_id = $job_id ?? null;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Amazon S3 Settings", 'backwpup'),
  'type' => 'sidebar'
]);
$selectedOptions = null;
if (null === $job_id) {
  $s3dir = trailingslashit(sanitize_title_with_dashes(get_bloginfo('name')));
  $s3maxbackups = 3;
} else {
  $selectedOptions = BackWPup_S3_Destination::fromJobId($job_id);
  $s3dir = esc_attr(BackWPup_Option::get($job_id, 's3dir',trailingslashit(sanitize_title_with_dashes(get_bloginfo('name')))));
  $s3maxbackups = esc_attr(BackWPup_Option::get($job_id, 's3maxbackups',3));
}
$s3Options = BackWPup_S3_Destination::options();
$s3regions =array_combine(array_keys($s3Options), array_column($s3Options, 'label'));
$s3 = BackWPup::get_destination("s3");
$s3->edit_inline_js();
?>

<?php if (isset($is_in_form) && ( false === $is_in_form || 'false' === $is_in_form )) : ?>
  <p>
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "link",
      "label" => __("Back to Storages", 'backwpup'),
      "icon_name" => "arrow-left",
      "icon_position" => "before",
      "trigger" => "load-and-open-sidebar",
      "display" => "storages",
      "data"		=> ['job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'sidebar/storages',  ]
    ]);
    ?>
  </p>
<?php endif; ?>

<?php BackWPupHelpers::component("containers/scrollable-start"); ?>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("S3 Service", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("heading", [
      "level" => 3,
      "font" => "xs",
      "title" => __("Select a S3 Service", 'backwpup'),
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/select", [
      "name" => "s3region",
			"identifier" => "s3region",
      "label" => "Select Field",
      "withEmpty" => false,
      "value" => esc_attr(BackWPup_Option::get($job_id, 's3region', "")),
      "options" => $s3regions,
    ]);
    ?>

    <p class="my-2 text-center text-sm"><?php _e("OR", 'backwpup'); ?></p>

    <?php
    BackWPupHelpers::component("heading", [
      "level" => 3,
      "font" => "xs",
      "title" => __("a S3 Server URL", 'backwpup'),
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "s3base_url",
      "identifier" => "s3base_url",
      "label" => __("Endpoint", 'backwpup'),
      "tooltip" => "Leave it empty to use a destination from S3 service list",
			"value" =>  esc_attr(
					BackWPup_Option::get($job_id, 's3base_url', "")
			)
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "s3base_region",
      "identifier" => "s3base_region",
      "label" => __("Region", 'backwpup'),
      "tooltip" => 'Specify S3 region like "us-west-1"',
			"value" =>  esc_attr(
				BackWPup_Option::get($job_id, 's3base_region', "")
			)
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/checkbox", [
      "name" => "s3base_multipart",
      "identifier" => "s3base_multipart",
      "label" => __("Destination supports multipart", 'backwpup'),
      "checked" => (bool)esc_attr(BackWPup_Option::get($job_id, 's3base_multipart', "1")),
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/checkbox", [
      "name" => "s3base_pathstylebucket",
      "identifier" => "s3base_pathstylebucket",
      "label" => __("Destination provides only Pathstyle buckets", 'backwpup'),
      "checked" => (bool)esc_attr(BackWPup_Option::get($job_id, 's3base_pathstylebucket', "1")),
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "s3base_version",
      "identifier" => "s3base_version",
      "label" => __("Version", 'backwpup'),
      "tooltip" => __('The S3 version for the API like "2006-03-01", default "latest"', 'backwpup'),
			"value" => esc_attr(
				BackWPup_Option::get($job_id, 's3base_version', "")
			)
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "s3base_signature",
      "identifier" => "s3base_signature",
      "label" => __("Signature", 'backwpup'),
      "tooltip" => __('The S3 signature version like "v4", default "latest"', 'backwpup'),
			"value" => esc_attr( BackWPup_Option::get($job_id, 's3base_signature', ""))
    ]);
    ?>

  </div>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("S3 Access Keys", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "s3accesskey",
      "identifier" => "s3accesskey",
      "label" => __("Access Key", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($job_id, 's3accesskey')),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "s3secretkey",
      "identifier" => "s3secretkey",
      "label" => __("Secret Key", 'backwpup'),
			"type" => "password",
      "value" => esc_attr(BackWPup_Encryption::decrypt(BackWPup_Option::get(
		  $job_id,
		  's3secretkey'
	  ))),
      "required" => true,
    ]);
    ?>
  </div>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("S3 Bucket", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

	<div id="s3bucketContainer">
		<?php
			$s3->edit_ajax([
				's3accesskey' => BackWPup_Option::get($job_id, 's3accesskey'),
				's3secretkey' => BackWPup_Option::get($job_id, 's3secretkey'),
				's3bucketselected' => BackWPup_Option::get($job_id, 's3bucket'),
				's3region' => BackWPup_Option::get($job_id, 's3region'),
				's3base_url' => BackWPup_Option::get($job_id, 's3base_url'),
				's3base_region' => BackWPup_Option::get($job_id, 's3base_region'),
				's3base_multipart' => BackWPup_Option::get(
					$job_id,
					's3base_multipart'
				),
				's3base_pathstylebucket' => BackWPup_Option::get(
					$job_id,
					's3base_pathstylebucket'
				),
				's3base_version' => BackWPup_Option::get($job_id, 's3base_version'),
				's3base_signature' => BackWPup_Option::get(
					$job_id,
					's3base_signature'
				),
			]);
		?>
	</div>

	<p class="my-2 text-center text-sm"><?php _e("OR", 'backwpup'); ?></p>

  <?php
  BackWPupHelpers::component("form/text", [
    "name" => "s3newbucket",
    "identifier" => "s3newbucket",
    "label" => __("Create a new Bucket", 'backwpup'),
    "value" => "",
	  "tooltip" => __('The bucket name must be alphanumeric characters only and in lowercase.', 'backwpup'),
    "required" => false,
  ]);
  ?>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("S3 Backup Settings", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "s3dir",
      "identifier" => "s3dir",
      "label" => __("Folder to store files in", 'backwpup'),
      "value" => $s3dir,
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "s3maxbackups",
      "identifier" => "s3maxbackups",
      "type" => "number",
      "label" => __("Max backups to retain", 'backwpup'),
			"value" => $s3maxbackups,
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("alerts/info", [
      "type" => "alert",
      "font" => "xs",
      "content" => __("When this limit is exceeded, the oldest backup will be deleted.", 'backwpup'),
    ]);
    ?>
  </div>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Amazon Specific Settings", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/select", [
      "name" => "s3storageclass",
			"identifier" => "s3storageclass",
      "label" => __("Amazon: Storage Class", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($job_id, 's3storageclass',"")),
      "options" => [
        "STANDARD" => __('Standard', 'backwpup'),
        "STANDARD_IA" => __('Standard-Infrequent Access', 'backwpup'),
        "ONEZONE_IA" => __('One Zone-Infrequent Access', 'backwpup'),
        "REDUCED_REDUNDANCY" => __('Reduced Redundancy', 'backwpup'),
        "INTELLIGENT_TIERING" => __('Intelligent-Tiering', 'backwpup'),
        "GLACIER_IR" => __('Glacier Instant Retrieval', 'backwpup'),
      ],
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/checkbox", [
      "name" => "s3ssencrypt",
      "identifier" => "s3ssencrypt",
      "label" => __("Save files encrypted (AES256) on server", 'backwpup'),
			"value" => "AES256",
      "checked" => (bool)esc_attr(BackWPup_Option::get($job_id, 's3ssencrypt', "1")),
    ]);
    ?>
  </div>
</div>

<?php BackWPupHelpers::component("containers/scrollable-end"); ?>

<?php
BackWPupHelpers::component("form/button", [
  "type" => "primary",
  "label" => __("Save & Test connection", 'backwpup'),
  "full_width" => true,
  "trigger" => "test-S3-storage",
  "data" => [
    "storage" => "amazon-s3",
    "job-id" => $job_id,
  ],
]);
?>