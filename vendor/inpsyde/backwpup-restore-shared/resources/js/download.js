/**
 * Download
 */

window.BWU = window.BWU || {};

(
	function iife( BWU, _, $, backwpupRestoreLocalized ) {
		'use strict';

		if ( !( 'EventSource' in window ) ) {
			// eslint-disable-line
			console.warn( backwpupRestoreLocalized.noEventSourceInWindowObject );

			return this;
		}

		var Download = {

			/**
			 * On Message Callback
			 *
			 * @param {object} message The data returned by the server.
			 *
			 * @returns {BWU} this for chaining
			 */
			onMessage: function ( message ) {
				var data = JSON.parse( message.data );

				if ( data.state === BWU.States.DOWNLOADING ) {
					$( '#onstep' )
						.text( data.filename );

					$( '#progressstep' )
						.css( { width: data.download_percent + '%' } )
						.text( data.download_percent + '%' );
				}

				if ( data.state === BWU.States.DONE ) {
					this.eventSource.close();

					$( 'body' ).trigger( this.ACTION_DOWNLOAD_SUCCESS, message );
				}

				return this;
			},

			/**
			 * On Log Callback
			 *
			 * @param {object} message The data returned by the server.
			 *
			 * @returns {BWU} this for chaining
			 */
			onError: function (message) {
				var data = JSON.parse(message.data)
				var $body = $('body')
				var action = this.ACTION_DOWNLOAD_ERROR

				this.eventSource.close()

				if (data.message === BWU.States.NEED_DECRYPTION_KEY) {
					action = this.ACTION_DOWNLOAD_REQUIRE_DECRYPTION
				} else {
					BWU.Functions.printMessageError(data.message, $('#restore_step'))
				}

				$body.trigger(action, message)

				return this
			},

			/**
			 * Download
			 *
			 * @returns {BWU} this for chaining
			 */
			download: function () {

				// Some destinations such as Folder doesn't have a remote file path.
				var remoteFilePath = typeof this.remoteFilePath === 'undefined' ? '' : this.remoteFilePath;

				this.eventSource = new EventSource(
					this.url
					+ '?action=download&context=event_source&controller=job&backwpup_action_nonce='
					+ this.nonce
					+ '&source_file_path='
					+ remoteFilePath
					+ '&service='
					+ this.service
					+ '&jobid='
					+ this.jobId
					+ '&local_file_path='
					+ this.localFilePath
				);

				this.eventSource.onmessage = this.onMessage;
				this.eventSource.addEventListener( 'log', this.onError );

				return this;
			},

			/**
			 * Constructor
			 *
			 * @param url
			 * @param nonce
			 * @param remoteFilePath
			 * @param service
			 * @param jobId
			 * @param localFilePath
			 * @returns {BWU}
			 */
			construct: function ( url, nonce, remoteFilePath, service, jobId, localFilePath ) {
				_.bindAll(
					this,
					'onMessage',
					'onError',
					'download'
				);

				this.eventSource = null;
				this.url = url;
				this.nonce = nonce;
				this.remoteFilePath = remoteFilePath;
				this.service = service;
				this.jobId = jobId;
				this.localFilePath = localFilePath;

				return this;
			}
		};

		/**
		 * Factory
		 *
		 * @param url
		 * @param nonce
		 * @param remoteFilePath
		 * @param service
		 * @param jobId
		 * @param localFilePath
		 * @returns {Download}
		 * @constructor
		 */
		BWU.Restore.FactoryDownload = function FactoryDownload(
			url,
			nonce,
			remoteFilePath,
			service,
			jobId,
			localFilePath
		) {
			return Object
				.create( Download, {
					ACTION_DOWNLOAD_SUCCESS: BWU.Functions.makeConstant( 'backwpup.download_success' ),
					ACTION_DOWNLOAD_ERROR: BWU.Functions.makeConstant( 'backwpup.download_error' ),
					ACTION_DOWNLOAD_REQUIRE_DECRYPTION: BWU.Functions.makeConstant('backwpup.download_require_decryption'),
				} )
				.construct( url, nonce, remoteFilePath, service, jobId, localFilePath );
		};
	}( window.BWU, window._, window.jQuery, window.backwpupRestoreLocalized )
);
