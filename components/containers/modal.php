<section id="backwpup-modal" class="hidden fixed inset-0 z-[100001] p-20 justify-center items-center backdrop-blur-md">
  <div class="bg-white w-[600px] rounded-lg p-6 shadow-lg">

    <?php
    # Get all files in the parts/modal directory
    $files = glob(untrailingslashit(BackWPup::get_plugin_data('plugindir')). '/parts/modal/*');
    foreach ($files as $file) {
      $filename = pathinfo($file, PATHINFO_FILENAME);
      echo '<article class="flex flex-col gap-4" id="sidebar-' . $filename . '">';
      include $file;
      echo '</article>';
    }
    ?>
  </div>
</section>