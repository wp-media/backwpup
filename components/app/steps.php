<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var int  $current_step   The current step number. Default 1.
 */

# Current step
$current_step = $current_step ?? 1;

# Steps
$steps = [
  [
    "number" => 1,
    "title" => __("What?", 'backwpup'),
    "description" => __("Please select what files and/or database you want to backup", 'backwpup'),
  ],
  [
    "number" => 2,
    "title" => __("When?", 'backwpup'),
    "description" => __("Choose how often you want to backup", 'backwpup'),
  ],
  [
    "number" => 3,
    "title" => __("Where?", 'backwpup'),
    "description" => __("Select one or more areas where you want to backup ", 'backwpup'),
  ],
];

?>
<div class="bg-primary-darker rounded-2xl px-10 w-[400px] shrink-0 overflow-hidden" id="backwpup-onboarding-steps">
  <?php foreach ($steps as $step) : ?>
    <?php BackWPupHelpers::component("app/steps-item", array_merge($step, ["is_active" => $step['number'] === $current_step, "is_reached" => $current_step >= $step['number'], "is_last" => $step['number'] === count($steps)])); ?>
  <?php endforeach; ?>
</div>