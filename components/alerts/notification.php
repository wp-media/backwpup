<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $children     Children component to display. Must fit a /part/ template. Default: null.
 * 
 */
?>
<div class="px-8 py-6 flex items-center gap-8 rounded-lg bg-primary-base text-white">
  <div class="flex-auto">
    <?php include untrailingslashit(BackWPup::get_plugin_data('plugindir')).'/components/icons/logo-white.php'; ?>
  </div>
  <p class="text-lg"><?php isset($children) && BackWPupHelpers::children($children); ?></p>
  <button class="text-2xl">✕</button>
</div>