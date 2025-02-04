<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var int     $max_pages      The total number of pages.  
 * @var string  $label          The label for the toggle. Default: null 
 * @var bool    $checked        Optional. True to check the toggle. Default: false.    
 * @var string  $tooltip        Optional. The tooltip content. Default: "".    
 * @var bool    $multiline      Optional. If true, aligns checkbox on top. Default: false.
 * @var string  $trigger        Optional. For JS. The CSS classname for jQuery. Default: null. 
 * @var string  $class   Optional. Additional CSS classname . Default: null.
 * @var int     $current_page   Optional. The current page number.
 */

# Current page 
$current_page = isset( $current_page ) ? intval( $current_page ) : (isset( $_GET["page_num"] ) ? intval( $_GET["page_num"] ) : 1);

# Max pages 
$max_pages = (int) $max_pages ?? 10;

# Prev and next page
$prev_status = $current_page === 1;
$next_status = $current_page === $max_pages;

# JS
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";

# CSS
$class = $class ?? "";
$one_is_shown = false;

?>
<nav class="<?php echo BackWPupHelpers::clsx("flex gap-2", $trigger, $class); ?>">
  <?php if ( $current_page !== 1): ?>
    <?= BackWPupHelpers::component("navigation/pagination-item", ["arrow" => "left", "disabled" => $prev_status, "page_num" => $current_page - 1], true); ?>
  <?php endif; ?>

  <?php if ($current_page > 1) : ?>
    <?php $one_is_shown = true; ?>
    <?= BackWPupHelpers::component("navigation/pagination-item", ["page_num" => 1], true); ?>
    <?php if ($current_page > 3 && true ) : ?>
      <?= BackWPupHelpers::component("navigation/pagination-item", ["dots" => true], true); ?>
    <?php endif; ?>
  <?php endif; ?>

  <?php for ($i = max(1, $current_page - 1); $i <= min($current_page + 1, $max_pages); $i++) : ?>
    <?php if ( $one_is_shown && $i === 1 ) continue; ?>
    <?= BackWPupHelpers::component("navigation/pagination-item", ["page_num" => $i, "active" => $current_page === $i], true); ?>
  <?php endfor; ?>

  <?php if ($current_page < $max_pages - 1) : ?>
    <?php if ($current_page < $max_pages - 2) : ?>
      <?= BackWPupHelpers::component("navigation/pagination-item", ["dots" => true], true); ?>
    <?php endif; ?>
    <?= BackWPupHelpers::component("navigation/pagination-item", ["page_num" => $max_pages], true); ?>
  <?php endif; ?>

  <?php if ( $current_page !== $max_pages ) : ?>
    <?= BackWPupHelpers::component("navigation/pagination-item", ["arrow" => "right", "disabled" => $next_status, "page_num" => $current_page + 1], true); ?>
  <?php endif; ?>
</nav>