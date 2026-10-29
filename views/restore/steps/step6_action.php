<?php

use Inpsyde\Restore\Log\Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restore step action view.
 *
 * @var \stdClass $bind
 */
if ( $bind->errors ) {
	/**
	 * Restore error entry.
	 *
	 * @var Log $log_error
	 */
	foreach ( $bind->errors as $log_error ) {
		// Show the full message: count() on a string is a TypeError on PHP 8 and the
		// truncation never worked (it appended a literal "&hellip;" to the full message).
		$message = $log_error->message();
		?>
		<div class="notice notice-error below-h2">
			<p>
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
	}
}
