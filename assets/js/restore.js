/**
 * Restore
 *
 * @type {*|{}}
 */

window.BWU = window.BWU || {};

(
	// eslint-disable-next-line
	function (
		BWU,
		ajaxurl,
		jsURL,
		plupload,
		_,
		$,
		backwpupRestoreLocalized
	) {
		'use strict';

		$( window ).load( function () {

			var stepID = jsURL( '?step' ) ? jsURL( '?step' ) : 1;
			var step = document.querySelector( '#restore_step' );
			var nonce = step ? step.getAttribute( 'data-nonce' ) : '';
			var strategy = BWU.Restore.FactoryStrategy( ajaxurl, nonce );
			var migrate = BWU.Restore.FactoryMigrate( ajaxurl, nonce );

			var decrypter = BWU.DecrypterFactory(
				ajaxurl,
				document.querySelector( '#decrypt_key' ),
				document.querySelector( '#decryption_key' )
			);
			var databaserestore = BWU.Restore.FactoryDatabaseRestore(
				ajaxurl,
				nonce,
				{
					onMessageCallback: function ( data ) {
						$( '#restore_progress' )
							.text( backwpupRestoreLocalized.restoringPrefix + data.message );
					},
					onSuccessCallback: function () {
						BWU.Restore.Functions.loadNextStep( 6, nonce );
					}
				}
			);
			var filesrestore = BWU.Restore.FactoryFilesRestore(
				ajaxurl,
				nonce,
				{
					onMessageCallback: function ( data ) {
						$( '#restore_progress' )
							.text( backwpupRestoreLocalized.restoringPrefix + data.message );
					},
					onSuccessCallback: function () {
						databaserestore
							.init()
							.restore();
					}
				}
			);
			var decompresser = BWU.Restore.FactoryDecompress(
				ajaxurl,
				nonce,
				jsURL( '?restore_file' )
			);
			var downloader = BWU.Restore.FactoryDownload(
				ajaxurl,
				nonce,
				jsURL( '?file' ),
				jsURL( '?service' ),
				jsURL( '?jobid' ),
				jsURL( '?restore_file' )
			);

			var bwuController = BWU.Restore.FactoryController(
				ajaxurl,
				nonce,
				jsURL,
				plupload,
				document.querySelector( '#drag-drop-area' ),
				strategy,
				databaserestore,
				filesrestore,
				decompresser,
				downloader,
				decrypter,
				migrate
			);

			if ( !bwuController ) {
				return;
			}

			// Initialize Object.
			bwuController.init();

			if ( _.isFunction( bwuController[ 'step' + stepID ] ) ) {
				// Execute the current step.
				bwuController[ 'step' + stepID ]();
			}
		} );

	}(
		window.BWU,
		window.ajaxurl,
		window.url,
		window.plupload,
		window._,
		window.jQuery,
		window.backwpupRestoreLocalized
	)
);
