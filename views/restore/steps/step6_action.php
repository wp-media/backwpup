<?php

use Inpsyde\Restore\Log\Log;

/** @var \stdClass $bind */
if ( $bind->errors ) {
	/** @var Log $error */
	foreach ( $bind->errors as $error ) {
		$message    = $error->message();
		$subMessage = substr( $error->message(), 0, 32 );
		$message    = count( $message ) > $subMessage ? $message . '&hellip;' : $message; ?>
		<div class="notice notice-error below-h2">
			<p>
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
	}
}
