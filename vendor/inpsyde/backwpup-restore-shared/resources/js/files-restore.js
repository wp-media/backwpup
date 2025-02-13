/**
 * Files Restore
 */

window.BWU = window.BWU || {};

(
	function iife( BWU, _, $, backwpupRestoreLocalized ) {
		'use strict';

		var FilesRestore = {

			/**
			 * Success Callback
			 *
			 * @returns {BWU} this for chaining
			 */
			restoreSuccess: function () {
				var $el = $( '#restore_progress' );

				if ( $el ) {
					$el.text( backwpupRestoreLocalized.successFilesRestored );
				}

				this.closeSource();

				return this;
			},

			/**
			 * Restore Failed
			 *
			 * @returns {BWU} this for chaining
			 */
			restoreFailed: function () {
				var $el = $( '#restore_progress' );

				if ( $el ) {
					$el.text( backwpupRestoreLocalized.failedRestore );
				}

				this.closeSource();

				return this;
			},

			/**
			 * On Open
			 *
			 * @returns {BWU} this for chaining
			 */
			onOpen: function () {
				$( '#start-restore' )
					.prop( 'disabled', true );

				var $el = $( '#restore_progress' );

				if ( $el ) {
					$el.text( backwpupRestoreLocalized.restoringDirectories );
				}

				return this;
			},

			/**
			 * Close Source
			 *
			 * Close the Event Source.
			 *
			 * @returns {BWU} this for chaining
			 */
			closeSource: function () {

				this.eventSource.close();

				$( '#start-restore' )
					.prop( 'disabled', false );

				return this;
			},

			/**
			 * On Done
			 *
			 * @param {object} data The data object to pass to the callback.
			 *
			 * @returns {BWU} this for chaining
			 */
			onDone: function ( data ) {
				if ( this.errors ) {
					this.restoreFailed();
				} else {
					this.restoreSuccess();
				}

				if ( _.isFunction( this.options.onSuccessCallback ) ) {
					this.options.onSuccessCallback.call( this, data );
				}

				return this;
			},

			/**
			 * On Message Callback
			 *
			 * The event source callback
			 *
			 * @param {string} message The message from the server.
			 *
			 * @returns {BWU} this for chaining
			 */
			onMessage: function ( message ) {
				// Make the server event data message an object.
				var data = JSON.parse( message.data );

				if ( 'done' === data.state ) {
					this.onDone( data );
				}

				if ( _.isFunction( this.options.onMessageCallback ) ) {
					this.options.onMessageCallback.call( this, data );
				}

				return this;
			},

      /**
       * On Error
       *
       * @returns {BWU} this for chaining
       */
      onError: function (data) {
        data = JSON.parse(data.data)
        var message = data.message
        var $messageContainer = $('#restore_step')

        this.eventSource.close()

        // Skip if not error.
        if (!data.state || 'error' !== data.state) {
          return this
        }

        BWU.Functions.printMessageError(message, $messageContainer)

        $('body').trigger(this.ACTION_FILE_RESTORE_ERROR, message)

        this.errors++

        return this
      },

			/**
			 * Restore
			 *
			 * @returns {BWU} this for chaining
			 */
			restore: function () {
				if ( !( 'EventSource' in window ) ) {
					console.warn( backwpupRestoreLocalized.noEventSourceInWindowObject ); // eslint-disable-line

					return this;
				}

				this.eventSource = new EventSource(
					this.url + '?action=restore_dir&context=event_source&controller=job&backwpup_action_nonce=' + this.nonce );
				this.eventSource.onmessage = this.onMessage;
				this.eventSource.onopen = this.onOpen;

				this.eventSource.addEventListener( 'log', this.onError );

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
			 * @param {object} options The options for the object.
			 *
			 * @returns {BWU} this for chaining
			 */
			construct: function ( url, nonce, options ) {
				_.bindAll(
					this,
					'restoreSuccess',
					'restoreFailed',
					'onOpen',
					'closeSource',
					'onDone',
					'onMessage',
					'onError',
					'restore',
					'init'
				);

				this.eventSource = null;
				this.url = url;
				this.nonce = nonce;
				this.errors = 0;
				this.options = {
					onMessageCallback: null,
					onSuccessCallback: null
				};

				_.extend( this.options, options );

				return this;
			}
		};

    /**
     * Construct
     *
     * @param {string} url The url where call the server.
     * @param {string} nonce The nonce for the request.
     * @param {object} options The options for the object.
     *
     * @returns {FilesRestore} The object
     */
    BWU.Restore.FactoryFilesRestore = function FactoryFilesRestore (
      url, nonce, options) {
      return Object.create(
        FilesRestore,
        {
          ACTION_FILE_RESTORE_ERROR: BWU.Functions.makeConstant(
            'backwpup.database_restore_error'
          ),
        }).construct(url, nonce, options)
    }

	}( window.BWU, window._, window.jQuery, window.backwpupRestoreLocalized )
);
