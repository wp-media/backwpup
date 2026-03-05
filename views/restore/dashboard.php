<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restore dashboard view.
 *
 * @var \stdClass $bind
 */
?>
<nav>
	<ul>
		<li>
			<?php
			if ( $bind->downloader->can_be_downloaded() ) {
				$bind->downloader->view();
			}
			?>
		</li>
	</ul>
</nav>
