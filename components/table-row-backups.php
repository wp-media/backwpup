<?php
use BackWPup\Utils\BackWPupHelpers;

// Date formatting logic
$date = new DateTime();
$date->setTimestamp($backup['time']);
$formatted_date = $date->format('M j, Y');
$formatted_time = $date->format('g:ia');
$type = ('' === $backup['type']) ? 'Manual Backup' : $backup['type'];
$type_icon = ('' === $backup['type']) ? 'user-settings' : 'clock';
$actions =[];
//Add the download and restore action
//If we can't restore the backup, we can't download it either.
if (isset($backup['dataset-download'])) {
	$actions[] = ["name" => __("Download", 'backwpup'), "icon" => "download", "trigger" => $backup["download-trigger"], "dataset" => $backup['dataset-download']];
}

if (isset($backup['dataset-restore'])) {
	$actions[] = ["name" => $backup['dataset-restore']['label'], "icon" => "restore", "trigger" => "open-modal", "display" => "restore-backup","dataset" => $backup['dataset-restore']];
}

// Add the delete action
if (isset($backup['dataset-delete'])) {
  $actions[] = [
    "name" => __("Delete", 'backwpup'),
    "icon" => "trash", 
    "trigger" => "open-modal", 
    "display" => "delete-backup", 
    "dataset" => $backup['dataset-delete']
  ];
}

// Start output buffering
ob_start();
?>

<tr class="*:py-6 *:border-b *:border-grey-300 max-md:bg-grey-100 max-md:rounded-lg max-md:block max-md:p-4">
  <td class="p-0 max-md:hidden">
    <?php
      echo BackWPupHelpers::component("form/checkbox", [
        "name" => "select_backup",
        "style" => "light",
        "trigger" => "select-backup",
        "data" => [
          "delete" => json_encode($backup['dataset-delete']),
        ]
      ]);
    ?>
  </td>

  <td class="px-8 max-md:py-4 max-md:px-6 max-md:flex max-md:items-baseline max-md:gap-1 max-md:bg-white max-md:rounded max-md:border-none">
    <p class="text-sm font-bold"><?= $formatted_date ?></p>
    <p class="text-base">at <?= $formatted_time ?></p>
  </td>

  <td class="px-8 max-md:block max-md:px-2 max-md:py-3">
    <div class="flex items-center md:justify-center max-md:justify-between">
      <p class="text-base font-semibold md:hidden"><?php _e("Type", "backwpup"); ?></p>
      <?php
        echo BackWPupHelpers::component("tooltip", [
          "content" => __($type, 'backwpup'),
          "icon_name" => $type_icon,
          "icon_size" => "large",
          "position" => "center",
        ]);
      ?>
    </div>
  </td>

  <td class="px-8 max-md:px-2 max-md:py-3 max-md:flex max-md:justify-between max-md:items-center">
    <p class="text-base font-semibold md:hidden"><?php _e("Stored on", "backwpup"); ?></p>
    <?php
      echo BackWPupHelpers::component("storage-list-compact", [
        "storages" => (array)$backup['stored_on'],
        "style" => "alt"
      ]);
    ?>
  </td>

  <td class="px-8 max-md:px-2 max-md:py-3 max-md:flex max-md:justify-between max-md:items-center">
    <p class="text-base font-semibold md:hidden"><?php _e("Data", "backwpup"); ?></p>
    <div class="flex gap-2">
    <?php
      foreach ($backup['data'] as $data) {
        switch ($data) {
          case 'FILE':
            $icon = 'wp';
            $label = 'Files';
            break;
          case 'DBDUMP':
            $icon = 'database';
            $label = 'Database';
            break;
          case 'WPPLUGIN':
            $icon = 'file';
            $label = 'Plugins';
            break;
          default:
            $icon = 'dots';
            $label = $data;
            break;
        }
        echo BackWPupHelpers::component("tooltip", [
          "content" => __($label, 'backwpup'),
          "icon_name" => $icon,
          "icon_size" => "large",
          "position" => "center",
        ]);
      }
    ?>
    </div>
  </td>

  <td class="px-8 max-md:block max-md:p-0 max-md:border-none">
    <?php
      echo BackWPupHelpers::component("navigation/menu", [
        "class" => "max-md:hidden",
        "actions" => $actions,
      ]);
    ?>
    <ul class="md:hidden flex flex-col">
      <li class="py-4 flex justify-end border-b border-grey-400">
        <?php
          echo BackWPupHelpers::component("form/button", [
            "type" => "link",
            "label" => __("Download", "backwpup"),
            "icon_name" => "download",
            "icon_position" => "after",
            "trigger" => "download-backup",
          ]);
        ?>
      </li>
      <li class="py-4 flex justify-end">
        <?php
          echo BackWPupHelpers::component("form/button", [
            "type" => "link",
            "label" => __("Restore", "backwpup"),
            "icon_name" => "restore",
            "icon_position" => "after",
            "trigger" => "open-modal",
            "display" => "restore-backup"
          ]);
        ?>
      </li>
    </ul>
  </td>
</tr>

<?php
// End output buffering and capture the output
$tableRowHtml = ob_get_clean();

// Return the HTML to use or echo it when needed
echo $tableRowHtml;
?>