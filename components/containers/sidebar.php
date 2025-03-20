<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * Sidebar container
 * @var string $is_in_form Set to true if the sidebar is in a form. Default: false.
 */
#Default values
$is_in_form = $is_in_form ?? false;

?>
<aside id="backwpup-sidebar" class="fixed z-[100000] top-0 right-0 bottom-0 w-[410px] p-6 flex flex-col gap-4 rounded-l-lg bg-white shadow-xl translate-x-[450px] transition-transform duration-500 ease-out backwpup-typography">
  <?php
  # Get all files in the parts/sidebar directory
  $files = glob(untrailingslashit(BackWPup::get_plugin_data('plugindir')) . '/parts/sidebar/*');
  if (BackWPup::is_pro()) {
    $filesPro = glob(untrailingslashit(BackWPup::get_plugin_data('plugindir')) . '/pro/parts/sidebar/*');
    $files = array_merge($files, $filesPro);
  }
  foreach ($files as $file) {
    if (!is_file($file)) {
      continue;
    }
    $filename = pathinfo($file, PATHINFO_FILENAME);
    echo '<article class="flex flex-col flex-auto gap-4" id="sidebar-' . $filename . '">';
    include $file;
    echo '</article>';
  }
  ?>
</aside>