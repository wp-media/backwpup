<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $children     Children component to display. Must fit a /part/ template. Default: null.
 * @var string  $class        Optional. Custom class to add to the container. Default: "".
 */

# Defaults
$class = isset($class) ? " " . $class : "";
?>
<div class="max-w-screen-xl<?php echo $class; ?>">
  <?php isset($children) && BackWPupHelpers::children($children); ?>
</div>