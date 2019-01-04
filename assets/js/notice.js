( function ( _, $, adminpage ) {

	var PromoterNotice = {

		dismiss: function ( evt ) {
			evt.preventDefault();
			evt.stopImmediatePropagation();

			$.post( $( evt.target ).attr( 'href' ), { isAjax: 1 } );
			this.container.remove();
		},

		construct: function () {

			var container;

			_.bindAll(
				this,
				'dismiss',
				'init',
				'addListeners'
			);

			container = document.querySelector( '#backwpup_notice_promoter_notice', {
				useCapture: true
			} );

			if ( !container ) {
				return false;
			}

			this.container = container;

			return this;
		},

		addListeners: function () {
			var dismisser = this.container.querySelector( '#backwpup_notice_promoter_dismiss' );
			dismisser && dismisser.addEventListener( 'click', this.dismiss );
		},

		init: function () {
			this.addListeners();

			return this;
		},
	};

	if ( adminpage === 'toplevel_page_backwpup' ) {
		var promoterNotice = Object.create( PromoterNotice );
		promoterNotice.construct() && promoterNotice.init();
	}
}( window._, window.jQuery, window.adminpage ) );
