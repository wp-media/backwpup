<?php
use BackWPup\Utils\BackWPupHelpers;
ob_start();
/**
 * @var array  $storages     An array of storage services. Default: [].
 * @var string $style        The style of the items. Values: "default", "alt". Default: "default".
 */

# Defaults
$storages = $storages ?? [];

# CSS
$item_class = isset($style) && $style === "alt" ? "max-md:bg-grey-300" : "md:bg-white md:border md:border-grey-200";
$justify_class = isset($style) && $style === "alt" ? "max-md:justify-end" : "";

if (count($storages)>0) {
?>
<ul class="<?php echo BackWPupHelpers::clsx("flex flex-wrap gap-2", $justify_class); ?>">
  <?php foreach ($storages as $storage) : ?>
    <li class="<?php echo BackWPupHelpers::clsx("rounded flex items-center", $item_class); ?>">
      <?php  
        $content = $storage;

        if ( $content === 'FOLDER' ) {
          $content = __( 'Website Server', 'backwpup' );
        }

         echo BackWPupHelpers::component("tooltip", [
          "content" => __($content, 'backwpup'),
          "icon_name" => $storage,
          "icon_size" => "large",
          "position" => "top",
        ]);
      ?>
    </li>
  <?php endforeach; ?>
</ul>
<?php
} else {
	BackWPupHelpers::component("alerts/info", [
		"type" => "alert",
		"font" => "xs",
		"content" => __("Warning: No storage method is configured. Your backup will not work!", 'backwpup'),
	]);
}
?>