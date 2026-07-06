/**
 * Delete stale restore files — admin notice handler.
 *
 * Attaches a click handler to the "Delete restore files" button rendered by
 * NoticeStaleRestoreFiles. On success the notice node is removed from the DOM
 * without a page reload. On error the message is displayed inline.
 */
( function () {
	'use strict';

	/**
	 * Initialise the delete-restore-files button handler.
	 *
	 * Called once the DOM is ready.
	 *
	 * @return {void}
	 */
	function init() {
		var btn = document.getElementById( 'backwpup-delete-restore-files' );

		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', handleClick );
	}

	/**
	 * Handle a click on the delete button.
	 *
	 * @param {MouseEvent} event The click event.
	 * @return {void}
	 */
	function handleClick( event ) {
		event.preventDefault();

		var btn    = /** @type {HTMLButtonElement} */ ( event.currentTarget );
		var nonce  = btn.getAttribute( 'data-nonce' );
		var action = btn.getAttribute( 'data-action' ) || 'backwpup_delete_restore_files';

		// Prevent double-submit.
		btn.disabled = true;

		// Clear any previous inline error.
		var errorSpan = document.getElementById( 'backwpup-delete-restore-files-error' );
		if ( errorSpan ) {
			errorSpan.textContent = '';
			errorSpan.style.display = 'none';
		}

		// Build the form-encoded body.
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( '_ajax_nonce', nonce || '' );

		// ajaxurl is provided by WordPress on all admin screens.
		var ajaxUrl = ( typeof window.ajaxurl === 'string' ) ? window.ajaxurl : '';

		fetch( ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( data && data.success === true ) {
					// Remove the closest notice wrapper from the DOM.
					var notice = btn.closest( '.notice-inpsyde' ) || btn.closest( '.notice' );
					if ( notice && notice.parentNode ) {
						notice.parentNode.removeChild( notice );
					}
				} else {
					// Show the error message returned by the server.
					var message =
						( data && data.data && data.data.message )
							? data.data.message
							: 'An error occurred. Please delete the restore files manually.';

					showError( btn, errorSpan, message );
				}
			} )
			.catch( function () {
				showError(
					btn,
					errorSpan,
					'An error occurred. Please delete the restore files manually.'
				);
			} );
	}

	/**
	 * Display an error message and re-enable the button.
	 *
	 * @param {HTMLButtonElement}    btn       The delete button.
	 * @param {HTMLElement|null}     errorSpan The error container element.
	 * @param {string}               message   The error message to display.
	 * @return {void}
	 */
	function showError( btn, errorSpan, message ) {
		btn.disabled = false;

		if ( errorSpan ) {
			errorSpan.textContent = message;
			errorSpan.style.display = '';
		}
	}

	// Attach after DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
