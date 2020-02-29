jQuery(document).ready(function ($) {


    $('.codo_menu_user').before('<li class="nav-item codo_pmx_link"><a class="nav-link"><i class="fa fa-envelope" style="font-size: 16px;margin-bottom: 2px;"></i></a></li>');

    $('.codo_mobile_menu_notifications').after('<li class="nav-item codo_pmx_link"><a class="nav-link"><i class="fa fa-envelope"></i>' + codo_defs.trans.pmx_title + '</a></li>');

    $('.codo_pmx_link').on('click', function() {
        window.location = codo_defs.url + 'messenger';
    });

    $('.freichat_send_pm_box').on('click', function () {
        const uid = $(this).data('uid');
        window.location = codo_defs.url + 'messenger#user-' + uid;
    });
});