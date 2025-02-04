<?php
use BackWPup\Utils\BackWPupHelpers;
?>
<div class="inline-block p-1 bg-secondary-lighter text-primary-base rounded">
  <?php BackWPupHelpers::component("icon", ["name" => "check"]); ?>
</div>

<h2 class="mt-4 mb-2 text-primary-darker text-lg font-semibold font-title"><?php _e("Congratulations! 🙌", 'backwpup'); ?></h2>
<p class="text-xl"><?php _e("The restoration is now complete!", 'backwpup'); ?></p>
<p class="text-xl"><?php _e("You may now continue using your site as usual.", 'backwpup'); ?></p>