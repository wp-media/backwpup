var backwpup_htmlspecialchars = function( string ) {
	return jQuery('<span>').text( string ).html()
};

jQuery(document).ready(function ($) {
    $('.help_tip').tipTip({
        'attribute':'title',
        'fadeIn':50,
        'fadeOut':50,
		'keepAlive': true,
		'activation': 'hover'
    });
});