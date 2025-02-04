<?php

/**
 * @var string  $title  Title to display. Default: "".  
 * @var string  $type   The type of container. Values: "modal", "sidebar". Default : "sidebar".       
 */

# Defaults
$title = $title ?? "";
$type = $type ?? "sidebar";

?>
<header class="flex items-center justify-between gap-4">
  <h1 class="flex items-center gap-1 text-primary-darker font-title font-bold text-2xl"><?php echo $title; ?></h1>
  <button class="text-primary-darker text-2xl hover:text-secondary-darker js-backwpup-close-<?php echo $type; ?>">
    ✕
  </button>
</header>