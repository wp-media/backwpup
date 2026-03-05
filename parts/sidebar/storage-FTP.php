<?php
use BackWPup\Utils\BackWPupHelpers;
use WPMedia\BackWPup\Plugin\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$job_id = $job_id ?? null;
BackWPupHelpers::component(
	'closable-heading',
	[
		'title' => __( 'FTP Settings', 'backwpup' ),
		'type'  => 'sidebar',
	]
	);

if ( null === $job_id || empty( $job_id ) ) {
	$job_id     = get_site_option( Plugin::FIRST_JOB_ID, false );
	$is_in_form = true;
}
$ftpdir        = BackWPup_Option::get( $job_id, 'ftpdir', trailingslashit( sanitize_title_with_dashes( get_bloginfo( 'name' ) ) ) );
$ftpmaxbackups = esc_attr( BackWPup_Option::get( $job_id, 'ftpmaxbackups', 15 ) );

$current_ftp_type = 'ftp';
if ( function_exists( 'ftp_ssl_connect' ) && BackWPup_Option::get( $job_id, 'ftpssl', false ) ) {
	$current_ftp_type = 'ftps';
}
if ( class_exists( phpseclib3\Net\SFTP::class ) && BackWPup::is_pro() ) {
	if ( BackWPup_Option::get( $job_id, 'ftpssh', false ) ) {
		$current_ftp_type = 'sftp';
	}
	if ( BackWPup_Option::get( $job_id, 'ftpssh', false ) && BackWPup_Option::get( $job_id, 'ftpsshprivkey', '' ) ) {
		$current_ftp_type = 'sftppk';
	}
}
?>

<?php if ( isset( $is_in_form ) && ( false === $is_in_form || 'false' === $is_in_form ) ) : ?>
	<p>
	<?php
	BackWPupHelpers::component(
		'form/button',
		[
			'type'          => 'link',
			'label'         => __( 'Back to Storages', 'backwpup' ),
			'icon_name'     => 'arrow-left',
			'icon_position' => 'before',
			'trigger'       => 'load-and-open-sidebar',
			'display'       => 'storages',
			'data'          => [
				'job-id'     => $job_id,
				'block-type' => 'children',
				'block-name' => 'sidebar/storages',
			],
		]
		);
	?>
	</p>
<?php endif; ?>

<?php BackWPupHelpers::component( 'containers/scrollable-start' ); ?>

