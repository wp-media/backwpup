<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
      <th class="px-8 text-left"><?php esc_html_e("Created at", 'backwpup'); ?></th>
      <th class="px-8"><?php esc_html_e("Type", 'backwpup'); ?></th>
      <th class=" px-8 text-left"><?php esc_html_e("Stored on", 'backwpup'); ?></th>
      <th class="px-8 text-left"><?php esc_html_e("Data", 'backwpup'); ?></th>
      <th class="px-8 text-right"><?php esc_html_e("Actions", 'backwpup'); ?></th>
    </tr>
  </thead>
  <tbody class="max-md:flex max-md:flex-col max-md:gap-4">

  </tbody>
</table>
