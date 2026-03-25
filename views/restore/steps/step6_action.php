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
		$message     = $log_error->message();
		$sub_message = substr( $log_error->message(), 0, 32 );
		$message     = count( $message ) > $sub_message ? $message . '&hellip;' : $message; ?>
		<div class="notice notice-error below-h2">
			<p>
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
	}
}
