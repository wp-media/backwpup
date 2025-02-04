<?php
use BackWPup\Utils\BackWPupHelpers;
?>
<table class="w-full" id="backwpup-backup-history">
  <thead class="max-md:hidden">
    <tr class="*:pb-2 *:border-b *:border-grey-300 *:text-grey-700 *:text-base *:font-normal *:font-title">
      <th>
        <?php
        BackWPupHelpers::component("form/checkbox", [
          "name" => "bulk_select",
          "style" => "light",
          "trigger" => "select-all",
        ]);
        ?>
      </th>
      <th class="px-8 text-left"><?php _e("Created at", 'backwpup'); ?></th>
      <th class="px-8"><?php _e("Type", 'backwpup'); ?></th>
      <th class=" px-8 text-left"><?php _e("Stored on", 'backwpup'); ?></th>
      <th class="px-8 text-left"><?php _e("Data", 'backwpup'); ?></th>
      <th class="px-8 text-right"><?php _e("Actions", 'backwpup'); ?></th>
    </tr>
  </thead>
  <tbody class="max-md:flex max-md:flex-col max-md:gap-4">

  </tbody>
</table>