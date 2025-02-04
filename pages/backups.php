<?php
  use BackWPup\Utils\BackWPupHelpers;
  use Inpsyde\Restore\ViewLoader;
?>
  <div class="max-w-screen-xl flex flex-col gap-4">
    <div id="tb_download_file" style="display: none;">
      <div id="tb_container">
        <p id="download-file-waiting">
        <?php esc_html_e('Please wait &hellip;', 'backwpup'); ?>
        </p>
        <p id="download-file-success" style="display: none;">
        <?php esc_html_e(
          'Your download has been generated. It should begin downloading momentarily.',
          'backwpup'
        ); ?>
        </p>
        <div class="progressbar" style="display: none;">
          <div id="progresssteps" class="bwpu-progress" style="width:0%;">0%</div>
        </div>
      <?php
      if ( \BackWPup::is_pro() ) {
        $view = new ViewLoader();
        $view->decrypt_key_input();
      }
      ?>
      </div>
    </div>
  <?php
  BackWPupHelpers::component("app/header", [
    "children" => "backups/top-menu",
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

  <?php
  BackWPupHelpers::component("containers/grey-box", [
    "padding_size" => "large",
    "children" => "backups/next-scheduled-backup",
  ]);
  ?>

  <div class="md:p-8 max-md:pt-8 flex flex-col gap-6">
    <div class="flex">
      <div class="flex-auto">
        <?php
        BackWPupHelpers::component("heading", [
          "level" => 1,
          "title" => __("Backups History", 'backwpup'),
          "class" => "max-md:justify-center"
        ]);
        ?>
      </div>
      <div id="backwpup-pagination">
	      <?php
	      BackWPupHelpers::component("navigation/pagination", [
		      "max_pages" => 10,
		      "trigger" => "table-pagination",
		      "class" => "max-md:hidden",
	      ]);
	      ?>
      </div>

    </div>

    <div class="flex gap-2 max-md:hidden">
      <?php
      BackWPupHelpers::component("form/select", [
        "name" => "bulk_actions",
        "label" => __("Bulk Actions", 'backwpup'),
        "withEmpty" => true,
        "value" => "",
        "identifier" => "bulk-actions-select",
        "options" => [
          "delete" => __("Delete permanently", 'backwpup'),
        ],
      ]);
      ?>

      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "primary",
        "label" => __("Apply", 'backwpup'),
        "identifier"  => "bulk-actions-apply",
        "disabled" => true,
      ]);
      ?>
    </div>

    <?php BackWPupHelpers::component("table-backups", []); ?>

    <div class="flex justify-center md:hidden">
      <?php
      BackWPupHelpers::component("navigation/pagination", [
        "max_pages" => 10,
        "trigger" => "table-pagination",
      ]);
      ?>
    </div>
  </div>
</div>

<?php
BackWPupHelpers::component("containers/sidebar");
BackWPupHelpers::component("containers/modal");
?>
