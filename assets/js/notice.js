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

jQuery(document).ready(function ($) {
	$('#backwpup_notice_evaluate_step_review').hide();
	$('#backwpup_notice_evaluate_step_issue').hide();
	$('#backwpup_notice_evaluate_step_thanks').hide();

	$('.doubleLink').on('click', function(event) {
		event.preventDefault();
		window.open(this.href, '_blank');
		$.ajax({
			type : "get",
			datatype : "html",
			url : $(this).attr('hrefbis')
		})
		$('#backwpup_notice_evaluate_step_review').hide();
		$('#backwpup_notice_evaluate_step_issue').hide();
		$('#backwpup_notice_evaluate_step_thanks').show();
	});

	$('#backwpup_notice_evaluate_working').on('click', function(event) {
		$('#backwpup_notice_evaluate_step1').hide();
		$('#backwpup_notice_evaluate_step_review').show();
	});

	$('#backwpup_notice_evaluate_issues').on('click', function(event) {
		$('#backwpup_notice_evaluate_step1').hide();
		$('#backwpup_notice_evaluate_step_issue').show();
	});
});
