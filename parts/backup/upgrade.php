<?php
use BackWPup\Utils\BackWPupHelpers;
?>
  <h1 class="text-3xl text-center text-white font-bold font-title">
    <?php _e("Upgrade to a Complete BackWPup Experience!", 'backwpup'); ?>
  </h1>

  <p class="my-4 text-xl text-white text-center">
    <?php _e("Give your website data the protection it deserves with BackWPup Pro.", 'backwpup'); ?>
  </p>

  <div class="m-auto w-full max-w-screen-md h-40 bg-primary-base rounded-2xl flex justify-center items-center gap-8">
    <!-- Banner -->
    <?php include untrailingslashit(BackWPup::get_plugin_data('plugindir')). '/assets/img/upgradebanner/encryption.svg'; ?>
    <?php include untrailingslashit(BackWPup::get_plugin_data('plugindir')). '/assets/img/upgradebanner/premiums_destinations.svg'; ?>
    <?php include untrailingslashit(BackWPup::get_plugin_data('plugindir')). '/assets/img/upgradebanner/migration.svg'; ?>
  </div>

  <p class="mt-8 flex items-center justify-center gap-10">
    <a href="https://backwpup.com/docs/what-is-the-difference-between-backwpup-free-and-backwpup-pro/" target="_blank" class="inline-block text-base leading-5 text-white border-b border-white font-title hover:text-secondary-lighter hover:border-secondary-lighter">
      <?php _e("See all features", 'backwpup'); ?>
    </a>

    <?php
    BackWPupHelpers::component("navigation/link", [
      "type" => "primary",
      "url" => "https://backwpup.com/#buy",
      "newtab" => true,
      "content" => __("Get BackWPup Pro now", 'backwpup'),
    ]);
    ?>
  </p>