( function ( _, $, adminpage ) {

	var Notice = {

		dismiss: function ( evt ) {
			evt.preventDefault();
			evt.stopImmediatePropagation();

			$.post( $( evt.target ).attr( 'href' ), { isAjax: 1 } );
			$( evt.target ).closest( '.notice-inpsyde' ).remove();
		},

		construct: function () {

			var $container;

			_.bindAll(
				this,
				'dismiss',
				'init',
				'addListeners'
			);

			$container = $( '.notice-inpsyde' );

			if ( $container.length === 0 ) {
				return false;
			}

			this.$container = $container;

			return this;
		},

		addListeners: function () {
			var $dismisser = this.$container.find( '.dismiss-button' );
			$dismisser.length > 0 && $dismisser.on( 'click', this.dismiss );
		},

		init: function () {
			this.addListeners();

			return this;
		},
	};

	var notice = Object.create( Notice );
	notice.construct() && notice.init();
}( window._, window.jQuery, window.adminpage ) );
