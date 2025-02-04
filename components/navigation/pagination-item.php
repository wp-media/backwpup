<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var int     $page_num       Optional. The page number.  
 * @var string  $arrow          Optional. The arrow to display. Values: "left", "right". Replaces $page_num if set. 
 * @var bool    $active         Optional .If true, the button is active. Default: false.
 * @var bool    $dots           Optional. If true, the button displays dots. Default: false.
 * @var bool    $disabled       Optional. If true, the button is disabled. Default: false. 
 */

# Page num
$page_num = isset($page_num) ? $page_num : "";

# Status & state
$disabled = $disabled ?? false;
$active = $active ?? false;
$dots = $dots ?? false;

# Arrow
$arrows = [
  "left" => "arrow-left",
  "right" => "arrow-right",
];
$arrow = isset($arrow) && array_key_exists($arrow, $arrows) ? $arrows[$arrow] : null;

# Classes
$base_styles = "flex items-center justify-center size-8 rounded border";
$contextual_styles = $active ? "bg-secondary-base border-secondary-base" : "border-grey-200 enabled:hover:bg-secondary-lighter enabled:hover:border-secondary-base disabled:text-grey-500";
?>
<button <?php if ($disabled || $dots) : ?>disabled<?php endif; ?> class="<?php echo BackWPupHelpers::clsx($base_styles, $contextual_styles); ?>" data-page="<?php echo $page_num;  ?>">
  <?php if (!$arrow) {
    echo $page_num;
  } ?>
  <?php $arrow !== null && BackWPupHelpers::component("icon", ["name" => $arrow, "size" => "medium"]); ?>
  <?php echo $dots ? "…" : ""; ?>
</button>