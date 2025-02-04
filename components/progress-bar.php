<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $class   Custom classes to add to the progress bar
 */

# Classes
$class = $class ?? "";

?>
<div class="<?php echo BackWPupHelpers::clsx($class, "p-1 min-h-10 flex-auto flex border border-primary-darker rounded"); ?> progress-bar">
  <div class="relative rounded-sm bg-secondary-base progress-step">
    <span class="absolute top-0 bottom-0 right-4 flex items-center justify-center text-base tabular-nums">
    </span>
  </div>
</div>