/**
 * Restore Database
 */

window.BWU = window.BWU || {};
window.BWU.Restore = window.BWU.Restore || {};
window.BWU.Restore.Factory = window.BWU.Restore.Factory || {};

(
	function iife( BWU, _, $ ) {
		'use strict';

		var Database = {
			/**
			 * On Test Success
			 *
			 * @param {*} response Response from the server.
			 * @param textStatus The status of the request.
			 * @param jqXHR The XHR object.
			 *
			 * @returns {BWU} this for chainging
			 */
			onTestSuccess: function ( response, textStatus, jqXHR ) {
				BWU.Functions.printMessageSuccess( jqXHR.responseJSON.data.message, $( '#restore_step' ) );

				// Show the charset field to the user if no valid charset has been found.
				if ( !_.isUndefined( jqXHR.responseJSON.data.charset )
					&& false === jqXHR.responseJSON.data.charset
				) {
					$( '#db-charset-field' )
						.removeClass( 'hidden' )
						.animate( {
							opacity: 1
						}, 200, 'linear' );
				}

				return this;
			},

			/**
			 * On Connection Fail
			 *
			 * @param {object} jqXHR The XHR object
			 *
			 * @returns {BWU} this for chainging
			 */
			onConnectionFail: function ( jqXHR ) {
				BWU.Functions.printMessageError( jqXHR.responseJSON.data.message, $( '#restore_step' ) );

				return this;
			},

			/**
			 * Connect
			 *
			 * @param {function} doneCallback The callback to call on done.
			 * @param {function} successCallback The callback to call on success.
			 *
			 * @returns {BWU}
			 */
			connect: function ( successCallback ) {
				var that = this;

				$.ajax( {
					type: 'POST',
					url: this.url,
					cache: false,
					dataType: 'json',
					data: {
						controller: 'job',
						action: 'db_test',
						db_settings: this.settings,
						backwpup_action_nonce: this.nonce
					}
				} )
					.done( function ( response, textStatus, jqXHR ) {
						if ( true === response.success ) {
							_.isFunction( successCallback ) && successCallback( response, textStatus, jqXHR );
						} else {
							that.onConnectionFail( jqXHR );
						}
					} )
					.fail( this.onConnectionFail );

				return this;
			},

			/**
			 * Test Connection
			 *
			 * @returns {BWU} this for chaining
			 */
			testConnection: function () {
				this.connect( this.onTestSuccess );

				return this;
			},

			/**
			 * Save Connection Settings and load next step
			 *
			 * @param {function} successCallback The callback to call on success.
			 * @returns {BWU} this for chaining
			 */
			saveConnectionSettings: function ( successCallback ) {
				this.connect( successCallback );

				return this;
			},

			/**
			 * Initialize
			 *
			 * @returns {BWU} this for chaining
			 */
			init: function () {
				return this;
			},

			/**
			 * Construct
			 *
			 * @param {string} url The url where call the server.
			 * @param {string} nonce The nonce for the request.
			 * @param {Object} settings The database creadentials.
			 *
			 * @returns {BWU} this for chaining
			 */
			construct: function ( url, nonce, settings ) {
				_.bindAll(
					this,
					'onTestSuccess',
					'onConnectionFail',
					'connect',
					'testConnection',
					'saveConnectionSettings',
					'init'
				);

				this.url = url;
				this.nonce = nonce;
				this.settings = settings;

				return this;
			}
		};

		/**
		 * Factory Database
		 *
		 * @constructor
		 *
		 * @param {string} url The url where call the server.
		 * @param {string} nonce The nonce for the request.
		 * @param {Object} settings The database creadentials.
		 *
		 * @returns {Database} the instance of the object.
		 */
		BWU.Restore.FactoryDatabase = function FactoryDatabase( url, nonce, settings ) {
			return Object
				.create( Database )
				.construct( url, nonce, settings );
		};
	}( window.BWU, window._, window.jQuery )
);
