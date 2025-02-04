<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $gap_size   The gap size. Values: "small", "medium". Default: "medium".
 */

# Padding 
$gap_sizes = [
  'small' => 'gap-2',
  'medium' => 'gap-4',
];
$gap_size = $gap_size ?? 'medium';
$gap = array_key_exists($gap_size, $gap_sizes) ? $gap_sizes[$gap_size] : $gap_sizes['medium'];

?>
<div class="relative flex-auto overflow-y-scroll">
  <div class="<?php echo BackWPupHelpers::clsx("absolute w-full flex flex-col", $gap); ?>">