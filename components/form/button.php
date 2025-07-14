<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $label          The link label. Default: "".
 * @var string  $type           The button variation type. Values: "primary", "secondary", "link". Default: "link".
 * @var bool    $disabled       Optional. True to disable button. Default: false.
 * @var bool    $full_width     Optional. True to make the button full width. Default: false.
 * @var string  $font           Optional. The font size. Values: "small", "medium". Default: "medium".
 * @var string  $icon_name      Optional. The name of the icon or false. Must match a file in components/icons/. Default: false.     
 * @var string  $icon_position  Optional. The position of the icon. Values: "before", "after", "alone". Default: "after".    
 * @var string  $trigger        Optional. For JS. The CSS classname for jQuery. Default: null.
 * @var string  $display        Optional. For JS. The content to display in modal or sidebar. Default: null. 
 * @var string  $class          Optional. Additional CSS classname . Default: null.
 * @var array   $data           Optional. Additional data attributes. Default: [].
 * @var string  $identifier     Optional. The identifier for the component. Default: null.
 * @var string  $tooltip        Optional. The tooltip content. Default: "".
 * @var string  $tooltip_pos    Optional. The tooltip position. Default: "center".
 * @var string  $tooltip_icon   Optional. The tooltip icon name. Must match a file in components/icons/. Default: "info".
 * @var string  $tooltip_size   Optional. The tooltip icon size. Values: "small", "medium". Default: "small".
 * @var string  $button_type    Optional. The button type. Values: "submit", "button", "reset". Default: "submit".
 */

# Disabled
$disabled = $disabled ?? false;

# Full width
$full_width = isset($full_width) && $full_width ? "w-full" : "";
$id = isset($identifier) ? " id='".esc_attr($identifier)."'" : null;

# Type
$button_base = "flex items-center";
$types = ["primary", "secondary", "link", "settings", "icon", "icon-hover"];
$type = isset($type) && in_array($type, $types) ? $type : "link";
switch ($type) {
  case "primary":
    $button_style = "justify-center rounded border border-transparent disabled:opacity-40 bg-secondary-base font-medium text-primary-darker enabled:hover:bg-secondary-darker";
    break;
  case "secondary":
    $button_style = "justify-center rounded border border-primary-darker disabled:opacity-40 font-medium text-primary-darker enabled:hover:bg-grey-200";
    break;
  case "settings":
    $button_style = "justify-between rounded bg-grey-100 text-xl font-medium text-primary-darker hover:bg-grey-200";
    break;
  case 'icon':
    $button_style = "justify-between rounded bg-white-100 text-xl font-medium text-primary-darker hover:bg-grey-200";
    break;
  case 'icon-hover':
   $button_style = "justify-between rounded bg-white-100 text-xl font-medium text-primary-darker";
   break;
  default:
    $button_style = "justify-center leading-5 text-primary-darker border-b border-primary-darker font-title disabled:opacity-40 enabled:hover:text-primary-lighter enabled:hover:border-primary-lighter";
    break;
}

# Font
$font = $font ?? "medium";
$font_key = $type === "link" ? $font . "_link" : $font;
$font_sizes = [
  "small_link" => "text-xs gap-2 text-base",
  "medium_link" => "text-base gap-4",
  "small" => "px-4 py-[11px] text-xs gap-2",
  "medium" => "px-6 py-[14px] text-base gap-4",
  "semi_large" => "px-3 py-[11px] text-base",
];
$font_size = array_key_exists($font_key, $font_sizes) ? $font_sizes[$font_key] : $font_sizes['medium'];

# Icon
$icon_position = isset($icon_name) ? $icon_position ?? "after" : false;
$icon = [
  "name" => $icon_name ?? false,
  "size" => $font === "small" ? "xs" : $font,
];

# JS actions
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";
$display = isset($display) ? " data-content=\"$display\"" : "";

# CSS
$class = $class ?? "";

# Tooltip position
$tooltip_pos = $tooltip_pos ?? "top";
$tooltip_icon = $tooltip_icon ?? "info";
$tooltip_size = $tooltip_size ?? "small";

# Data
$data_attrs = "";
if (isset($data)) {
  foreach ($data as $key => $value) {
    $data_attrs .= " data-$key=\"$value\"";
  }
}

$button_type = $button_type ?? 'submit';

?>
<button type="<?php echo esc_attr( $button_type ); ?>" <?php echo $id ?> <?php if ($disabled) : ?>disabled<?php endif; ?> class="<?php echo BackWPupHelpers::clsx($font_size, $full_width, $button_base, $button_style, $class, $trigger); ?>" <?php echo $display; ?><?php echo $data_attrs; ?>>
  <?= $icon_position === "before" || $icon_position === "alone" && BackWPupHelpers::component("icon", $icon) ? BackWPupHelpers::component("icon", $icon) : '' ; ?>
  <?php if ($icon_position !== "alone" && $label !== "") : ?>
    <span><?php echo $label ?? ""; ?></span>
  <?php endif; ?>
  <?= $icon_position === "after" && BackWPupHelpers::component("icon", $icon) ? BackWPupHelpers::component("icon", $icon) : ''; ?>
  <?php isset($tooltip) && BackWPupHelpers::component("tooltip", ["content" => $tooltip, "position" => $tooltip_pos, "icon_name" => $tooltip_icon, 'icon_size' => $tooltip_size]); ?>
</button>