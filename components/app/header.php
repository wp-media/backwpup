<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $title     Optional. Title of the page.
 * @var string  $subtitle  Optional. Subtitle of the page.
 * @var string  $children       Children component to display. Must fit a /part/ template. Default: null.
 */
?>
<header class="p-4 w-full flex items-center gap-6">
  <div class="flex-auto">
    <?php include untrailingslashit(BackWPup::get_plugin_data('plugindir')).'/components/icons/logo.php'; ?>

    <?php
    isset($title) && BackWPupHelpers::component("heading", [
      "title" => $title,
      "level" => 1,
      "class" => "mt-7",
    ]);
    ?>
    <?php if (isset($subtitle)) : ?>
      <p class="font-light text-xl"><?php echo $subtitle; ?></p>
    <?php endif; ?>
  </div>
  <?php isset($children) && BackWPupHelpers::children($children); ?>
</header>