<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var int     $number       The current step number. Default 1.
 * @var string  $title        The current step title. Default "".
 * @var string  $description  The current step description. Default "".
 * @var bool    $is_reached   Whether the step is reached. Default false.
 * @var bool    $is_active    Whether the step is active. Default false.
 * @var bool    $is_last      Whether the step is the last one. Default false.
 */

# Defaults
$number = $number ?? 1;
$title = $title ?? "";
$description = $description ?? "";
$is_reached = $is_reached ?? false;
$is_active = $is_active ?? false;
$is_last = $is_last ?? false;

# Classes
$line_styles = "relative flex items-center gap-4 before:absolute before:w-[1px] before:bottom-[50%] before:left-5";
$line_styles_not_reached = "before:bg-primary-base";
$line_styles_reached = "before:bg-secondary-base";

$first_step_line_styles = "pt-20 pb-10 before:top-0";
$other_step_line_styles = "py-10 before:top-[-50px]";
$last_step_line_styles = "pt-10 pb-20 before:top-[-50px] after:absolute after:w-[1px] after:bg-primary-base after:left-5 after:bottom-[-100px] after:top-[50%]";

$reached_step_line_styles = "before:bg-secondary-base";

$circle_styles = "relative z-10 shrink-0 rounded-full h-10 w-10 text-white flex items-center justify-center text-lg before:absolute before:z-0 before:inset before:z-0 before:w-14 before:h-14 before:border-8 before:border-primary-darker before:rounded-full";
$circle_styles_not_reached = "bg-primary-base";
$circle_styles_reached = "bg-secondary-base";
$circle_styles_active = "after:absolute after:z-10 after:inset after:w-12 after:h-12 after:border-secondary-base after:border after:rounded-full after:animate-pulse";

$bloc_styles = BackWPupHelpers::clsx(
  $line_styles,
  ($is_reached ? $line_styles_reached : $line_styles_not_reached),
  ($number === 1 ? $first_step_line_styles : false),
  ($number > 1 && !$is_last ? $other_step_line_styles : false),
  ($is_last ? $last_step_line_styles : false),
);

$step_styles = BackWPupHelpers::clsx(
  $circle_styles,
  ($is_active ? $circle_styles_active : false),
  ($is_reached ? " " . $circle_styles_reached : " " . $circle_styles_not_reached)
);

?>
<article class="<?php echo $bloc_styles; ?>" data-step="<?php echo $number; ?>">
  <div class="<?php echo $step_styles; ?>"><?php echo $number; ?></div>
  <div>
    <h2 class="text-xl font-bold text-white font-title"><?php echo $title; ?></h2>
    <p class="text-grey-400 text-base"><?php echo $description; ?></p>
  </div>
</article>