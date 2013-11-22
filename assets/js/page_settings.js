jQuery(document).ready(function ($) {
    $('.table').addClass('ui-tabs-hide');
    $((window.location.hash || "#backwpup-tab-general")).removeClass('ui-tabs-hide');
    $('.nav-tab-wrapper>a').removeClass('nav-tab-active');
    $('.nav-tab-wrapper>a').each(function (index) {
        if ($(this).attr('href') == (window.location.hash || "#backwpup-tab-general")) {
            $(this).addClass('nav-tab-active');
        }
    });
    $('.nav-tab-wrapper>a').click(function () {
        var clickedid = $(this).attr('href');
        $('.nav-tab-wrapper>a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.table').addClass('ui-tabs-hide');
        $(clickedid).removeClass('ui-tabs-hide');
        $('#message').hide();
        $('input[name="anchor"]').val(clickedid);
		if ( clickedid == '#backwpup-tab-information' ) {
			$('#submit').hide();
			$('#default_settings').hide();
		} else {
			$('#submit').show();
			$('#default_settings').show();
		}
		return false;
    });
});