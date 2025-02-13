/**
 * Decrypter
 */

window.BWU = window.BWU || {};

(
	function iife( BWU, _, $ ) {

		var INVALID_KEY_STATUS = 'invalid';
		var AJAX_ACTION_DECRYPTION = 'decrypt';
		var CONTROLLER = 'decrypt';

		function showContainer() {
			this.containerEl.style.display = 'block';
		}

		function giveFocusToKeyInput() {
			$( this.keyField ).not( ':focus' ).focus();
		}

		function resetKeyInputValue() {
			this.keyField.value = '';
			this.keyField.setAttribute( 'value', '' );
		}

		function toggleInvalidKeyMessageByStatus( status ) {
			if ( status !== INVALID_KEY_STATUS ) {
				return;
			}

			giveFocusToKeyInput.call( this );
		}

		var Decrypter = {

			needDecryption: function ( status ) {
				showContainer.call( this );
				toggleInvalidKeyMessageByStatus.call( this, status );
			},

			hide: function () {
				this.containerEl.style.display = 'none';
			},

			destruct: function() {
				this.hide();
				resetKeyInputValue.call( this );
			},

			decrypt: function ( data ) {

				var data = _.extend( data, {
					decryption_key: this.keyField.value,
					action: AJAX_ACTION_DECRYPTION,
					controller: CONTROLLER
				} );
				var successCallback = function ( response ) {

					if ( !response.success ) {
						showContainer.call( this );
						BWU.Functions.printMessageError( response.data.message, this.containerEl );
						$( 'body' ).trigger( this.ACTION_DECRYPTION_FAILED, response );
						return;
					}

					this.hide();
					resetKeyInputValue.call( this );
					$( 'body' ).trigger( this.ACTION_DECRYPTION_SUCCESS, response );
				}.bind( this );
				var errorCallback = function ( response ) {

					this.hide();
					resetKeyInputValue.call( this );

					$( 'body' ).trigger( this.ACTION_DECRYPTION_FAILED, response );
				}.bind( this );

				this.hide();

				$.ajax( {
					url: this.url,
					data: data,
					method: 'POST',
					success: successCallback,
					error: errorCallback
				} );
			},

			construct: function ( url, containerEl, keyField ) {

				_.bindAll(
					this,
					'needDecryption',
					'decrypt',
					'destruct',
					'hide'
				);

				if ( !containerEl || !keyField ) {
					return false;
				}

				this.url = url;
				this.containerEl = containerEl;
				this.keyField = keyField;

				return this;
			}
		};

		BWU.DecrypterFactory = function ( url, containerEl, keyField ) {
			return Object
				.create( Decrypter, {
					ACTION_DECRYPTION_SUCCESS: BWU.Functions.makeConstant( 'backwpup.decryption_access' ),
					ACTION_DECRYPTION_FAILED: BWU.Functions.makeConstant( 'backwpup.decryption_failed' )
				} )
				.construct( url, containerEl, keyField );
		};
	}
)( window.BWU, window._, window.jQuery );
