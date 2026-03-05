<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var string  $content        The link label. Default: ""    
 * @var string  $url            The URL for the link. It should be a valid URL. Default: "#".
 * @var string  $type           Optional. The link variation type. Values: "primary", "secondary", "link". Default: "link".
 * @var bool    $newtab         Optional. True : open in a new tab. Default: false.
 * @var string  $font           Optional. The font size. Values: "xs", "small", "medium". Default: "medium".
 * @var string  $icon_name      Optional. The name of the icon or false. Must match a file in components/icons/. Default: false.     
 * @var string  $icon_position  Optional. The position of the icon. Values: "before", "after". Default: "after". 
 * @var bool    $class          Optional. Custom classes to add to the link. Default: "".
 * @var string  $download       Optional. The download attribute. Default: "".
 * @var string  $identifier     Optional. The identifier for the component. Default: null.
 */

# URL & Target
$url = isset($url) ? $url : "#";
$target = isset($newtab) && $newtab ? '_blank' : '';

# Full width
$full_width = isset($full_width) && $full_width ? "w-full" : "";

# Identifier
$id = $identifier ?? '';

# Type
$types = ["primary", "secondary", "link"];
$type = isset($type) && in_array($type, $types) ? $type : "link";
switch ($type) {
  case "primary":
    $button_style = "inline-flex gap-2 rounded border border-transparent bg-secondary-base font-medium text-primary-darker flex items-center justify-center hover:bg-secondary-darker hover:text-primary-darker";
    break;
  case "secondary":
    $button_style = "inline-flex gap-2 rounded border border-primary-darker font-medium text-primary-darker flex items-center justify-center hover:bg-grey-200 hover:text-primary-darker";
    break;
  default:
    $button_style = "inline-flex gap-2 items-center leading-5 text-primary-darker border-b border-primary-darker font-title hover:text-primary-lighter hover:border-primary-lighter";
    break;
}

# Font
$font = $font ?? "medium";
$font_sizes = [
  "small" => "text-xs",
  "medium" => "text-base",
];
$font_size = array_key_exists($font, $font_sizes) ? $font_sizes[$font] : $font_sizes['medium'];

# Paddings 
$paddings = "";

if ($font === "small" && $type !== "link") {
  $paddings = "px-4 py-[11px]";
}
if ($font === "medium" && $type !== "link") {
  $paddings = "px-6 py-[14px]";
}

# Icon
$icon_position = isset($icon_name) ? $icon_position ?? "after" : false;
$icon = [
  "name" => $icon_name ?? false,
  "size" => $font,
];

# Classes
$class = $class ?? "";

# Download
$download = $download ?? '';

$allowed_protocols = null;
if ( ! empty( $download ) ) {
  $allowed_protocols = wp_allowed_protocols();
  if ( ! in_array( 'data', $allowed_protocols, true ) ) {
    $allowed_protocols[] = 'data';
  }
}
$href = $url;

?>
<a href="<?php echo esc_url( $href, $allowed_protocols ); ?>"<?php echo $id ? " id='" . esc_attr( $id ) . "'" : ''; ?><?php echo $target ? ' target="' . esc_attr( $target ) . '"' : ''; ?><?php echo $download ? ' download="' . esc_attr( $download ) . '"' : ''; ?> class="<?php echo esc_attr( BackWPupHelpers::clsx( $paddings, $font_size, $full_width, $button_style, $class ) ); ?>">
  <?php $icon_position === "before" && BackWPupHelpers::component("icon", $icon); ?>
  <?php echo wp_kses_post( $content ?? '' ); ?>
  <?php $icon_position === "after" && BackWPupHelpers::component("icon", $icon); ?>
</a>
