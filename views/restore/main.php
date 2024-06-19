<?php /** @var \stdClass $bind */ ?>
<div class="mdl-grid bwpr-content">
	<?php

	/*
	 * Before main content.
	 *
	 * @param object $bind the object bind for this view
	 */
	do_action( 'backwpup_restore_before_main_content', $bind );
	?>

	<div id="bwpr-action-card" class="bwpr-graphs mdl-shadow--2dp mdl-color--white mdl-cell mdl-cell--8-col">
		<?php
		/*
		 * Main Content Restore.
		 *
		 * @param object $bind the object bind for this view
		 */
		do_action( 'backwpup_restore_main_content', $bind );
		?>
	</div>

	<?php
	/*
	 * After Restore Main Content.
	 *
	 * @param object $bind the object bind for this view
	 */
	do_action( 'backwpup_restore_after_main_content', $bind );
	?>
</div>
