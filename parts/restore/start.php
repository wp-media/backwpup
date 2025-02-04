<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("heading", [
  "level" => 1,
  "title" => __("Restore full backup from:", 'backwpup') . " " . "March 11, 2024 11:03",
]);
?>

<div class="mt-6 flex flex-col gap-2">

  <div class="p-4 bg-white rounded">
    <h2 class="flex items-center gap-2 text-base font-semibold">
      <span class="w-5 text-secondary-base">
        <?php BackWPupHelpers::component("icon", ["name" => "check"]); ?>
      </span>
      <?php _e("Archive downloaded", 'backwpup'); ?>
    </h2>
  </div>

  <div class="p-4 bg-white rounded">
    <h2 class="flex items-center gap-2 text-base font-semibold">
      <span class="w-5 text-alert animate-spin">
        <?php BackWPupHelpers::component("icon", ["name" => "loading"]); ?>
      </span>
      <?php _e("Extracting Archive…", 'backwpup'); ?>
    </h2>

    <?php BackWPupHelpers::component("progress-bar", ["class" => "mt-2 h-11"]); ?>
  </div>

  <div class="p-4 bg-white rounded">
    <h2 class="flex items-center gap-2 text-base text-grey-400 font-semibold">
      <span class="w-5">3.</span>
      <?php _e("Restore", 'backwpup'); ?>
    </h2>
  </div>

</div>