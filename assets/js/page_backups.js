jQuery( document ).ready( function ( $ ) {

	$( '.backup-download-link' ).click( function () {
		$( '#download-file-waiting' ).show();
		$( '#download-file-generating' ).hide();
		$( '#download-file-private-key' ).hide();
		$( '#download-file-done' ).hide();

		var link              = this;
		var eventsource       = new EventSource( ajaxurl + '?action=download_file&destination=' + $( this ).data( 'destination' ) + '&jobid=' + $( this ).data( 'jobid' ) + '&file=' + $( this ).data( 'file' ) + '&local_file=' + $( this ).data( 'localFile' ) + '&_wpnonce=' + $( this ).data( 'nonce' ) );
		eventsource.onmessage = function ( message ) {
			var data = JSON.parse( message.data );
			if ( data.state === 'downloading' ) {
				$( '#download-file-waiting' ).hide();
				$( '#download-file-generating' ).show();
				$( '#download-file-private-key' ).hide();
				$( '#download-file-done' ).hide();

				// Progress bar
				$( '#progresssteps' )
					.css( {
						width: data.download_percent + '%'
					} )
					.text( data.download_percent + '%' );

			} else if ( data.state === 'need-private-key' ) {
				$( '#download-file-waiting' ).hide();
				$( '#download-file-generating' ).hide();
				$( '#download-file-private-key' ).show();
				$( '#download-file-done' ).hide();
				$( '#download-file-private-key-input' ).focus();
				if ( data.status === 'invalid' ) {
					// Private key invalid
					$( '#download-file-private-key-invalid' ).show();
				} else {
					$( '#download-file-private-key-invalid' ).hide();
				}
			} else if ( data.state === 'done' ) {
				$( '#download-file-waiting' ).hide();
				$( '#download-file-generating' ).hide();
				$( '#download-file-private-key' ).hide();
				$( '#download-file-done' ).show();
				eventsource.close();
				window.location.href = $( link ).data( 'url' );
				setTimeout( function () {
					tb_remove();
				}, 3000 );
			}
		};

	} );

	$( '#download-file-private-key-button' ).click( function () {
		data = {
			action    : 'send_private_key',
			privatekey: $( '#download-file-private-key-input' ).val(),
		};

		$.post( ajaxurl, data, function ( response ) {
			if ( response === 'ok' ) {
				$( '#download-file-private-key-input' ).val( '' );
				$( '#download-file-private-key-invalid' ).hide();
				$( '#download-file-private-key' ).hide();
				$( '#download-file-generating' ).show();
			}
		} );
	} );
} );