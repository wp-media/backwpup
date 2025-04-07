<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $job_id The job ID.
 * @var array $excludedTables Optional. The excluded tables.
 * @var int $second_job_id ID of the second job we are retrieving the frequency settings for. Only avaialble during onboarding.
 */

if ( ! isset( $job_id ) && get_site_option( 'backwpup_onboarding', false ) ) {
	$job_id = $second_job_id;
}

if ( ! isset( $job_id ) ) {
	return;
}

BackWPupHelpers::component("closable-heading", [
  'title' => __("Select Tables", 'backwpup'),
  'type' => 'sidebar'
]);

/** @var wpdb $wpdb */
global $wpdb;
$dbtables = $wpdb->get_results('SHOW TABLES FROM `' . DB_NAME . '`', ARRAY_N);
$tables = [];
$defaultexcludedtables = [];
foreach ($dbtables as $dbtable) {
  $tables[] = $dbtable[0];
  if (!strstr((string) $dbtable[0], $wpdb->prefix)) {
      $defaultexcludedtables[] = $dbtable[0];
  }
}

$excludedTables = BackWPup_Option::get($job_id, 'dbdumpexclude', $defaultexcludedtables);

?>

<?php BackWPupHelpers::component("containers/scrollable-start", ["gap_size" => "small"]); ?>

  <p class="text-base"><?php _e("Select tables you want to backup", 'backwpup'); ?></p>

  <div class="flex flex-col gap-4 rounded-lg p-6 bg-grey-100">
    <?php
    BackWPupHelpers::component("form/search", [
      "name" => "filter_tables",
      "placeholder" => __("Search…", 'backwpup'),
      "trigger" => "filter-tables",
    ]);
    ?>

    <div class="js-backwpup-tables-list flex flex-col gap-4">
      <?php
      foreach ($tables as $table) {
        $checked = !in_array($table, $excludedTables);
        BackWPupHelpers::component("form/checkbox", [
          "name" => "tabledb[]",
          "value" => $table,
          "checked" => $checked,
          "label" => $table,
        ]);
      }
      ?>
    </div>


  </div>
  <?php
  BackWPupHelpers::component("form/hidden", [
      "name" => "dbdumpfile",
      "value" => "local",
  ]);
  BackWPupHelpers::component("form/hidden", [
      "name" => "dbdumpwpdbsettings",
      "value" => true,
  ]);
  BackWPupHelpers::component("form/hidden", [
      "name" => "dbdumpfilecompression",
      "value" => "",
  ]);
  BackWPupHelpers::component("form/hidden", [
      "name" => "job_id",
      "value" => $job_id,
  ]);
 
  ?>

  <?php BackWPupHelpers::component("containers/scrollable-end"); ?>

  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Save settings", 'backwpup'),
    "full_width" => true,
    "trigger" => "close-sidebar",
    "identifier" => "save-excluded-tables"
  ]);
  ?>