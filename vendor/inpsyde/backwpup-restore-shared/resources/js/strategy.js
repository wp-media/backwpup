/**
 * Restore Strategy
 */

window.BWU = window.BWU || {};
window.BWU.Restore = window.BWU.Restore || {};
window.BWU.Restore.Factory = window.BWU.Restore.Factory || {};

(
	function iife( BWU, _, $ ) {
		'use strict';

		var Strategy = {
			/**
			 * On action fail
			 *
			 * @param {object} jqXHR The response object from the server.
			 *
			 * @returns {BWU} this for chainging
			 */
			onFail: function ( jqXHR ) {
				// Print error message.
				BWU.Functions.printMessageError( jqXHR.responseJSON.data.message, $( '#restore_step' ) );

				return this;
			},

			/**
			 * Save strategy
			 *
			 * @param {string} strategy The strategy to save in the registry.
			 *
			 * @returns {BWU} this for chaining
			 */
			save: function ( strategy, successCallback ) {

				// If empty don't do anything.
				if ( !strategy ) {
					return;
				}

				$.ajax( {
					type: 'POST',
					url: this.url,
					cache: false,
					dataType: 'json',
					data: {
						controller: 'job',
						action: 'save_strategy',
						strategy: strategy,
						backwpup_action_nonce: this.nonce
					}
				} )
				.done( successCallback )
					.fail( this.onFail );

				return this;
			},

			/**
			 * Retrieve Strategy
			 *
			 * @param {function} successCallback The function to execute after the strategy has been retrieved.
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
						action: 'get_strategy',
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
					'save',
					'retrieve',
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
		 * @returns {Strategy} The instance.
		 */
		BWU.Restore.FactoryStrategy = function FactoryStrategy( url, nonce ) {
			return Object
				.create( Strategy )
				.construct( url, nonce );
		};

	}( window.BWU, window._, window.jQuery )
);
