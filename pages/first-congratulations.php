<?php
  use BackWPup\Utils\BackWPupHelpers;
?>
<div class="max-w-screen-xl flex flex-col gap-4 backwpup-typography">

  <?php BackWPupHelpers::component("app/header", []); ?>

  <div class="p-8 bg-grey-100 rounded-lg">
    <div class="m-auto w-full max-w-[850px]">

      <?php
      BackWPupHelpers::component("heading", [
        "level" => 1,
        "title" => __("We are creating a backup of your site…", 'backwpup'),
        "align" => "center",
      ]);
      ?>

      <div class="my-6">
        <div class="flex gap-2">

          <?php BackWPupHelpers::component("progress-bar", []); ?>

          <?php
          BackWPupHelpers::component("form/button", [
            "type" => "secondary",
            "disabled" => true,
            "label" => __("Display Logs", 'backwpup'),
            "font" => "small",
          ]);
          ?>

          <?php
          BackWPupHelpers::component("form/button", [
            "type" => "secondary",
            "disabled" => true,
            "label" => __("Abort", 'backwpup'),
            "font" => "small",
          ]);
          ?>
        </div>
      </div>

      <div class="p-8 text-center bg-white rounded-lg">
        <div class="inline-block p-1 bg-secondary-lighter text-primary-base rounded">
          <?php BackWPupHelpers::component("icon", ["name" => "check", "size" => "medium"]); ?>
        </div>

        <h2 class="mt-4 mb-2 text-primary-darker text-xl font-semibold"><?php _e("Congratulations! 🙌", 'backwpup'); ?></h2>
        <p class="text-xl"><?php _e("You’ve set up your first backup.", 'backwpup'); ?></p>
        <p class="mt-6 flex items-center justify-center gap-6">
          <?php
          BackWPupHelpers::component("navigation/link", [
            "url" => "#",
            "content" => __("Go to my Backups", 'backwpup'),
          ]);
          ?>

          <?php
          BackWPupHelpers::component("form/button", [
            "type" => "primary",
            "label" => __("Download my Backup", 'backwpup'),
            "icon_name" => "download",
            "icon_position" => "after",
            "trigger" => "download-backup",
          ]);
          ?>
        </p>

      </div>
    </div>
  </div>

  <?php
  BackWPupHelpers::component("containers/green-box", [
    "children" => "backup/upgrade",
  ]);
  ?>
</div>

<?php
BackWPupHelpers::component("containers/sidebar");
BackWPupHelpers::component("containers/modal");
?>