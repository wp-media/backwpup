<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $class   Optional. Additional CSS classname . Default: null.
 * @var string  $abortUrl   Optional. The URL to abort the job. Default: null.
 */
# CSS
$class = $class ?? "";
$classStep = $class."-step";
$abortUrl = $abortUrl ?? "";
?>
<div class="mt-6">
  <div class="flex gap-2 <?php echo $class; ?>">
    <?php BackWPupHelpers::component("progress-bar", []); ?>

    <?php
      BackWPupHelpers::component("navigation/link", [
        "type" => "secondary",
        "content" => __("Display Logs", 'backwpup'),
        "url" => "#TB_inline?height=440&amp;inlineId=tb-showworking&amp;width=640&amp;height=137",
        "font" => "small",
        "class" => "thickbox",
      ]);
    ?>



    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "label" => __("Abort", 'backwpup'),
      "font" => "small",
      "class" => "max-md:hidden",
      "trigger" => "abortbutton",
      "data" => [
        "url" => $abortUrl,
      ],
      "identifier" => "abortbutton",
    ]);
    ?>

      <?php
      BackWPupHelpers::component("form/button", [
          "type" => "secondary",
          "label" => __("Close", 'backwpup'),
          "font" => "small",
          "class" => "max-md:hidden",
          "identifier" => "showworkingclose",
      ]);
      ?>

  </div>

  <p class="mt-1 text-base font-title">
    <span class="js-backwpup-current-file <?php echo $classStep; ?>"></span>
  </p>
</div>