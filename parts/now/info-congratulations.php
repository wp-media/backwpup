<?php
use BackWPup\Utils\BackWPupHelpers;
?>
<div class="inline-block p-1 bg-secondary-lighter text-primary-base rounded">
  <?php BackWPupHelpers::component("icon", ["name" => "check"]); ?>
</div>

<h2 class="mt-4 mb-2 text-primary-darker text-lg font-semibold font-title"><?php _e("Congratulations! 🙌", 'backwpup'); ?></h2>
<p class="text-xl"><?php _e("You’re done with your manual backup.", 'backwpup'); ?></p>
<p class="mt-6 flex items-center justify-center gap-6">
  <?php
  BackWPupHelpers::component("navigation/link", [
    "href" => "#",
    "newtab" => true,
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