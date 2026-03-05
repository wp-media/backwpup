<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

# Current page.
$requested_page = filter_input( INPUT_GET, 'page_num', FILTER_VALIDATE_INT );
$current_page = isset( $current_page ) ? (int) $current_page : ( ( null !== $requested_page && false !== $requested_page ) ? (int) $requested_page : 1 );

# Max pages.
$max_pages = isset( $max_pages ) ? (int) $max_pages : 10;
$current_page = max( 1, min( $current_page, $max_pages ) );

# Prev and next page
$prev_status = $current_page === 1;
$next_status = $current_page === $max_pages;

# JS
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";

# CSS
$class = $class ?? "";
$one_is_shown = false;

?>
<nav class="<?php echo esc_attr( BackWPupHelpers::clsx( "flex gap-2", $trigger, $class ) ); ?>">
  <?php if ( $current_page !== 1): ?>
    <?php BackWPupHelpers::component("navigation/pagination-item", ["arrow" => "left", "disabled" => $prev_status, "page_num" => $current_page - 1]); ?>
  <?php endif; ?>

  <?php if ($current_page > 1) : ?>
    <?php $one_is_shown = true; ?>
	  <?php BackWPupHelpers::component("navigation/pagination-item", ["page_num" => 1]); ?>
    <?php if ($current_page > 3 && true ) : ?>
		  <?php BackWPupHelpers::component("navigation/pagination-item", ["dots" => true]); ?>
    <?php endif; ?>
  <?php endif; ?>

  <?php for ($i = max(1, $current_page - 1); $i <= min($current_page + 1, $max_pages); $i++) : ?>
    <?php if ( $one_is_shown && $i === 1 ) continue; ?>
	  <?php BackWPupHelpers::component("navigation/pagination-item", ["page_num" => $i, "active" => $current_page === $i]); ?>
  <?php endfor; ?>

  <?php if ($current_page < $max_pages - 1) : ?>
    <?php if ($current_page < $max_pages - 2) : ?>
		  <?php BackWPupHelpers::component("navigation/pagination-item", ["dots" => true]); ?>
    <?php endif; ?>
	  <?php BackWPupHelpers::component("navigation/pagination-item", ["page_num" => $max_pages]); ?>
  <?php endif; ?>

  <?php if ( $current_page !== $max_pages ) : ?>
	  <?php BackWPupHelpers::component("navigation/pagination-item", ["arrow" => "right", "disabled" => $next_status, "page_num" => $current_page + 1]); ?>
  <?php endif; ?>
</nav>
