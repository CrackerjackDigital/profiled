(function($) {
    $('.profiled-tabs li.tab a').on('click', function(ev) {
        ev.preventDefault();
        $('.profiled-tabs li').removeClass('current');
        $(this).closest('li').addClass('current');

        $('.profiled-tab-body').removeClass('current');
        $($(this).attr('href')).addClass('current');
    });
    $(document).on('hashchange', function() {
        if ($('.profiled-tabs li').length) {
            var hash = document.location.hash;

            if (hash) {
                $('.profiled-tab-body').removeClass('current');
                $(hash).addClass('current');
            }
        }

    });
})(jQuery);