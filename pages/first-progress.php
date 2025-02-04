<?php
  use BackWPup\Utils\BackWPupHelpers;

  $abortUrl = $abortUrl ?? "";
  
  BackWPupHelpers::component("form/hidden", [
    "name" => "backwpupworking_ajax_nonce",
    "identifier" => "backwpupworking_ajax_nonce",
    "value" => wp_create_nonce('backwpupworking_ajax_nonce'),
  ]);
?>
<div class="max-w-screen-xl flex flex-col gap-4">

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

      <?php
        BackWPupHelpers::component("containers/grey-box", [
          "identifier" => "runningjob",
          "padding_size" => "large",
          "children" => "backups/progress",
          "display" => false,
        ]);
      ?>      

      <div class="progressbar" style="display: none;">
          <div id="progresssteps" class="bwpu-progress" style="width:0%;">0%</div>
      </div>
      <div class="mt-6 p-8 text-center bg-white rounded-lg">
        <div id="info_container_2">
          <div class="inline-block p-1 bg-alert-light text-alert rounded">
            <?php BackWPupHelpers::component("icon", ["name" => "alert", "size" => "medium", 'abortUrl' => $abortUrl]); ?>
          </div>
          <h2 class="mt-4 mb-2 text-primary-darker text-xl font-semibold"><?php _e("Creating a backup might take a few minutes, depending on your site’s size", 'backwpup'); ?></h2>
          <p class="text-xl font-light"><?php _e("The page will update automatically when it’s ready to download. The backup will keep running. You’ll get a notification when it’s done.", 'backwpup'); ?></p>
          <p class="mt-2 text-base font-semibold text-alert"><?php _e("Feel free to leave 👋", 'backwpup'); ?></p>
        </div>

        <?php 
        BackWPupHelpers::component("first-congrats", [
          "identifier" => "first-congratulations",
          "display" => false,
        ]);
        ?>
      </div>
    </div>
  </div>

  <?php
  if ( ! BackWPup::is_pro() ):

    BackWPupHelpers::component("containers/green-box", [
      "children" => "backup/upgrade",
    ]);

  endif;
  ?>
</div>

<?php
BackWPupHelpers::component("containers/sidebar");
BackWPupHelpers::component("containers/modal");
?>