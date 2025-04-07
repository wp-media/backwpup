<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $name   The icon name. Must match a file in components/icons/. Default: "arrow-right".
 * @var string $size The size of the icon. Values: "xs", "small", "medium", "large". Default : "medium".
 * @var string $class Additional classes for the icon. Default: "".
 * @var array  $data  Additional data attributes for the icon. Default: [].
 */

# Icon
$icon = $name ?? 'arrow-right';

# Sizes
$sizes = [
  "xs" => 12,
  "small" => 16,
  "medium" => 21,
  "medium-2x" => 25,
  "large" => 32,
  "xl" => 40,
];
$size_num = isset($size) && array_key_exists($size, $sizes) ? $sizes[$size] : $sizes["medium"];

# Classes
$classes = isset($class) ? $class : '';

# Data attributes
$data_attributes = '';
if (isset($data) && is_array($data)) {
	foreach ($data as $key => $value) {
		$data_attributes .= ' data-' . $key . '="' . esc_attr($value) . '"';
	}
}

if (file_exists(untrailingslashit(BackWPup::get_plugin_data('plugindir'))."/components/icons/" . strtolower( $icon ) . ".php")) { // /var/www/html/wp-content/plugins/backwpup/components/icons/folder.php
  BackWPupHelpers::component("icons/". strtolower( $icon ), [
    'size' => $size_num,
	'class' => $classes,
	'data' => $data_attributes,
  ]);
} else if (file_exists(untrailingslashit(BackWPup::get_plugin_data('plugindir'))."/assets/img/storage/" . $icon . ".svg")) {
  include untrailingslashit(BackWPup::get_plugin_data('plugindir'))."/assets/img/storage/" . $icon . ".svg";
} else {
  return;
}


