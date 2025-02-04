<?php
  use BackWPup\Utils\BackWPupHelpers;
?>
<?php BackWPupHelpers::component("containers/form-start", [
  "scrollable" => false,
  "identifier" => "backwpup-onboarding-form",
]); ?>
<?php BackWPupHelpers::component("form/hidden", [
    "name" => "onboarding",
    "value" => "1",
]); ?>
<div class="max-w-screen-xl flex flex-col gap-4">

  <?php
  BackWPupHelpers::component("app/header", [
    "title" => __("Ready to set up your first backup?", 'backwpup'),
    "subtitle" => __("You’re just a few steps away from creating a new backup of your site.", 'backwpup'),
  ]);
  ?>

  <div class="flex gap-4">
    <?php
    BackWPupHelpers::component("app/steps", [
      "current_step" => 1,
    ]);
    ?>

    <div class="p-8 bg-grey-100 rounded-lg flex flex-col flex-auto" id="backwpup-onboarding-panes">
      <article class="flex flex-col flex-auto" data-step="1">
        <?php BackWPupHelpers::component("onboarding/step1"); ?>
      </article>

      <article class="hidden flex-col flex-auto" data-step="2">
        <?php BackWPupHelpers::component("onboarding/step2"); ?>
      </article>

      <article class="hidden flex-col flex-auto" data-step="3">
        <?php BackWPupHelpers::component("onboarding/step3"); ?>
      </article>
    </div>
  </div>
</div>

<?php
BackWPupHelpers::component("containers/sidebar", [
  "is_in_form" => true,
]);
BackWPupHelpers::component("containers/modal");
BackWPupHelpers::component("containers/form-end");
?>