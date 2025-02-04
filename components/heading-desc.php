<?php

/**
 * @var string  $title        Heading title. Default "".
 * @var string  $description  Heading description.
 */

$title = $title ?? "";
$description = $description ?? "";
?>
<h3 class="mb-1">
  <span class="text-xl font-semibold"><?php echo $title; ?></span>
  <?php if ($description) : ?>
    · <span class="text-base"><?php echo $description ?></span>
  <?php endif; ?>
</h3>