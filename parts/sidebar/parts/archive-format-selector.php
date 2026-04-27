<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var string $label The label of the select.
 * @var string $archiveformat The archive format selected
 */

$format_labels  = [
	'.tar'    => __( 'TAR (.tar)', 'backwpup' ),
	'.tar.gz' => __( 'TAR GZIP (.tar.gz)', 'backwpup' ),
	'.zip'    => __( 'ZIP (.zip)', 'backwpup' ),
];
$format_options = [];
foreach ( BackWPup_Option::get_allowed_archive_formats() as $format ) {
	if ( isset( $format_labels[ $format ] ) ) {
		$format_options[ $format ] = $format_labels[ $format ];
	}
}

$is_readonly = count( $format_options ) === 1;

BackWPupHelpers::component(
	'form/select',
	[
		'name'     => 'archiveformat',
		'label'    => $label,
		'value'    => $archiveformat,
		'trigger'  => 'format-job',
		'options'  => $format_options,
		'readonly' => $is_readonly,
	]
);

if ( $is_readonly ) {
	BackWPupHelpers::component(
		'alerts/info',
		[
			'type'    => 'info',
			'font'    => 'small',
			'content' => __( 'Only one archive format is available on this server.', 'backwpup' ),
		]
	);
}
?>

<div class="js-backwpup-format-job-show-if-zip">
	<?php
	BackWPupHelpers::component(
		'alerts/info',
		[
			'type'    => 'alert',
			'font'    => 'small',
			'content' => __( 'ZIP format may increase server load (higher CUP & RAM usage) during backup.', 'backwpup' ),
		]
		);
	?>
</div>
