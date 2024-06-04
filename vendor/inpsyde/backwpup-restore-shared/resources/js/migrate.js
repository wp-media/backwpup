/**
 * Restore Migration
 */

window.BWU = window.BWU || {};
window.BWU.Restore = window.BWU.Restore || {};
window.BWU.Restore.Factory = window.BWU.Restore.Factory || {};

(
	function iife( BWU, _, $ ) {
		'use strict';

		var Migrate = {
			/**
			 * On action fail
			 *
			 * @param {object} jqXHR The response object from the server.
			 *
			 * @returns {BWU} this for chaining
			 */
			onFail: function ( jqXHR ) {
				// Print error message.
				BWU.Functions.printMessageError( jqXHR.responseJSON.data.message, $( '#restore_step' ) );

				return this;
			},

			/**
			 * Fetch old URL
			 *
			 * @param {function} successCallback The function to execute after the URL has been retrieved.
			 *
			 * @returns {BWU} this for chaining
			 */
			retrieve: function ( successCallback ) {
				$.ajax( {
					type: 'POST',
					url: this.url,
					cache: false,
					dataType: 'json',
					data: {
						controller: 'job',
						action: 'fetch_url',
						backwpup_action_nonce: this.nonce
					}
				} )
					.done( successCallback )
					.fail( this.onFail );

				return this;
			},

			/**
			 * Save migration settings
			 *
			 * @param {string} oldUrl The URL to migrate from
			 * @param {string} newUrl The URL to migrate to
			 *
			 * @returns {BWU} this for chaining
			 */
			save: function ( oldUrl, newUrl, successCallback ) {
				$.ajax( {
					type: 'POST',
					url: this.url,
					cache: false,
					dataType: 'json',
					data: {
						controller: 'job',
						action: 'save_migration',
						old_url: oldUrl,
						new_url: newUrl,
						backwpup_action_nonce: this.nonce
					}
				} )
					.done( successCallback )
					.fail( this.onFail );

				return this;
			},

			/**
			 * Initialize
			 *
			 * @returns {BWU} this For chaining
			 */
			init: function () {

				return this;
			},

			/**
			 * Construct
			 *
			 * @param {string} url The url to call.
			 * @param {string} nonce The nonce for the server.
			 *
			 * @returns {BWU} this for chainging
			 */
			construct: function ( url, nonce ) {
				_.bindAll(
					this,
					'onFail',
					'retrieve',
					'save',
					'init'
				);

				this.nonce = nonce;
				this.url = url;

				return this;
			}
		};

		/**
		 * Factory
		 *
		 * @param {string} url The url to call.
		 * @param {string} nonce The nonce for the server.
		 *
		 * @returns {Migrate} The instance.
		 */
		BWU.Restore.FactoryMigrate = function FactoryMigrate( url, nonce ) {
			return Object
				.create( Migrate )
				.construct( url, nonce );
		};

	}( window.BWU, window._, window.jQuery )
);
