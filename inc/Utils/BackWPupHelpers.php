<?php

namespace BackWPup\Utils;

use BackWPup;
use BackWPup_Pro_Destination_HiDrive_Api;
use BackWPup_Pro_Destination_HiDrive_Authorization;
use BackWPup_Pro_Destination_HiDrive_Request;

class BackWPupHelpers {

	/**
	 * Render or return a component's HTML.
	 *
	 * @param string $component The name of the component file to include.
	 * @param array  $args      Variables to pass to the component.
	 * @param bool   $return    Whether to return the HTML instead of echoing it.
	 *
	 * @return string|null HTML content of the component if `$return` is true; otherwise, null.
	 */
	public static function component( string $component, array $args = [], bool $return = false ) { // @phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.returnFound
		$filename = sanitize_text_field( $component ) . '.php';
		$filename = str_replace( '../', '', $filename );
		$path     = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/components/' . $filename;
		// Check if Pro version is active and try pro path if file not found.
		if ( ! file_exists( $path ) && BackWPup::is_pro() ) {
			$path = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/pro/components/' . $filename;
		}
		if ( ! file_exists( $path ) ) {
			error_log( "Component file not found: {$path}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return null;
		}

		// Extract the arguments for the component context.
		// Avoid overwriting built-in variables.
		extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		// Start output buffering.
		ob_start();
		include $path; // Execute the PHP file in the local context of extracted arguments.
		$output = ob_get_clean();

		if ( $return ) {
			return $output; // Return the output content for further processing.
		}
		// Directly echo the content if $return is false (default behavior).
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return; //phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
	}

	/**
	 * Render or return a component's children HTML.
	 *
	 * @param string $component The name of the component file to include.
	 * @param bool   $return    Whether to return the HTML instead of echoing it.
	 * @param array  $args      Variables to pass to the children.
	 *
	 * @return string|null HTML content of the component if `$return` is true; otherwise, null.
	 */
	public static function children( string $component, bool $return = false, array $args = [] ) { // @phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.returnFound
		$filename = sanitize_text_field( $component ) . '.php';
		$filename = str_replace( '../', '', $filename );
		$path     = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/parts/' . $filename;
		// Check if Pro version is active and try pro path if file not found.
		if ( ! file_exists( $path ) && BackWPup::is_pro() ) {
			$path = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/pro/parts/' . $filename;
		}

		if ( ! file_exists( $path ) ) {
			return;
		}
		// Extract the arguments for the component context.
		// Avoid overwriting built-in variables.
		extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		// Start output buffering.
		ob_start();
		include $path; // Execute the PHP file in the local context of extracted arguments.
		$output = ob_get_clean();

		if ( $return ) {
			return $output; // Return the output content for further processing.
		}
		// Directly echo the content if $return is false (default behavior).
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return; //phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
	}

	/**
	 * Combines class names into a single string.
	 *
	 * @param mixed ...$classes Variable list of class names which can be strings or arrays.
	 * @return string A space-separated string of class names.
	 */
	public static function clsx( ...$classes ) {
		return implode( ' ', array_filter( $classes ) );
	}

	/**
	 * Process backup items.
	 *
	 * @param array  $items    The list of backup items.
	 * @param array  $job_data The job data to merge with each item.
	 * @param string $dest     The destination of the backup.
	 * @param int    $page     The current page for pagination.
	 *
	 * @return array The processed items.
	 */
	public static function process_backup_items( array $items, array $job_data, string $dest, int $page ): array {
		array_walk(
			$items,
			function ( &$item ) use ( $job_data, $dest, $page ) {
				$item = array_merge( $item, $job_data, [ 'stored_on' => $dest ] );

				// Parse the filename to get the type of backup.
				$filename = pathinfo( $item['filename'] )['filename'];
				if ( stripos( $item['filename'], '.tar.gz' ) === strlen( $item['filename'] ) - 7 ) {
					$filename = substr( $item['filename'], 0, -7 );
				} elseif ( stripos( $item['filename'], '.tar.bz2' ) === strlen( $item['filename'] ) - 8 ) {
					$filename = substr( $item['filename'], 0, -8 );
				}
				$filename_parts = explode( '_', $filename );

				if ( count( $filename_parts ) > 1 ) {
					$item['data'] = (array) explode( '-', end( $filename_parts ) );
				}

				$local_file      = untrailingslashit( BackWPup::get_plugin_data( 'TEMP' ) ) . "/{$item['filename']}";
				$downloadhref    = '';
				$downloadurl     = '';
				$downloadtrigger = '';

				if ( 'HIDRIVE' === $dest && $item['filesize'] > 10485760 ) {
					$request       = new BackWPup_Pro_Destination_HiDrive_Request();
					$authorization = new BackWPup_Pro_Destination_HiDrive_Authorization( $request );
					$api           = new BackWPup_Pro_Destination_HiDrive_Api( $request, $authorization );
					$response      = $api->temporalDownloadUrl( $job_data['id'], $item['file'] );
					$respons_body  = json_decode( (string) $response['body'] );

					if ( isset( $respons_body->url ) ) {
						$downloadurl     = $respons_body->url;
						$downloadhref    = $respons_body->url;
						$downloadtrigger = 'direct-download-backup';
					}
				} else {
					$downloadurl     = wp_nonce_url( $item['downloadurl'], 'backwpup_action_nonce' );
					$downloadhref    = '#TB_inline?height=300&inlineId=tb_download_file&width=640&height=412';
					$downloadtrigger = 'download-backup';
				}

				// Add the download URL and dataset.
				$item['dataset-download'] = [
					'data-jobid'       => $job_data['id'],
					'data-destination' => $dest,
					'data-file'        => $item['file'],
					'data-local-file'  => $local_file,
					'data-nonce'       => wp_create_nonce( 'backwpup_action_nonce' ),
					'data-url'         => $downloadurl,
					'data-href'        => $downloadhref,
				];
				$item['download-trigger'] = $downloadtrigger;

				// If the user can restore, add the restore URL.
				if ( current_user_can( 'backwpup_restore' ) && ! empty( $item['restoreurl'] ) ) {
					$item['dataset-restore'] = [
						'label'    => __( 'Restore Backup', 'backwpup' ),
						'data-url' => wp_nonce_url(
							add_query_arg(
								[
									'step'             => 1,
									'trigger_download' => 1,
								],
								$item['restoreurl']
							),
							'restore-backup_' . $job_data['id']
						),
					];
				} elseif ( current_user_can( 'backwpup_restore' ) ) {
					$item['dataset-restore'] = [
						'label'    => __( 'Restore Backup', 'backwpup' ),
						'data-url' => network_admin_url( 'admin.php?page=backwpuprestore' ),
					];
				}

				// If the user can delete, add the delete URL.
				if ( current_user_can( 'backwpup_backups_delete' ) ) {
					$item['dataset-delete'] = [
						'data-url' => wp_nonce_url(
							add_query_arg(
								[
									'page'          => 'backwpupbackups',
									'action'        => 'delete',
									'jobdest-top'   => $job_data['id'] . '_' . $dest,
									'backupfiles[]' => esc_attr( $item['file'] ),
									'paged'         => $page,
								],
								network_admin_url( 'admin.php' )
							),
							'bulk-backups'
						),
					];
				}
			}
		);

		return $items;
	}
}
