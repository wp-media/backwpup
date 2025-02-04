<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $name     The name of the action  
 * @var string  $action   The classe of the action.
 * @var string  $icon     Optional. The name of the icon. Must match a name in /components/icons.
 * @var string  $display  Optional. For JS. The content to display in modal or sidebar. Default: null.
 * @var array   $dataset  Optional. An array of data attributes. Default: null.
 */

# JS actions
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";
$display = isset($display) ? " data-content=\"$display\"" : "";

# Defaults
$name = $name ?? "";
$action = $action ?? "";
$dataset = $dataset ?? null;

?>
<button class="<?php echo BackWPupHelpers::clsx("p-2 w-full flex gap-2 hover:bg-grey-100", $trigger); ?>" <?php echo $display; ?>
  <?php if (isset($dataset)) : ?>
    <?php foreach ($dataset as $key => $value) : ?>
      <?php echo " $key=\"$value\""; ?>
    <?php endforeach; ?>
  <?php endif; ?>
>
  <div class="shrink-0">
    <?php isset($icon) && BackWPupHelpers::component("icon", ["name" => $icon, "size" => "small"]); ?>
  </div>
  <span class="text-nowrap"><?php echo $name; ?></span>
</button>