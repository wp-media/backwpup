<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( get_site_option( 'backwpup_onboarding', false ) ) {
  return;
}

BackWPupHelpers::component("closable-heading", [
  'title' => __("Logs Settings", 'backwpup'),
  'type' => 'sidebar'
]);
?>

<p>
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "link",
    "label" => __("Back to Advanced Settings", 'backwpup'),
    "icon_name" => "arrow-left",
    "icon_position" => "before",
    "trigger" => "open-sidebar",
    "display" => "advanced-settings",
  ]);
  ?>
</p>

<?php
  if (isset($is_in_form) && false === $is_in_form) {
    BackWPupHelpers::component("containers/form-start");
  }
?>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Logs settings", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>
  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "logfolder",
      "label" => __("Log file folder", 'backwpup'),
      "value" => get_site_option('backwpup_cfg_logfolder'),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "maxlogs",
      "type" => "number",
      "trigger" => "intonly",
      "label" => __("Maximum log files in folder", 'backwpup'),
      "value" => get_site_option('backwpup_cfg_maxlogs'),
      "min" => 0,
      "required" => true,
    ]);
    ?>

    <?php
    $loglevel = get_site_option('backwpup_cfg_loglevel', 'normal');
    BackWPupHelpers::component("form/select", [
      "name" => "loglevel",
      "label" => __("Logging level", 'backwpup'),
      "value" => $loglevel,
      "options" => [
        "normal_translated" => "Normal (translated)",
        "normal" => "Normal",
        "debug_translated" => "Debug (translated)",
        "debug" => "Debug (not translated)",
      ],
    ]);
    ?>

    <div class="js-backwpup-show-if-debug-log-active <?php if ( strpos($loglevel, 'debug') === false) : ?>hidden<?php endif; ?>">
        <?php
        $count = wpm_apply_filters_typed( 'integer', 'backwpup_debug_log_count', 5 );
        BackWPupHelpers::component('alerts/info', [
          'type' => 'alert',
          'font' => 'xs',
          'content' => sprintf(
                // translators: %1$d: number of backups with debug log active, %2$s: WP_DEBUG is enabled text.
                __('Debug logging provides detailed information for troubleshooting. Use with caution as it may contain sensitive data. It will automatically be disabled after <strong>%1$d</strong> backups. %2$s', 'backwpup' ),
                $count,
                defined('WP_DEBUG') && WP_DEBUG ? '<br />' . __('Because of <code>WP_DEBUG</code> is active, automatic disabling is inactive!', 'backwpup' ) : ''
            ),
        ]);
        ?>
    </div>
  </div>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Log notification", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "mailaddresslog",
      "type" => "email",
      "label" => __("Send log to email address", 'backwpup'),
      "value" => get_site_option('backwpup_cfg_mailaddresslog'),
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "mailaddresssenderlog",
      "type" => "email",
      "label" => __("Email from field", 'backwpup'),
      "value" => get_site_option('backwpup_cfg_mailaddresssenderlog')
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/checkbox", [
      "name" => "mailerroronly",
      "label" => __("Send email with log only when errors occur during job execution", 'backwpup'),
      "checked" => (bool)get_site_option('backwpup_cfg_mailerroronly'),
    ]);
    ?>
  </div>
</div>

<?php
  BackWPupHelpers::component("navigation/link", [
    "type" => "secondary",
    "content" => __("Access to Logs", 'backwpup'),
    "url" => network_admin_url('admin.php?page=backwpuplogs'),
    "full_width" => true,
  ]);
?>

<?php
if (isset($is_in_form) && false === $is_in_form) {
    BackWPupHelpers::component("containers/form-end");
}
?>

<?php
BackWPupHelpers::component("form/button", [
	"type" => "primary",
	"label" => __("Save", 'backwpup'),
	"full_width" => true,
	"trigger" => "sidebar-submit-form",
]);
?>