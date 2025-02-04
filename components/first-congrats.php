<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var string $identifier The unique identifier for the component. Optional.
 * @var bool $display Whether to display the component (default: true).
 */

// Default to `true` for `$display` if it's not set.
$display = isset($display) ? $display : true;

?>
<div 
    <?= isset($identifier) && !empty($identifier) ? "id='" . esc_attr($identifier) . "'" : ''; ?> 
    class="p-8 text-center bg-white rounded-lg" 
    <?= !$display ? 'style="display:none;"' : ''; ?>
>
	<div class="inline-block p-1 bg-secondary-lighter text-primary-base rounded">
		<?php BackWPupHelpers::component("icon", ["name" => "check", "size" => "medium"]); ?>
	</div>

	<h2 class="mt-4 mb-2 text-primary-darker text-xl font-semibold"><?php esc_html_e("Congratulations! 🙌", 'backwpup'); ?></h2>
	<p class="text-xl"><?php esc_html_e("You’ve set up your first backup.", 'backwpup'); ?></p>
	<p class="mt-6 flex items-center justify-center gap-6">
		<?= __('You will be redirected to the main dashboard in ') ?><span id="redirect-time">5</span><?= __(' seconds', 'backwpup') ?>
	</p>
</div>