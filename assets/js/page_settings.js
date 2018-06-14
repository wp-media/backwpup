jQuery( document ).ready( function ( $ ) {
	var anchor        = $( 'input[name="anchor"]' );
	var tab_wrapper_a = $( '.nav-tab-wrapper>a' );
	var actual_anchor = window.location.hash;

	if ( actual_anchor !== '' ) {
		actual_anchor = '#' + actual_anchor.replace( '#', '' );
	}

	if ( actual_anchor !== '' ) {
		anchor.val( actual_anchor );
	}

	$( '.table' ).addClass( 'ui-tabs-hide' );
	$( anchor.val() ).removeClass( 'ui-tabs-hide' );

	if ( anchor.val() === '#backwpup-tab-information' ) {
		$( '#submit' ).hide();
		$( '#default_settings' ).hide();
	}

	tab_wrapper_a.removeClass( 'nav-tab-active' );
	tab_wrapper_a.each( function () {
		if ( $( this ).attr( 'href' ) === anchor.val() ) {
			$( this ).addClass( 'nav-tab-active' );
		}
	} );

	tab_wrapper_a.on( 'click', function () {
		var clickedid = $( this ).attr( 'href' );
		tab_wrapper_a.removeClass( 'nav-tab-active' );
		$( this ).addClass( 'nav-tab-active' );
		$( '.table' ).addClass( 'ui-tabs-hide' );
		$( clickedid ).removeClass( 'ui-tabs-hide' );
		$( '#message' ).hide();
		anchor.val( clickedid );
		if ( clickedid === '#backwpup-tab-information' ) {
			$( '#submit' ).hide();
			$( '#default_settings' ).hide();
		} else {
			$( '#submit' ).show();
			$( '#default_settings' ).show();
		}
		window.location.hash = clickedid;
		window.scrollTo( 0, 0 );
		return false;
	} );

	$( '#authentication_method' ).change( function () {
		var auth_method = $( '#authentication_method' ).val();
		if ( '' === auth_method ) {
			$( '.authentication_basic' ).hide();
			$( '.authentication_query_arg' ).hide();
			$( '.authentication_user' ).hide();
		} else if ( 'basic' === auth_method ) {
			$( '.authentication_basic' ).show();
			$( '.authentication_query_arg' ).hide();
			$( '.authentication_user' ).hide();
		} else if ( 'query_arg' === auth_method ) {
			$( '.authentication_basic' ).hide();
			$( '.authentication_query_arg' ).show();
			$( '.authentication_user' ).hide();
		} else if ( 'user' === auth_method ) {
			$( '.authentication_basic' ).hide();
			$( '.authentication_query_arg' ).hide();
			$( '.authentication_user' ).show();
		}
	} );

	if ( $( '#encryption-symmetric' ).is( ':checked' ) ) {
		$( '#encryption-key-row' ).show();
		$( '#public-key-row' ).hide();
	} else if ( $( '#encryption-asymmetric' ).is( ':checked' ) ) {
		$( '#encryption-key-row' ).hide();
		$( '#public-key-row' ).show();
	}

	$( 'input:radio[name="encryption"]' ).change( function () {
		if ( $( this ).val() === "symmetric" ) {
			$( '#encryption-key-row' ).show();
			$( '#public-key-row' ).hide();
		} else if ( $( this ).val() === "asymmetric" ) {
			$( '#encryption-key-row' ).hide();
			$( '#public-key-row' ).show();
		}
	} );

	$( '#generate-key-button' ).click( function ( e ) {
		e.preventDefault();
		e.stopPropagation();

		// Only run if key is not already set
		if ( $( '#encryptionkey' ).val() !== '' ) {
			return;
		}

		$( '#generate-key-button' ).attr( 'disabled', 'disabled' );

		var data = {
			action     : 'generate_key',
			_ajax_nonce: $( '#backwpupajaxnonce' ).val(),
		};

		$.post( ajaxurl, data, function ( response ) {
			$( '#encryptionkey' ).val( response );
			$( '#encryptionkey' ).focus();
			$( '#key-generation' ).hide();
		} );
	} );

	$( '#validate-key-button' ).click( function ( e ) {
		if ( $( '#publickey' ).val() === '' ) {
			e.preventDefault();
			alert( backwpup_vars.no_public_key );
			$( '#publickey' ).focus();
			return false;
		}

		// Put focus on private key field after a time, to give time for opening dialog.
		setTimeout( function () {
			$( '#privatekey' ).focus();
		}, 100 );

	} );

	$( '#do-validate' ).click( function () {
		if ( $( '#privatekey' ).val() === '' ) {
			alert( backwpup_vars.no_private_key );
			$( '#privatekey' ).focus();
			return false;
		}

		var data = {
			action     : 'validate_key',
			publickey  : $( '#publickey' ).val(),
			privatekey : $( '#privatekey' ).val(),
			_ajax_nonce: $( '#backwpupajaxnonce' ).val(),
		};

		$.post( ajaxurl, data, function ( response ) {
			if ( response === 'valid' ) {
				alert( backwpup_vars.public_key_valid );
				tb_remove();
				$( '#publickey' ).focus();
			} else {
				alert( backwpup_vars.public_key_invalid );
			}
			$( '#privatekey' ).val( '' );
		} )
			.fail( function ( xhr, status, error ) {
				alert( error );
			} );
	} );

	$( '#generate-key-pair-button' ).click( function () {
		$( '#key-pair-generating-progress' ).show();
		$( '#key-pair-generating-done' ).hide();

		var data = {
			action     : 'generate_key_pair',
			_ajax_nonce: $( '#backwpupajaxnonce' ).val()
		};

		$.post( ajaxurl, data, function ( response ) {
			$( '#generated-public-key' ).val( response.public_key );
			$( '#generated-public-key-link' ).attr( 'href', 'data:text/plain;base64,' + btoa( response.public_key ) );
			$( '#generated-private-key' ).val( response.private_key );
			$( '#generated-private-key-link' ).attr( 'href', 'data:text/plain;base64,' + btoa( response.private_key ) );
			$( '#key-pair-generating-progress' ).hide();
			$( '#key-pair-generating-done' ).show();
			$( '#generated-public-key' ).focus();
		} );

	} );

	var private_key_downloaded = false;
	$( '#generated-private-key-link' ).click( function () {
		private_key_downloaded = true;
	} );

	$( '#use-key-pair-button' ).click( function () {
		if ( private_key_downloaded === false ) {
			alert( backwpup_vars.must_download_private_key );
			return false;
		}
		$( '#publickey' ).val( $( '#generated-public-key' ).val() );
		tb_remove();
		$( '#publickey' ).focus();
	} );

	setTimeout( function () {
		window.scrollTo( 0, 0 );
	}, 1 );

} );
