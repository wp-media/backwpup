<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $name   The icon name. Must match a file in components/icons/. Default: "arrow-right".   
 * @var string  $size   The size of the icon. Values: "xs", "small", "medium", "large". Default : "medium".       
 */

# Icon
$icon = $name ?? 'arrow-right';

if (!file_exists(untrailingslashit(BackWPup::get_plugin_data('plugindir'))."/components/icons/" . $icon . ".php")) {
  return;
}

# Sizes
$sizes = [
  "xs" => 12,
  "small" => 16,
  "medium" => 21,
  "large" => 32,
  "xl" => 40,
];
$size_num = isset($size) && array_key_exists($size, $sizes) ? $sizes[$size] : $sizes["medium"];

BackWPupHelpers::component("icons/$icon", [
  'size' => $size_num,
]);