<div class="rounded-lg p-4 bg-grey-100">
	<?php
	BackWPupHelpers::component(
		'heading',
		[
			'level' => 2,
			'title' => __( 'Server and Login', 'backwpup' ),
			'font'  => 'small',
			'class' => 'mb-4',
		]
		);
	?>

	<div class="flex flex-col gap-2">

		<?php
		$options = [
			'ftp' => __( 'FTP (unencrypted)', 'backwpup' ),
		];
		if ( function_exists( 'ftp_ssl_connect' ) ) {
			$options['ftps'] = __( 'FTPS (Explicit SSL-FTP)', 'backwpup' );
		}
		if ( class_exists( phpseclib3\Net\SFTP::class ) && BackWPup::is_pro() ) {
			$options['sftp']   = __( 'SFTP (Password)', 'backwpup' );
			$options['sftppk'] = __( 'SFTP (SSH Key)', 'backwpup' );
		}
		BackWPupHelpers::component(
			'form/select',
			[
				'name'        => 'ftpcontype',
				'identifier'  => 'ftpcontype',
				'label'       => __( 'Server type', 'backwpup' ),
				'value'       => $current_ftp_type,
				'required'    => true,
				'options'     => $options,
				'tooltip'     => __( 'FTPS is only available if your servers PHP is build with OpenSSL. SFTP connections requires BackWPup Pro.', 'backwpup' ),
				'tooltip_pos' => 'right',
			]
			);
		?>

	<div class="flex gap-2">
		<div class="flex-auto">
		<?php
		BackWPupHelpers::component(
			'form/text',
			[
				'name'       => 'ftphost',
				'identifier' => 'ftphost',
				'label'      => __( 'Server', 'backwpup' ),
				'value'      => esc_attr( BackWPup_Option::get( $job_id, 'ftphost' ) ),
				'required'   => true,
			]
			);
		?>
		</div>
		<div class="w-[88px]">
		<?php
		BackWPupHelpers::component(
			'form/text',
			[
				'type'       => 'number',
				'min'        => 1,
				'max'        => 66000,
				'name'       => 'ftphostport',
				'identifier' => 'ftphostport',
				'label'      => __( 'Port', 'backwpup' ),
				'value'      => esc_attr( BackWPup_Option::get( $job_id, 'ftphostport', 21 ) ),
				'required'   => true,
			]
			);
		?>
		</div>
	</div>

	<?php
	BackWPupHelpers::component(
		'form/text',
		[
			'name'       => 'ftpuser',
			'identifier' => 'ftpuser',
			'label'      => __( 'Username', 'backwpup' ),
			'value'      => esc_attr( BackWPup_Option::get( $job_id, 'ftpuser' ) ),
			'required'   => true,
		]
		);
	?>

		<?php
		BackWPupHelpers::component(
			'form/textarea',
			[
				'name'        => 'ftpsshprivkey',
				'identifier'  => 'ftpsshprivkey',
				'label'       => __( 'Private SSH key', 'backwpup' ),
				'hidden'      => ! ( 'sftppk' === $current_ftp_type ),
				'value'       => esc_attr(
					BackWPup_Encryption::decrypt( BackWPup_Option::get( $job_id, 'ftpsshprivkey' ) )
					),
				'tooltip'     => __( "This can be a local private key file like:<br /> <code>~/.ssh/id_rsa</code><br /> <br />or private key content like:<br /><code>\n-----BEGIN RSA PRIVATE KEY-----\n ABCDefgHIJ...\n -----END RSA PRIVATE KEY-----\n</code>", 'backwpup' ),
				'tooltip_pos' => 'right',
			]
			);
		?>

	<?php
	BackWPupHelpers::component(
		'form/text',
		[
			'name'       => 'ftppass',
			'identifier' => 'ftppass',
			'type'       => 'password',
			'label'      => __( 'Password', 'backwpup' ),
			'value'      => esc_attr(
				BackWPup_Encryption::decrypt( BackWPup_Option::get( $job_id, 'ftppass' ) )
				),
		]
		);
	?>

	<?php
	BackWPupHelpers::component(
		'form/text',
		[
			'name'       => 'ftptimeout',
			'identifier' => 'ftptimeout',
			'type'       => 'number',
			'min'        => 1,
			'max'        => 300,
			'label'      => __( 'Timeout for connection (in seconds)', 'backwpup' ),
			'value'      => esc_attr( BackWPup_Option::get( $job_id, 'ftptimeout', 90 ) ),
			'required'   => true,
		]
		);
	?>

	<?php
	BackWPupHelpers::component(
		'form/checkbox',
		[
			'name'       => 'ftppasv',
			'identifier' => 'ftppasv',
			'checked'    => BackWPup_Option::get( $job_id, 'ftppasv', true ),
			'label'      => __( 'Use FTP passive mode', 'backwpup' ),
			'hidden'     => ! ( 'ftp' === $current_ftp_type || 'ftps' === $current_ftp_type ),
		]
		);
	?>
	</div>
</div>

<div class="rounded-lg p-4 bg-grey-100">
	<?php
	BackWPupHelpers::component(
		'heading',
		[
			'level' => 2,
			'title' => __( 'Backup Settings', 'backwpup' ),
			'font'  => 'small',
			'class' => 'mb-4',
		]
		);
	?>

	<div class="flex flex-col gap-2">
	<?php
	BackWPupHelpers::component(
		'form/text',
		[
			'name'       => 'ftpdir',
			'identifier' => 'ftpdir',
			'label'      => __( 'Folder to store files in', 'backwpup' ),
			'value'      => $ftpdir,
			'required'   => true,
		]
		);
	?>

	<?php
	BackWPupHelpers::component(
		'form/text',
		[
			'name'       => 'ftpmaxbackups',
			'identifier' => 'ftpmaxbackups',
			'type'       => 'number',
			'min'        => 0,
			'label'      => __( 'Max backups to retain', 'backwpup' ),
			'value'      => $ftpmaxbackups,
		]
		);
	?>

	<?php
	BackWPupHelpers::component(
		'alerts/info',
		[
			'type'    => 'alert',
			'font'    => 'xs',
			'content' => __( 'Limits the number of stored backups. When exceeded, the oldest backup is removed. Setting this to 0 keeps unlimited backups and may increase storage usage.', 'backwpup' ),
		]
		);
	?>

		<?php
		BackWPupHelpers::component(
		'alerts/info',
		[
			'type'    => 'alert',
			'font'    => 'xs',
			'content' => __( 'Files belonging to this job are now tracked. Old backup archives which are untracked will not be automatically deleted.', 'backwpup' ),
		]
		);
		?>
	</div>
</div>

<?php BackWPupHelpers::component( 'containers/scrollable-end' ); ?>

<div class="flex flex-col gap-2">
	<?php
	BackWPupHelpers::component(
		'form/button',
		[
			'type'       => 'primary',
			'label'      => __( 'Save & Test connection', 'backwpup' ),
			'full_width' => true,
			'trigger'    => 'test-FTP-storage',
			'data'       => [
				'storage' => 'sftp',
				'job-id'  => $job_id,
			],
		]
		);
	?>
</div>