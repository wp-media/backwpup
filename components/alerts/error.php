<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $content   The error message to display. Default: "".
 * @var string  $link      URL to the page. Default: null.
 */

# Defaults
$content = $content ?? "";

?>
<div class="flex md:items-center max-md:flex-col max-md:items-start gap-2">
  <div class="size-6 flex items-center justify-center rounded-sm bg-danger-light text-danger">✕</div>
  <p class="text-base text-danger"><?php echo $content; ?></p>

  <?php if (isset($link) && !empty($link)) : ?>
    <?php
    BackWPupHelpers::component("navigation/link", [
      "url" => $link,
      "newtab" => true,
      "font" => "small",
      "content" => __("More info.", 'backwpup'),
      "class" => "max-md:text-base",
    ]);
    ?>
  <?php endif; ?>
</div>