<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
  <div class="flex gap-2 <?php echo esc_attr( $class ); ?>">
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
    $btn_args = [
        "type" => "secondary",
        "label" => __("Close", 'backwpup'),
        "font" => "small",
        "class" => "max-md:hidden",
        "identifier" => "showworkingclose",
        "data" => [
	        "bwpup_redirect_url" => esc_url( network_admin_url( 'admin.php?page=backwpup' ) ),
        ],
    ];
    BackWPupHelpers::component("form/button", $btn_args);
    ?>

  </div>

  <p class="mt-1 text-base font-title">
    <span class="js-backwpup-current-file <?php echo esc_attr( $classStep ); ?>"></span>
  </p>
</div>
