<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $children       Children component to display. Must fit a /part/ template. Default: null.
 */
?>
<aside class="bg-primary-base rounded-lg p-8 flex-auto flex flex-col">
  <?php isset($children) && BackWPupHelpers::children($children); ?>
</aside>