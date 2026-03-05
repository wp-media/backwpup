<?php

/**
 * @var string  $title        Heading title. Default "".
 * @var string  $description  Heading description.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title = $title ?? "";
$description = $description ?? "";
?>
<h3 class="mb-1">
  <span class="text-xl font-semibold"><?php echo esc_html( $title ); ?></span>
  <?php if ($description) : ?>
    · <span class="text-base"><?php echo esc_html( $description ); ?></span>
  <?php endif; ?>
</h3>
