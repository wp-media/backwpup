( function ( $, ajaxurl, tbRemove ) {

	var link;
	var eventsource;
	var $waiting;
	var $generating;
	var $privateKey;
	var $done;
	var $privateKeyInput;
	var $privateKeyInvalid;
	var $privateKeySubmit;

	if ( !ajaxurl ) {
		console.warn( 'Missing ajaxurl value.' ); // eslint-disable-line

		return;
	}
	if ( !( 'EventSource' in window ) ) {
		console.warn( 'Event Source does not exist in this browser' ); // eslint-disable-line

		return;
	}

	window.addEventListener( 'load', function () {
		$waiting = $( '#download-file-waiting' );
		$generating = $( '#download-file-generating' );
		$privateKey = $( '#download-file-private-key' );
		$done = $( '#download-file-done' );
		$privateKeyInput = $( '#download-file-private-key-input' );
		$privateKeyInvalid = $( '#download-file-private-key-invalid' );
		$privateKeySubmit = $( '#download-file-private-key-button' );

		$( '.backup-download-link' ).click( function () {
			$waiting.show();
			$generating.hide();
			$privateKey.hide();
			$done.hide();

			link = this;
			eventsource = new EventSource(
				ajaxurl
				+ '?action=download_file&destination=' + $( this ).data( 'destination' )
				+ '&jobid=' + $( this ).data( 'jobid' )
				+ '&file=' + $( this ).data( 'file' )
				+ '&local_file=' + $( this ).data( 'localFile' )
				+ '&_wpnonce=' + $( this ).data( 'nonce' )
			);

			eventsource.onmessage = function ( message ) {
				var data = JSON.parse( message.data );

				if ( 'downloading' === data.state ) {
					$waiting.hide();
					$generating.show();
					$privateKey.hide();
					$done.hide();

					// Progress bar
					$( '#progresssteps' )
						.css( {
							width: data.download_percent + '%'
						} )
						.text( data.download_percent + '%' );
				}

				if ( 'need-private-key' === data.state ) {
					$waiting.hide();
					$generating.hide();
					$privateKey.show();
					$done.hide();
					$privateKeyInput.focus();

					if ( 'invalid' === data.status ) {
						$privateKeyInvalid.show();

						return;
					}

					$privateKeyInvalid.hide();
				}

				if ( 'done' === data.state ) {
					$waiting.hide();
					$generating.hide();
					$privateKey.hide();
					$done.show();

					eventsource.close();

					window.location.href = $( link ).data( 'url' );

					setTimeout( tbRemove, 3000 );
				}
			};

		} );

		$privateKeySubmit.click( function () {
			var data = {
				action: 'send_private_key',
				privatekey: $privateKeyInput.val(),
			};

			$.post( ajaxurl, data, function ( response ) {
				if ( true === response.success ) {
					$privateKeyInput.val( '' );
					$privateKeyInvalid.hide();
					$privateKey.hide();
					$generating.show();
				}
			} );
		} );
	} );

}( window.jQuery, window.ajaxurl, window.tb_remove ) );
