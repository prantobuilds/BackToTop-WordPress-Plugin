jQuery(document).ready(function($) {
    var $button = $('#back-to-top');
    
    // Ensure threshold exists, default to 300
    var threshold = (typeof bttb_vars !== 'undefined') ? bttb_vars.scroll_dist : 300;

    $(window).scroll(function() {
        if ($(window).scrollTop() > threshold) {
            $button.addClass('show');
        } else {
            $button.removeClass('show');
        }
    });

    $button.on('click', function(e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});