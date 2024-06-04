/**
 * Decompress
 */

window.BWU = window.BWU || {};
window.BWU.Restore = window.BWU.Restore || {};

(
	function iife( BWU, _, $, backwpupRestoreLocalized ) {
		'use strict';

		if ( !( 'EventSource' in window ) ) {
			// eslint-disable-line
			console.warn( backwpupRestoreLocalized.noEventSourceInWindowObject );

			return;
		}

		var Decompress = {

			/**
			 * On Message Callback
			 *
			 * @param {object} message The data returned by the server.
			 *
			 * @returns {BWU} this for chaining
			 */
			onMessage: function ( message ) {
				var data = JSON.parse( message.data );

				if ( data.fileName ) {
					$( '#onstep' )
						.text( data.fileName );

					$( '#progressstep' )
						.css( {
							width: BWU.Restore.Functions.calculatePercentage( data.index, data.remains ) + '%'
						} )
						.text( BWU.Restore.Functions.calculatePercentage( data.index, data.remains ) + '%' );
				}

				if ( BWU.States.DONE === data.state ) {
					this.eventSource.close();

					$( 'body' ).trigger( this.ACTION_DECOMPRESS_SUCCESS, message );
				}

				return this;
			},

			/**
			 * On log error
			 *
			 * @param message
			 * @param status
			 * @param error
			 * @returns {Decompress}
			 */
			onError: function ( message, status, error ) {
				var data = JSON.parse( message.data );

				this.eventSource.close();

				if ( data.message !== BWU.States.NEED_DECRYPTION_KEY ) {
					BWU.Functions.printMessageError( data.message, $( '#restore_step' ) );
				}

				$( 'body' ).trigger( this.ACTION_DECOMPRESS_FAILED, message, error );

				return this;
			},

			/**
			 * Decompress
			 *
			 * @returns {BWU} this for chaining
			 */
			decompress: function () {

				this.eventSource = new EventSource(
					this.url
					+ '?action=decompress_upload&context=event_source&controller=job&'
					+ 'backwpup_action_nonce=' + this.nonce
					+ '&file_path=' + this.filePath
				);
				this.eventSource.onmessage = this.onMessage;
				this.eventSource.addEventListener( 'log', this.onError );

				$( '#upload_progress' )
					.text( backwpupRestoreLocalized.extractingArchive );
				$( '#progressstep' )
					.css( { width: '0%' } )
					.text( '0%' );

				return this;
			},

			/**
			 * Construct
			 *
			 * @param {string} url The url to call.
			 * @param {string} nonce The nonce value.
			 * @param {string} filePath The file path to decompress.
			 *
			 * @returns {BWU} this for chaining
			 */
			construct: function ( url, nonce, filePath ) {
				_.bindAll(
					this,
					'onMessage',
					'onError',
					'decompress'
				);

				this.eventSource = null;
				this.url = url;
				this.nonce = nonce;
				this.filePath = _.isEmpty( filePath ) ? '' : filePath;

				return this;
			}
		};

		/**
		 * Factory
		 *
		 * @constructor
		 *
		 * @param {string} url The url to call.
		 * @param {string} nonce The nonce value.
		 * @param {string} filePath The file path to decompress.
		 *
		 * @returns {Decompress}
		 */
		BWU.Restore.FactoryDecompress = function FactoryDecompress( url, nonce, filePath ) {
			return Object
				.create( Decompress, {
					ACTION_DECOMPRESS_SUCCESS: BWU.Functions.makeConstant( 'backwpup.decompress_success' ),
					ACTION_DECOMPRESS_FAILED: BWU.Functions.makeConstant( 'backwpup.decompress_failed' )
				} )
				.construct( url, nonce, filePath );
		};
	}( window.BWU, window._, window.jQuery, window.backwpupRestoreLocalized )
);
