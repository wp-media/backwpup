<?php /** @var \stdClass $bind */ ?>
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
