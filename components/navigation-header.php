<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var string  $title  Title to display. Default: "".
 * @var string  $type   The type of container. Values: "modal", "sidebar". Default : "sidebar".
 */

# Defaults
$title = $title ?? "";
$type = $type ?? "sidebar";
$navigation = $navigation ?? "";

?>
<header class="flex items-center justify-between gap-4">
	<button data-toggle-setting-panel="<?php echo esc_attr( $navigation ); ?>" class="text-primary-darker text-2xl hover:text-secondary-darker">
        <?php
            BackWPupHelpers::component( 'icon', [
                'name' => 'arrow-left',
                'size' => 'large',
            ]);
        ?>
	</button>
	<h1 class="flex items-center gap-1 text-primary-darker font-title font-bold text-2xl"><?php echo $title; ?></h1>
	<button class="text-primary-darker text-2xl hover:text-secondary-darker js-backwpup-close-<?php echo $type; ?>">
		✕
	</button>
</header>