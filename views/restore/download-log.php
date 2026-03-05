<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restore download log view.
 *
 * @var \stdClass $bind
 */
?>
<a class="button" href="<?php echo esc_url( $bind->link ); ?>">
	<?php echo esc_html( $bind->label ); ?>
</a>
