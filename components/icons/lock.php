<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$size = $size ?? '21';
?>
<svg width="<?php echo esc_attr( $size ); ?>" height="<?php echo esc_attr( $size ); ?>" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M7 9.5V6.5C7 4.567 8.567 3 10.5 3C12.433 3 14 4.567 14 6.5V9.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
  <rect x="4" y="9.5" width="13" height="9" rx="2" fill="currentColor"/>
  <circle cx="10.5" cy="14" r="1.5" fill="white"/>
</svg>
