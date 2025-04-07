<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $title      Heading title. Default "".
 * @var string  $level      Heading level. Values: 1 to 6. Default: 1.
 * @var string  $tooltip    Optional. The tooltip content. Default: "".
 * @var string  $class      Optional. Custom class to add to the heading. Eg: margins. Default: "".
 * @var string  $align      Optional. Heading alignment. Values: left, center, right. Default: left. 
 * @var string  $font       Optional. Font size. Values: small, medium, large. Default: medium. 
 * @var string  $color      Optional. Heading color. Default: "primary-darker".
 * @var string  $identifier Optional. The identifier for the component. Default: null.
 * @var string  $bold       Optional. Font weight. Values: tailwind bold utitlity claseses. Default: font-bold.
 * @var bool  $flex         Optional. Flex Box Layout. Values: true, false. Default: true.
 * @var bool  $truncate     Optional. Wrap text. Values: true, false. Default: false.
 */

$title = $title ?? "";
$level = isset($level) && in_array($level, range(1, 6)) ? $level : 1;
$tag = "h" . $level;
$bold = $bold ?? $level < 3 ? "font-bold" : "font-semibold";
$class = $class ?? "";
$id = isset($identifier) ? " id='".esc_attr($identifier)."'" : null;

# Font
$font = $font ?? ($level < 3 ? "medium" :  "small");
$font_sizes = [
  "xs" => "text-lg",
  "small" => "text-xl",
  "regular" => "text-[1.21rem]",
  "medium" => "text-2xl",
  "large" => "text-3xl",
];
$font_size = array_key_exists($font, $font_sizes) ? $font_sizes[$font] : $font_sizes['medium'];

# Color 
$color = $color ?? "primary-darker";

$flex = isset( $flex ) && ! $flex ? '' : 'flex';
$truncate = isset( $truncate ) && $truncate ? 'truncate' : '';

# CSS classes
$classes = BackWPupHelpers::clsx(
  "items-center gap-1 text-$color font-title",
  $flex,
  $truncate,
  $bold,
  $font_size,
  (isset($align) ? "justify-$align" : ""),
  $class,
)

?>
<<?php echo $tag ?> class="<?php echo $classes; ?>" <?php echo $id ?>>
  <?php echo $title ?>
  <?php isset($tooltip) && BackWPupHelpers::component("tooltip", ["content" => $tooltip]); ?>
</<?php echo $tag ?>>