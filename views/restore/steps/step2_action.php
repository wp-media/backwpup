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
<div
	id="restore_step"
	class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width action"
	data-nonce="<?php echo esc_attr( $bind->nonce ); ?>">

	<?php
	if ( $bind->upload_is_archive ) {
		?>
		<h3 class="hndle">
			<span>
				<?php esc_html_e( 'Choose your restore strategy.', 'backwpup' ); ?>
			</span>
		</h3>

		<?php if ( $can_full ) : ?>
		<button
			class="button button-primary button-primary-bwp restore-select-strategy step-loader"
			data-strategy="complete restore"
			data-next-step="3">
			<?php esc_html_e( 'Full Restore', 'backwpup' ); ?>
		</button>
	<?php endif; ?>

		<?php if ( $has_db ) : ?>
		<button
			class="button button-primary button-primary-bwp restore-select-strategy step-loader"
			data-strategy="db only restore"
			data-next-step="3">
			<?php esc_html_e( 'Database Only', 'backwpup' ); ?>
		</button>
	<?php endif; ?>

		<?php if ( $has_files ) : ?>
		<button
		class="button button-primary button-primary-bwp restore-select-strategy step-loader"
		data-strategy="files only restore"
		data-next-step="5">
			<?php esc_html_e( 'Files Only', 'backwpup' ); ?>
		</button>
	<?php endif; ?>

	<?php } elseif ( $bind->upload_is_sql ) { ?>
		<h4><?php esc_html_e( 'Restore Database.', 'backwpup' ); ?></h4>

		<button
			class="button button-primary button-primary-bwp step-loader"
			data-strategy="db only restore"
			data-next-step="3">
			<?php esc_html_e( 'Continue', 'backwpup' ); ?>
		</button>

	<?php } else { ?>
		<h4 class='red'>
			<?php
			esc_html_e(
				'There seems to be a problem with the archive. It is neither an archive nor a SQL file. Try again and repeat the upload.',
				'backwpup'
			);
			?>
		</h4>
	<?php } ?>

</div>
