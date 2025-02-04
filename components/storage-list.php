<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var array   $storages     An array of storage services. Default: [].
 * @var bool    $full_width   Optional. True to make the button full width. Default: false.
 * @var string  $prefix       Optional. The prefix for the input name. Default: "".
 */

# Defaults
$storages = $storages ?? [];
$prefix = $prefix ?? "";
$full_width = $full_width ?? false;

?>
<div class="flex flex-wrap gap-2 max-w-screen-md">
  <?php foreach ($storages as $storage) : ?>
    <?php BackWPupHelpers::component("storage-item", [
      "name" => $storage['name'],
      "slug" => $storage['slug'],
      "active" => $storage['active'],
      "full_width" => $full_width,
      "prefix" => $prefix,
      "label" => $storage['label'],
    ]); ?>
  <?php endforeach; ?>
</div>