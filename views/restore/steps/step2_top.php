<?php
/**
 * The StdClass from TemplateLoader::createBindFromStep
 *
 * @var \stdClass $bind
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$caps      = $bind->restore_capabilities ?? null;
$has_db    = $caps ? (bool) ( $caps->has_db ?? false ) : false;
$has_files = $caps ? (bool) ( $caps->has_files ?? false ) : false;
$can_full  = $caps ? (bool) ( $caps->can_full_restore ?? false ) : false;
?>
<div class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width">
	<h3 class="hndle"><span><?php esc_html_e( 'Select Strategy', 'backwpup' ); ?></span></h3>
	<div class="inside">
		<p><?php esc_html_e( 'Select how you want to restore this backup. Available options depend on what is included in the archive.', 'backwpup' ); ?></p>

	<?php if ( $can_full ) : ?>
		<br />
	<hr>
		<h3 class="mdl-card__title-text"><?php esc_html_e( 'Full Restore', 'backwpup' ); ?></h3>
		<p>
			<?php
			wp_kses(
				__(
					'Restores everything from the backup: database and files (typically from <code>wp-content/</code>).',
					'backwpup'
				),
				[ 'code' => [] ]
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $has_db ) : ?>
		<br />
	<hr>
		<h3 class="mdl-card__title-text"><?php esc_html_e( 'Database Only', 'backwpup' ); ?></h3>
		<p><?php esc_html_e( 'Restores only the database dump. No files will be restored.', 'backwpup' ); ?></p>
	<?php endif; ?>

	<?php if ( $has_files ) : ?>
		<br />
		<hr>
		<h3 class="mdl-card__title-text"><?php esc_html_e( 'Files Only', 'backwpup' ); ?></h3>
		<p>
		<?php
		esc_html_e(
			'Restores only the files from the backup. The database will not be restored.',
			'backwpup'
		);
		?>
		</p>
	<?php endif; ?>

	<br />
		<hr>
		<p>
		<?php
		echo wp_kses(
			__( 'Note: Available strategies depend on the archive contents. If the backup contains only a database dump, you can only restore the database. If it contains only files, you can only restore files.', 'backwpup' ),
			[ 'strong' => [] ]
		);
		?>
		</p>
	</div>
</div>
