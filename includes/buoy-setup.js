jQuery(document).ready(function () {
    BUOY.init();
});

jQuery(document).on('install.ios', function () {
    BUOY.installWebApp();
});
