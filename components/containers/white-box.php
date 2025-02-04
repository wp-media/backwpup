<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $padding_size   The padding sizes. Values: "small", "medium", "large". Default: "small".
 * @var string  $children       Children component to display. Must fit a /part/ template. Default: null.
 * @var string  $class          Optional. Additional classes to add to the container.
 */

# Padding 
$padding_sizes = [
  'small' => 'p-4',
  'medium' => 'p-6',
  'large' => 'p-8',
];
$padding_size = $padding_size ?? 'small';
$padding = array_key_exists($padding_size, $padding_sizes) ? $padding_sizes[$padding_size] : $padding_sizes['small'];

# Class
$class = $class ?? '';

?>
<div class="p-8 <?php echo BackWPupHelpers::clsx($class, $padding, "text-center bg-white rounded-lg"); ?>">
  <?php isset($children) && BackWPupHelpers::children($children); ?>
</div>