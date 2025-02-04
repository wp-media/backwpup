<?php
use BackWPup\Utils\BackWPupHelpers;
?>
<div class="mb-2 flex justify-center text-alert">
  <?php BackWPupHelpers::component("icon", ["name" => "star"]); ?>
  <?php BackWPupHelpers::component("icon", ["name" => "star"]); ?>
  <?php BackWPupHelpers::component("icon", ["name" => "star"]); ?>
  <?php BackWPupHelpers::component("icon", ["name" => "star"]); ?>
  <?php BackWPupHelpers::component("icon", ["name" => "star"]); ?>
</div>

<?php
BackWPupHelpers::component("heading", [
  "level" => 2,
  "title" => __("Site back and shining?", 'backwpup'),
  "align" => "center",
  "color" => "white",
]);
?>

<p class="my-4 text-xl text-white text-center">
  <?php _e("Share your joy with a review on the WordPress repository. Your support inspires us.", 'backwpup'); ?>
</p>

<p class="mt-6 flex items-center justify-center">
  <?php
  BackWPupHelpers::component("navigation/link", [
    "type" => "primary",
    "href" => "#",
    "newtab" => true,
    "content" => __("Rate us on WordPress.org", 'backwpup'),
  ]);
  ?>
</p>